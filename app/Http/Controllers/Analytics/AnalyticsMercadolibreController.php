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

class AnalyticsMercadolibreController extends Controller
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

    private function validateAccess($userModel, int $idVista)
    {
        return $userModel->Accesos->contains('idVista', $idVista);
    }

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

    public function index(Request $request)
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
        $ventasMercadoLibre = Venta::query()
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->leftJoin('Usuario', 'Venta.idUser', '=', 'Usuario.idUser')
            ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->leftJoin('ml_orders', 'Venta.numeroOrden', '=', 'ml_orders.ml_order_id')
            ->selectRaw("Venta.idVenta, Venta.numeroOrden, Venta.fechaVenta, Venta.idUser, Usuario.user as nombre_usuario,
                         GROUP_CONCAT(Producto.modelo SEPARATOR ', ') as modelo,
                         SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                         SUM((($costoVentaExpr) + ($comisionMercadoLibreExpr)) * DetalleVenta.cantidad + ($costosComponentesSub)) as costos,
                         SUM(($comisionMercadoLibreExpr) * DetalleVenta.cantidad) as comision_mercadolibre,
                         MAX(ml_orders.logistic_label) as logistic_label,
                         MAX(ml_orders.shipping_status) as shipping_status,
                         MAX(ml_orders.return_status) as return_status")
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->where('DetalleVenta.estado', '!=', 'DEVUELTO')
            ->whereRaw("(UPPER(Venta.canal) = 'MERCADO LIBRE' OR UPPER(Venta.canal) = 'MERCADOLIBRE')")
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->groupBy('Venta.idVenta', 'Venta.fechaVenta', 'Venta.idUser', 'Usuario.user')
            ->orderByDesc('Venta.fechaVenta')
            ->get()
            ->map(fn($venta) => $this->gananciaQueryService->formatVentaItem($venta));

        // Cuentas de Mercado Libre
        $cuentasMercadoLibre = DB::table('Venta')
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->join('Publicacion', 'DetalleVenta.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->join('CuentasPlataforma', 'Publicacion.idCuentaPlataforma', '=', 'CuentasPlataforma.idCuentaPlataforma')
            ->whereRaw("(UPPER(Venta.canal) = 'MERCADO LIBRE' OR UPPER(Venta.canal) = 'MERCADOLIBRE')")
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->select(
                'CuentasPlataforma.nombreCuenta',
                DB::raw('COUNT(DISTINCT Venta.idVenta) as cantidad_ventas'),
                DB::raw('SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as total_ingresos'),
                DB::raw("SUM((($costoVentaExpr) + ($comisionMercadoLibreExpr)) * DetalleVenta.cantidad + ($costosComponentesSub)) as total_costos")
            )
            ->groupBy('CuentasPlataforma.nombreCuenta')
            ->orderByDesc('total_ingresos')
            ->get()
            ->map(fn($cuenta) => $this->gananciaQueryService->formatVentaItem($cuenta));

        // â”€â”€ Consulta para agrupar por SKU (Modelo) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $skusMercadoLibre = \App\Models\Ventas\DetalleVenta::query()
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->join('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->selectRaw("Producto.modelo as sku,
                         SUM(DetalleVenta.cantidad) as total_unidades,
                         SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                         SUM((($costoVentaExpr) + ($comisionMercadoLibreExpr)) * DetalleVenta.cantidad + ($costosComponentesSub)) as costos,
                         SUM(($comisionMercadoLibreExpr) * DetalleVenta.cantidad) as comision_mercadolibre")
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->where('DetalleVenta.estado', '!=', 'DEVUELTO')
            ->whereRaw("(UPPER(Venta.canal) = 'MERCADO LIBRE' OR UPPER(Venta.canal) = 'MERCADOLIBRE')")
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->groupBy('Producto.modelo')
            ->orderByDesc('ingresos')
            ->get()
            ->map(fn($sku) => $this->gananciaQueryService->formatVentaItem($sku));

        $skusMayorRotacion = $skusMercadoLibre->sortByDesc('total_unidades')->take(5)->values();
        $skusMayorRentabilidad = $skusMercadoLibre->sortByDesc('ganancia')->take(5)->values();

        // â”€â”€ Consulta para tendencia de ventas por mes (GrÃ¡fico) â”€â”€â”€â”€â”€â”€â”€â”€
        $ventasMesRaw = Venta::query()
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->selectRaw('DATE(Venta.fechaVenta) as fecha, SUM(DetalleVenta.cantidad) as total_unidades, SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as total_monto')
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->where('DetalleVenta.estado', '!=', 'DEVUELTO')
            ->whereRaw("(UPPER(Venta.canal) = 'MERCADO LIBRE' OR UPPER(Venta.canal) = 'MERCADOLIBRE')")
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

        return view('analytics.components.mercadolibre.index', [
            'user' => $userModel,
            'ventasMercadoLibre' => $ventasMercadoLibre,
            'cuentasMercadoLibre' => $cuentasMercadoLibre,
            'skusMercadoLibre' => $skusMercadoLibre,
            'skusMayorRotacion' => $skusMayorRotacion,
            'skusMayorRentabilidad' => $skusMayorRentabilidad,
            'ventasMes' => $ventasMes,
            'filtros' => compact('anio', 'mes') + $request->only('dia_inicio', 'dia_fin') + ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin],
        ]);
    }
}
