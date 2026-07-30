<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\HeaderServiceInterface;
use App\Services\CalculadoraServiceInterface;
use App\Models\Venta;

class AnalyticsFallabellaController extends Controller
{
    protected $headerService;
    protected $calculadoraService;

    public function __construct(HeaderServiceInterface $headerService, CalculadoraServiceInterface $calculadoraService)
    {
        $this->headerService = $headerService;
        $this->calculadoraService = $calculadoraService;
    }

    /**
     * Valida si el usuario tiene acceso a la vista específica.
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

        // Podrías validar acceso aquí si es necesario, usar 13 como analitica falabella
        if (!$this->validateAccess($userModel, 13)) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
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
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
            return redirect()->route('dashboard', ['user' => $userModel]);
        }

        $tc = $this->calculadoraService->getTasaCambio();
        [$fechaInicio, $fechaFin, $anio, $mes] = $this->resolveDateRange($request);
        $gruposCostoBajo = $this->calculadoraService->getGruposCostoExcepcion();

        $subqueryTipoCambioCosto = "(SELECT COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE(c_inner.fechaRegistro))) ASC LIMIT 1), $tc))";

        $costoVentaExpr = $this->calculadoraService->getCostoVentaExpr($subqueryTipoCambioCosto, (string)$tc);

        $comisionFalabellaExpr = "CASE WHEN UPPER(Venta.canal) = 'FALABELLA' THEN 
                (CASE WHEN GrupoProducto.idCategoria IN (1, 3) OR GrupoProducto.idGrupoProducto IN (10, 40, 41, 42, 43) THEN 10.90 ELSE 3.90 END)
                + (DetalleVenta.precioVenta * CASE WHEN GrupoProducto.idGrupoProducto = 10 THEN 0.08 WHEN GrupoProducto.idGrupoProducto IN (155, 156, 157, 158, 159, 160, 169) THEN 0.15 ELSE 0.10 END)
            ELSE 0 END";

        $costosComponentesSubInner = $this->calculadoraService->getCostoVentaExpr($subqueryTipoCambioCosto, (string)$tc, 'dv_comp', 'p_comp');
        $costosComponentesSub = "COALESCE((SELECT SUM(
            ({$costosComponentesSubInner}) * dv_comp.cantidad
        )
        FROM DetalleVenta dv_comp
        LEFT JOIN Producto p_comp ON dv_comp.idProducto = p_comp.idProducto
        WHERE dv_comp.idVenta = DetalleVenta.idVenta
        AND dv_comp.precioVenta <= 0.10) / 
        GREATEST((SELECT COUNT(*) FROM DetalleVenta dv_main WHERE dv_main.idVenta = DetalleVenta.idVenta AND dv_main.precioVenta > 0.10), 1)
        , 0)";

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
                         SUM(($comisionFalabellaExpr) * DetalleVenta.cantidad) as comision_falabella")
            ->where('DetalleVenta.precioVenta', '>', 0.10)
            ->where('DetalleVenta.estado', 'COMPLETADO')
            ->whereRaw("UPPER(Venta.canal) = 'FALABELLA'")
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->groupBy('Venta.idVenta', 'Venta.numeroOrden', 'Venta.fechaVenta', 'Venta.idUser', 'Usuario.user')
            ->orderByDesc('Venta.fechaVenta')
            ->get()
            ->map(function ($venta) {
                $venta->ingresos = round($venta->ingresos, 2);
                $venta->costos   = round($venta->costos, 2);
                $venta->ganancia = round($venta->ingresos - $venta->costos, 2);
                $venta->comision_falabella = round($venta->comision_falabella, 2);
                $venta->margen = $venta->ingresos > 0 ? round(($venta->ganancia / $venta->ingresos) * 100, 2) : 0;
                return $venta;
            });

        // ── Consulta para agrupar por SKU (Modelo) ───────────────────
        $skusFalabella = \App\Models\DetalleVenta::query()
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
            ->map(function ($sku) {
                $sku->ingresos = round($sku->ingresos, 2);
                $sku->costos = round($sku->costos, 2);
                $sku->ganancia = round($sku->ingresos - $sku->costos, 2);
                $sku->comision_falabella = round($sku->comision_falabella, 2);
                $sku->margen = $sku->ingresos > 0 ? round(($sku->ganancia / $sku->ingresos) * 100, 2) : 0;
                return $sku;
            });

        $skusMayorRotacion = $skusFalabella->sortByDesc('total_unidades')->take(5)->values();
        $skusMayorRentabilidad = $skusFalabella->sortByDesc('ganancia')->take(5)->values();

        // ── Consulta para tendencia de ventas por mes (Gráfico) ────────
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
