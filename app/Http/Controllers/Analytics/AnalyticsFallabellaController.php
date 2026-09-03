<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\HeaderServiceInterface;
use App\Services\CalculadoraServiceInterface;
use App\Services\GananciaQueryService;
use App\Models\Ventas\Venta;

class AnalyticsFallabellaController extends Controller
{
    protected $headerService;
    protected $calculadoraService;
    protected $gananciaQueryService;

    public function __construct(HeaderServiceInterface $headerService, CalculadoraServiceInterface $calculadoraService, GananciaQueryService $gananciaQueryService)
    {
        $this->headerService = $headerService;
        $this->calculadoraService = $calculadoraService;
        $this->gananciaQueryService = $gananciaQueryService;
    }

    /**
     * Valida si el usuario tiene acceso a la vista especÃ­fica.
     */
    private function validateAccess($userModel, int $idVista)
    {
        return $userModel->Accesos->contains('idVista', $idVista);
    }

    /**
     * Resuelve las fechas de inicio y fin desde el Request.
     */
    private function resolveDateRange(Request $request)
    {
        Carbon::setLocale('es');
        $anio = (int) $request->query('anio', now()->year);
        $mes  = (int) $request->query('mes', now()->month);

        $fechaInicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFin    = Carbon::create($anio, $mes, 1)->endOfMonth()->endOfDay();

        if ($request->filled('dia_inicio')) {
            $fechaInicio = Carbon::parse($request->query('dia_inicio'))->startOfDay();
            $fechaFin = $request->filled('dia_fin')
                ? Carbon::parse($request->query('dia_fin'))->endOfDay()
                : $fechaInicio->copy()->endOfDay();
        } elseif ($request->filled('dia_fin')) {
            $fechaFin = Carbon::parse($request->query('dia_fin'))->endOfDay();
        }

        return [$fechaInicio, $fechaFin, $anio, $mes];
    }

    public function scraperFalabella(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        // PodrÃ­as validar acceso aquÃ­ si es necesario, usar 13 como analitica falabella
        if (!$this->validateAccess($userModel, 13)) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaÃ±a', 'warning', 'btn-danger');
            return redirect()->route('dashboard');
        }

        return view('analytics.scraper_falabella', [
            'user' => $userModel
        ]);
    }

    public function falabella(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        if (!$this->validateAccess($userModel, 13)) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaÃ±a', 'warning', 'btn-danger');
            return redirect()->route('dashboard', ['user' => $userModel]);
        }

        $tc = $this->calculadoraService->getTasaCambio();
        [$fechaInicio, $fechaFin, $anio, $mes] = $this->resolveDateRange($request);
        $gruposCostoBajo = $this->calculadoraService->getGruposCostoExcepcion();

        $exprs = $this->gananciaQueryService->getSqlExpressions($tc);
        extract($exprs);

        // Usamos el Modelo Venta para iniciar la consulta
        $ventasFalabella = Venta::query()
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->leftJoin('Usuario', 'Venta.idUser', '=', 'Usuario.idUser')
            ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->selectRaw("Venta.idVenta, Venta.numeroOrden, Venta.fechaVenta, Venta.idUser, Usuario.user as nombre_usuario,
                         GROUP_CONCAT(Producto.modelo SEPARATOR ', ') as modelo,
                         SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                         SUM((($costoVentaExpr) + ($comisionFalabellaExpr)) * DetalleVenta.cantidad + ($costosComponentesSub)) as costos,
                         SUM(($comisionFalabellaExpr) * DetalleVenta.cantidad) as comision_falabella,
                         {$subqueryTcDia} as tc_dia")
            ->where('DetalleVenta.precioVenta', '>', 0.10)
            ->where('DetalleVenta.estado', 'COMPLETADO')
            ->whereRaw("UPPER(Venta.canal) = 'FALABELLA'")
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->groupBy('Venta.idVenta', 'Venta.numeroOrden', 'Venta.fechaVenta', 'Venta.idUser', 'Usuario.user')
            ->orderByDesc('Venta.fechaVenta')
            ->get()
            ->map(fn($venta) => $this->gananciaQueryService->formatVentaItem($venta));

        // â”€â”€ Consulta para agrupar por SKU (Modelo) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $skusFalabella = \App\Models\Ventas\DetalleVenta::query()
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->join('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->selectRaw("Producto.modelo as sku,
                         SUM(DetalleVenta.cantidad) as total_unidades,
                         SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                         SUM((($costoVentaExpr) + ($comisionFalabellaExpr)) * DetalleVenta.cantidad + ($costosComponentesSub)) as costos,
                         SUM(($comisionFalabellaExpr) * DetalleVenta.cantidad) as comision_falabella")
            ->where('DetalleVenta.precioVenta', '>', 0.10)
            ->whereRaw("UPPER(Venta.canal) = 'FALABELLA'")
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->groupBy('Producto.modelo')
            ->orderByDesc('ingresos')
            ->get()
            ->map(fn($sku) => $this->gananciaQueryService->formatVentaItem($sku));

        $skusMayorRotacion = $skusFalabella->sortByDesc('total_unidades')->take(5)->values();
        $skusMayorRentabilidad = $skusFalabella->sortByDesc('ganancia')->take(5)->values();

        // â”€â”€ Consulta para tendencia de ventas por mes (GrÃ¡fico) â”€â”€â”€â”€â”€â”€â”€â”€
        $ventasMesRaw = Venta::query()
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->selectRaw('DATE(Venta.fechaVenta) as fecha, SUM(DetalleVenta.cantidad) as total_unidades, SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as total_monto')
            ->where('DetalleVenta.precioVenta', '>', 0.10)
            ->where('DetalleVenta.estado', 'COMPLETADO')
            ->whereRaw("UPPER(Venta.canal) = 'FALABELLA'")
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->groupBy(\Illuminate\Support\Facades\DB::raw('DATE(Venta.fechaVenta)'))
            ->orderBy('fecha', 'asc')
            ->get();

        $ventasMes = [];
        for ($date = $fechaInicio->copy(); $date->lte($fechaFin); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $found = $ventasMesRaw->firstWhere('fecha', $dateStr);
            $ventasMes[] = [
                'fecha' => $date->format('d/m'),
                'total' => $found ? $found->total_unidades : 0,
                'monto' => $found ? round($found->total_monto, 2) : 0
            ];
        }

        return view('analytics.components.falabella.index', [
            'user' => $userModel,
            'ventasFalabella' => $ventasFalabella,
            'skusFalabella' => $skusFalabella,
            'skusMayorRotacion' => $skusMayorRotacion,
            'skusMayorRentabilidad' => $skusMayorRentabilidad,
            'ventasMes' => $ventasMes,
            'filtros' => compact('anio', 'mes') + $request->only('dia_inicio', 'dia_fin') + ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin],
        ]);
    }
}

