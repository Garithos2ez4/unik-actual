<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\HeaderServiceInterface;
use App\Services\CalculadoraServiceInterface;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\EgresoProducto;
use App\Models\Producto;

class AnalyticsController extends Controller
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

    public function index(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        if (!$this->validateAccess($userModel, 13)) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
            return redirect()->route('dashboard', ['user' => $userModel]);
        }

        $tc = $this->calculadoraService->getTasaCambio();
        [$fechaInicio, $fechaFin, $anio, $mes] = $this->resolveDateRange($request);

        // ── Helper Variables para la transición de sistema ────────
        $fechaTransicion = '2026-05-25';
        $ordenesIgnoradas = ['2026', '2026/SN', '2026-SN'];
        $subqueryTipoCambioEgreso = "(SELECT COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE(EgresoProducto.fechaCompra))) ASC LIMIT 1), $tc))";
        $precioPubExpr = "COALESCE(Publicacion.precioPublicacion, COALESCE(Producto.precioDolar, 0) * $subqueryTipoCambioEgreso * 1.20)";

        // ── 1. Tendencia de ventas (diarias) ──────────────────
        $qVentas1 = DetalleVenta::query()
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->selectRaw('DATE(Venta.fechaVenta) as fecha, DetalleVenta.cantidad as cantidad, (DetalleVenta.precioVenta * DetalleVenta.cantidad) as monto')
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->where('Venta.fechaVenta', '>=', $fechaTransicion);

        $qEgresos1 = EgresoProducto::query()
            ->join('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->selectRaw("DATE(EgresoProducto.fechaCompra) as fecha, 1 as cantidad, $precioPubExpr as monto")
            ->whereBetween('EgresoProducto.fechaCompra', [$fechaInicio, $fechaFin])
            ->where('EgresoProducto.fechaCompra', '<', $fechaTransicion)
            ->whereNotIn('EgresoProducto.numeroOrden', $ordenesIgnoradas);

        $ventasMesRaw = DB::query()
            ->fromSub($qVentas1->unionAll($qEgresos1), 'unioned')
            ->selectRaw('fecha, SUM(cantidad) as total_unidades, SUM(monto) as total_monto')
            ->groupBy('fecha')
            ->orderBy('fecha', 'asc')
            ->get();

        // Rellenar días vacíos
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

        // ── 2. Top SKUs más vendidos ──────────────────────────
        $qVentas2 = DetalleVenta::query()
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->join('Publicacion', 'DetalleVenta.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->select('Publicacion.sku', 'Publicacion.titulo', 'DetalleVenta.cantidad as cantidad')
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->where('Venta.fechaVenta', '>=', $fechaTransicion)
            ->whereNotNull('DetalleVenta.idPublicacion');

        $qEgresos2 = EgresoProducto::query()
            ->join('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->selectRaw('Publicacion.sku, Publicacion.titulo, 1 as cantidad')
            ->whereBetween('EgresoProducto.fechaCompra', [$fechaInicio, $fechaFin])
            ->where('EgresoProducto.fechaCompra', '<', $fechaTransicion)
            ->whereNotIn('EgresoProducto.numeroOrden', $ordenesIgnoradas);

        $skusMostSoldMonth = DB::query()
            ->fromSub($qVentas2->unionAll($qEgresos2), 'unioned')
            ->selectRaw('sku, titulo, SUM(cantidad) as total_ventas')
            ->groupBy('sku', 'titulo')
            ->orderByDesc('total_ventas')
            ->limit(5)
            ->get();

        // ── 3. Métricas por plataforma ────────────────────────
        $qVentas3 = DetalleVenta::query()
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->leftJoin('Publicacion', 'DetalleVenta.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->leftJoin('CuentasPlataforma', 'Publicacion.idCuentaPlataforma', '=', 'CuentasPlataforma.idCuentaPlataforma')
            ->leftJoin('Plataforma', 'CuentasPlataforma.idPlataforma', '=', 'Plataforma.idPlataforma')
            ->selectRaw('COALESCE(Plataforma.nombrePlataforma, "VENTA DIRECTA") as plataforma, DetalleVenta.cantidad as cantidad, (DetalleVenta.precioVenta * DetalleVenta.cantidad) as monto')
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->where('Venta.fechaVenta', '>=', $fechaTransicion);

        $qEgresos3 = EgresoProducto::query()
            ->leftJoin('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->leftJoin('CuentasPlataforma', 'Publicacion.idCuentaPlataforma', '=', 'CuentasPlataforma.idCuentaPlataforma')
            ->leftJoin('Plataforma', 'CuentasPlataforma.idPlataforma', '=', 'Plataforma.idPlataforma')
            ->join('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->selectRaw("COALESCE(Plataforma.nombrePlataforma, 'VENTA DIRECTA') as plataforma, 1 as cantidad, $precioPubExpr as monto")
            ->whereBetween('EgresoProducto.fechaCompra', [$fechaInicio, $fechaFin])
            ->where('EgresoProducto.fechaCompra', '<', $fechaTransicion)
            ->whereNotIn('EgresoProducto.numeroOrden', $ordenesIgnoradas);

        $metricasPlataformas = DB::query()
            ->fromSub($qVentas3->unionAll($qEgresos3), 'unioned')
            ->selectRaw('plataforma, SUM(cantidad) as total_pedidos, SUM(monto) as total_monto')
            ->groupBy('plataforma')
            ->orderByDesc('total_monto')
            ->get();

        // ── 4. Top productos por ingreso (monto S/) ──────────
        $qVentas4 = DetalleVenta::query()
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->join('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->selectRaw('Producto.idProducto, Producto.nombreProducto, Producto.modelo, DetalleVenta.cantidad as cantidad, (DetalleVenta.precioVenta * DetalleVenta.cantidad) as monto')
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->where('Venta.fechaVenta', '>=', $fechaTransicion);

        $qEgresos4 = EgresoProducto::query()
            ->join('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->selectRaw("Producto.idProducto, Producto.nombreProducto, Producto.modelo, 1 as cantidad, $precioPubExpr as monto")
            ->whereBetween('EgresoProducto.fechaCompra', [$fechaInicio, $fechaFin])
            ->where('EgresoProducto.fechaCompra', '<', $fechaTransicion)
            ->whereNotIn('EgresoProducto.numeroOrden', $ordenesIgnoradas);

        $productosMostRevenueMonth = DB::query()
            ->fromSub($qVentas4->unionAll($qEgresos4), 'unioned')
            ->selectRaw('idProducto, nombreProducto, modelo, SUM(cantidad) as total_unidades, SUM(monto) as total_ingreso')
            ->groupBy('idProducto', 'nombreProducto', 'modelo')
            ->orderByDesc('total_ingreso')
            ->limit(5)
            ->get();

        // ── 5. Top productos por cantidad vendida ─────────────
        $qVentas5 = DetalleVenta::query()
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->join('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->select('Producto.idProducto', 'Producto.nombreProducto', 'Producto.modelo', 'DetalleVenta.cantidad as cantidad')
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->where('Venta.fechaVenta', '>=', $fechaTransicion);

        $qEgresos5 = EgresoProducto::query()
            ->join('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->selectRaw('Producto.idProducto, Producto.nombreProducto, Producto.modelo, 1 as cantidad')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('devoluciones')
                    ->whereColumn('devoluciones.idEgreso', 'EgresoProducto.idEgreso');
            })
            ->whereBetween('EgresoProducto.fechaCompra', [$fechaInicio, $fechaFin])
            ->where('EgresoProducto.fechaCompra', '<', $fechaTransicion)
            ->whereNotIn('EgresoProducto.numeroOrden', $ordenesIgnoradas);

        $productosMostSoldMonth = DB::query()
            ->fromSub($qVentas5->unionAll($qEgresos5), 'unioned')
            ->selectRaw('idProducto, nombreProducto, modelo, SUM(cantidad) as total_unidades')
            ->groupBy('idProducto', 'nombreProducto', 'modelo')
            ->orderByDesc('total_unidades')
            ->limit(5)
            ->get();

        // ── 6. Top productos con fallas (solo RegistroProducto)
        $productosConFallas = Producto::query()
            ->join('DetalleComprobante', 'Producto.idProducto', '=', 'DetalleComprobante.idProducto')
            ->join('RegistroProducto', 'DetalleComprobante.idDetalleComprobante', '=', 'RegistroProducto.idDetalleComprobante')
            ->leftJoin('devoluciones', 'RegistroProducto.idRegistro', '=', 'devoluciones.idRegistro')
            ->selectRaw('Producto.nombreProducto, Producto.modelo, COUNT(DISTINCT RegistroProducto.idRegistro) as total_fallas')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereIn('RegistroProducto.estado', ['DEFECTUOSO', 'GARANTIA'])
                        ->whereNotNull('RegistroProducto.observacion')
                        ->where('RegistroProducto.observacion', '!=', '');
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('devoluciones.idDevolucion')
                        ->whereNotNull('devoluciones.motivo')
                        ->where('devoluciones.motivo', '!=', '');
                });
            })
            ->groupBy('Producto.idProducto', 'Producto.nombreProducto', 'Producto.modelo')
            ->orderByDesc('total_fallas')
            ->limit(10)
            ->get();

        // ── 7. Top mejores meses históricos (Global) ──────────
        $qVentas7 = Venta::query()
            ->selectRaw("DATE_FORMAT(fechaVenta, '%Y-%m') as mes_raw, DATE_FORMAT(fechaVenta, '%M %Y') as mes_nombre, totalVenta as monto")
            ->where('fechaVenta', '>=', $fechaTransicion);

        $qEgresos7 = EgresoProducto::query()
            ->join('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->selectRaw("DATE_FORMAT(EgresoProducto.fechaCompra, '%Y-%m') as mes_raw, DATE_FORMAT(EgresoProducto.fechaCompra, '%M %Y') as mes_nombre, $precioPubExpr as monto")
            ->whereNotIn('EgresoProducto.numeroOrden', $ordenesIgnoradas)
            ->where('EgresoProducto.fechaCompra', '<', $fechaTransicion);

        $topBestMonths = DB::query()
            ->fromSub($qVentas7->unionAll($qEgresos7), 'unioned')
            ->selectRaw('mes_raw, mes_nombre, SUM(monto) as total_monto')
            ->groupBy('mes_raw', 'mes_nombre')
            ->orderByDesc('total_monto')
            ->limit(3)
            ->get();

        // ── 8. Cálculos de Costos y Márgenes ──────────────────
        $subqueryTipoCambioCosto = "(SELECT COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE(c_inner.fechaRegistro))) ASC LIMIT 1), $tc))";

        $costoVentaExpr = "COALESCE(
            (SELECT CASE WHEN c_inner.moneda = 'DOLAR' THEN dc_inner.precioUnitario * $subqueryTipoCambioCosto ELSE dc_inner.precioUnitario END
             FROM EgresoProducto ep_inner
             INNER JOIN RegistroProducto rp_inner ON rp_inner.idRegistro = ep_inner.idRegistro
             INNER JOIN DetalleComprobante dc_inner ON dc_inner.idDetalleComprobante = rp_inner.idDetalleComprobante
             INNER JOIN Comprobante c_inner ON c_inner.idComprobante = dc_inner.idComprobante
             WHERE ep_inner.idEgreso = DetalleVenta.idEgreso AND dc_inner.precioUnitario > 1
             LIMIT 1),
            COALESCE(Producto.precioDolar, 0) * $tc * 1.18
        )";

        $comisionFalabellaVenta = "CASE WHEN UPPER(Venta.canal) = 'FALABELLA' THEN 
                (CASE WHEN GrupoProducto.idCategoria IN (1, 3) OR GrupoProducto.idGrupoProducto IN (10, 40, 41, 42, 43) THEN 10.90 ELSE 3.90 END)
                + (DetalleVenta.precioVenta * CASE WHEN GrupoProducto.idGrupoProducto = 10 THEN 0.08 ELSE 0.10 END)
            ELSE 0 END";

        $costosComponentesSub = "COALESCE((SELECT SUM(
            COALESCE(
                (SELECT CASE WHEN c_inner.moneda = 'DOLAR' THEN dc_inner.precioUnitario * $subqueryTipoCambioCosto ELSE dc_inner.precioUnitario END
                 FROM EgresoProducto ep_inner
                 INNER JOIN RegistroProducto rp_inner ON rp_inner.idRegistro = ep_inner.idRegistro
                 INNER JOIN DetalleComprobante dc_inner ON dc_inner.idDetalleComprobante = rp_inner.idDetalleComprobante
                 INNER JOIN Comprobante c_inner ON c_inner.idComprobante = dc_inner.idComprobante
                 WHERE ep_inner.idEgreso = dv_comp.idEgreso AND dc_inner.precioUnitario > 1
                 LIMIT 1),
                COALESCE(p_comp.precioDolar, 0) * $tc * 1.18
            ) * dv_comp.cantidad
        )
        FROM DetalleVenta dv_comp
        LEFT JOIN Producto p_comp ON dv_comp.idProducto = p_comp.idProducto
        WHERE dv_comp.idVenta = DetalleVenta.idVenta
        AND dv_comp.precioVenta <= 0.01) / 
        GREATEST((SELECT COUNT(*) FROM DetalleVenta dv_main WHERE dv_main.idVenta = DetalleVenta.idVenta AND dv_main.precioVenta > 0.01), 1)
        , 0)";

        $qVentas8 = DetalleVenta::query()
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->join('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->selectRaw("Producto.idProducto, Producto.nombreProducto, Producto.modelo, 
                         (DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos, 
                         ((($costoVentaExpr) + ($comisionFalabellaVenta)) * DetalleVenta.cantidad) + ($costosComponentesSub) as costos")
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('DetalleVenta.precioVenta', '>', 0.01)
            ->where('Venta.fechaVenta', '>=', $fechaTransicion);

        $comisionFalabellaEgreso = "CASE WHEN UPPER(Plataforma.nombrePlataforma) LIKE '%FALABELLA%' THEN 
                (CASE WHEN GrupoProducto.idCategoria IN (1, 3) OR GrupoProducto.idGrupoProducto IN (10, 40, 41, 42, 43) THEN 10.90 ELSE 3.90 END)
                + ($precioPubExpr * CASE WHEN GrupoProducto.idGrupoProducto = 10 THEN 0.08 ELSE 0.10 END)
            ELSE 0 END";

        $subqueryTipoCambioEgresoCosto = "(SELECT COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE(Comprobante.fechaRegistro))) ASC LIMIT 1), $tc))";

        $qEgresos8 = EgresoProducto::query()
            ->join('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Comprobante', 'Comprobante.idComprobante', '=', 'DetalleComprobante.idComprobante')
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->leftJoin('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->leftJoin('CuentasPlataforma', 'Publicacion.idCuentaPlataforma', '=', 'CuentasPlataforma.idCuentaPlataforma')
            ->leftJoin('Plataforma', 'CuentasPlataforma.idPlataforma', '=', 'Plataforma.idPlataforma')
            ->selectRaw("Producto.idProducto, Producto.nombreProducto, Producto.modelo, $precioPubExpr as ingresos,
                         (COALESCE(
                            NULLIF(CASE WHEN DetalleComprobante.precioUnitario > 1 THEN 
                                (CASE WHEN Comprobante.moneda = 'DOLAR' THEN DetalleComprobante.precioUnitario * $subqueryTipoCambioEgresoCosto ELSE DetalleComprobante.precioUnitario END) 
                            ELSE NULL END, NULL),
                            COALESCE(Producto.precioDolar, 0) * $tc * 1.18
                         ) + ($comisionFalabellaEgreso)) as costos")
            ->whereBetween('EgresoProducto.fechaCompra', [$fechaInicio, $fechaFin])
            ->where('EgresoProducto.fechaCompra', '<', $fechaTransicion)
            ->whereRaw("($precioPubExpr) > 0.01")
            ->whereNotIn('EgresoProducto.numeroOrden', $ordenesIgnoradas);

        $margenesProductos = DB::query()
            ->fromSub($qVentas8->unionAll($qEgresos8), 'unioned')
            ->selectRaw('idProducto, nombreProducto, modelo, SUM(ingresos) as total_ingresos, SUM(costos) as total_costos, (SUM(ingresos) - SUM(costos)) as ganancia_neta')
            ->groupBy('idProducto', 'nombreProducto', 'modelo')
            ->having('total_ingresos', '>', 0)
            ->get()
            ->map(function ($item) {
                $item->margen_porcentaje = $item->total_ingresos > 0 ? ($item->ganancia_neta / $item->total_ingresos) * 100 : 0;
                return $item;
            });

        $productosMasGanancia = $margenesProductos->sortByDesc('ganancia_neta')->take(5)->values();
        $productosMenosGanancia = $margenesProductos->sortBy('ganancia_neta')->take(5)->values();

        // ── 9. Top 5 Productos Más Enviados ───────────────────
        $topEnviados = \App\Models\EnvioProvinciaProducto::query()
            ->join('envio_provincias', 'envio_provincia_productos.idEnvioProvincia', '=', 'envio_provincias.idEnvioProvincia')
            ->join('Producto', 'envio_provincia_productos.idProducto', '=', 'Producto.idProducto')
            ->selectRaw('Producto.nombreProducto, Producto.modelo, SUM(envio_provincia_productos.cantidad) as total_enviado')
            ->whereBetween('envio_provincias.fecha_envio', [$fechaInicio, $fechaFin])
            ->groupBy('Producto.idProducto', 'Producto.nombreProducto', 'Producto.modelo')
            ->orderByDesc('total_enviado')
            ->limit(5)
            ->get();

        return view('analytics.index', [
            'user' => $userModel,
            'ventasMes' => $ventasMes,
            'productosConFallas' => $productosConFallas,
            'skusMostSoldMonth' => $skusMostSoldMonth,
            'metricasPlataformas' => $metricasPlataformas,
            'productosMostRevenueMonth' => $productosMostRevenueMonth,
            'productosMostSoldMonth' => $productosMostSoldMonth,
            'productosMasGanancia' => $productosMasGanancia,
            'productosMenosGanancia' => $productosMenosGanancia,
            'topBestMonths' => $topBestMonths,
            'topEnviados' => $topEnviados,
            'filtros' => compact('anio', 'mes') + $request->only('dia_inicio', 'dia_fin') + ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin],
        ]);
    }

    public function ripley(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        if (!$this->validateAccess($userModel, 13)) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
            return redirect()->route('dashboard', ['user' => $userModel]);
        }

        $tc = $this->calculadoraService->getTasaCambio();
        [$fechaInicio, $fechaFin, $anio, $mes] = $this->resolveDateRange($request);

        $subqueryTipoCambioCosto = "(SELECT COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE(c_inner.fechaRegistro))) ASC LIMIT 1), $tc))";

        $costoVentaExpr = "COALESCE(
            (SELECT CASE WHEN c_inner.moneda = 'DOLAR' THEN dc_inner.precioUnitario * $subqueryTipoCambioCosto ELSE dc_inner.precioUnitario END
             FROM EgresoProducto ep_inner
             INNER JOIN RegistroProducto rp_inner ON rp_inner.idRegistro = ep_inner.idRegistro
             INNER JOIN DetalleComprobante dc_inner ON dc_inner.idDetalleComprobante = rp_inner.idDetalleComprobante
             INNER JOIN Comprobante c_inner ON c_inner.idComprobante = dc_inner.idComprobante
             WHERE ep_inner.idEgreso = DetalleVenta.idEgreso AND dc_inner.precioUnitario > 1
             LIMIT 1),
            COALESCE(Producto.precioDolar, 0) * $tc * 1.18
        )";

        $comisionRipleyExpr = "0";

        $costosComponentesSub = "COALESCE((SELECT SUM(
            COALESCE(
                (SELECT CASE WHEN c_inner.moneda = 'DOLAR' THEN dc_inner.precioUnitario * $subqueryTipoCambioCosto ELSE dc_inner.precioUnitario END
                 FROM EgresoProducto ep_inner
                 INNER JOIN RegistroProducto rp_inner ON rp_inner.idRegistro = ep_inner.idRegistro
                 INNER JOIN DetalleComprobante dc_inner ON dc_inner.idDetalleComprobante = rp_inner.idDetalleComprobante
                 INNER JOIN Comprobante c_inner ON c_inner.idComprobante = dc_inner.idComprobante
                 WHERE ep_inner.idEgreso = dv_comp.idEgreso AND dc_inner.precioUnitario > 1
                 LIMIT 1),
                COALESCE(p_comp.precioDolar, 0) * $tc * 1.18
            ) * dv_comp.cantidad
        )
        FROM DetalleVenta dv_comp
        LEFT JOIN Producto p_comp ON dv_comp.idProducto = p_comp.idProducto
        WHERE dv_comp.idVenta = DetalleVenta.idVenta
        AND dv_comp.precioVenta <= 0.01) / 
        GREATEST((SELECT COUNT(*) FROM DetalleVenta dv_main WHERE dv_main.idVenta = DetalleVenta.idVenta AND dv_main.precioVenta > 0.01), 1)
        , 0)";

        // Usamos el Modelo Venta para iniciar la consulta
        $ventasRipley = Venta::query()
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->leftJoin('Usuario', 'Venta.idUser', '=', 'Usuario.idUser')
            ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->selectRaw("Venta.idVenta, Venta.fechaVenta, Venta.idUser, Usuario.user as nombre_usuario,
                         GROUP_CONCAT(Producto.modelo SEPARATOR ', ') as modelo,
                         SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                         SUM((($costoVentaExpr) + ($comisionRipleyExpr)) * DetalleVenta.cantidad + ($costosComponentesSub)) as costos,
                         SUM(($comisionRipleyExpr) * DetalleVenta.cantidad) as comision_ripley")
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->whereRaw("UPPER(Venta.canal) = 'RIPLEY'")
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->groupBy('Venta.idVenta', 'Venta.fechaVenta', 'Venta.idUser', 'Usuario.user')
            ->orderByDesc('Venta.fechaVenta')
            ->get()
            ->map(function ($venta) {
                $venta->ingresos = round($venta->ingresos, 2);
                $venta->costos   = round($venta->costos, 2);
                $venta->ganancia = round($venta->ingresos - $venta->costos, 2);
                $venta->comision_ripley = round($venta->comision_ripley, 2);
                $venta->margen = $venta->ingresos > 0 ? round(($venta->ganancia / $venta->ingresos) * 100, 2) : 0;
                return $venta;
            });

        // ── Consulta para tendencia de ventas por mes (Gráfico) ────────
        $ventasMesRaw = Venta::query()
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->selectRaw('DATE(Venta.fechaVenta) as fecha, SUM(DetalleVenta.cantidad) as total_unidades, SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as total_monto')
            ->where('DetalleVenta.precioVenta', '>', 0.01)
            ->whereRaw("UPPER(Venta.canal) = 'RIPLEY'")
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

        return view('analytics.components.ripley.index', [
            'user' => $userModel,
            'ventasRipley' => $ventasRipley,
            'ventasMes' => $ventasMes,
            'filtros' => compact('anio', 'mes') + $request->only('dia_inicio', 'dia_fin') + ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin],
        ]);
    }


    public function tienda(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        if (!$this->validateAccess($userModel, 13)) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
            return redirect()->route('dashboard', ['user' => $userModel]);
        }

        $tc = $this->calculadoraService->getTasaCambio();
        [$fechaInicio, $fechaFin, $anio, $mes] = $this->resolveDateRange($request);

        return view('analytics.components.tienda.index', [
            'user' => $userModel,
            'filtros' => compact('anio', 'mes') + $request->only('dia_inicio', 'dia_fin') + ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin],
        ]);
    }

    public function tiendaData(Request $request)
    {
        $tc = $this->calculadoraService->getTasaCambio();
        [$fechaInicio, $fechaFin, $anio, $mes] = $this->resolveDateRange($request);
        
        $cacheKey = "tienda_data_{$fechaInicio}_{$fechaFin}";

        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(5), function () use ($fechaInicio, $fechaFin, $tc) {
            $subqueryTipoCambioCosto = "(SELECT COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE(c_inner.fechaRegistro))) ASC LIMIT 1), $tc))";

            $costoVentaExpr = "COALESCE(
                (SELECT CASE WHEN c_inner.moneda = 'DOLAR' THEN dc_inner.precioUnitario * $subqueryTipoCambioCosto ELSE dc_inner.precioUnitario END
                 FROM EgresoProducto ep_inner
                 INNER JOIN RegistroProducto rp_inner ON rp_inner.idRegistro = ep_inner.idRegistro
                 INNER JOIN DetalleComprobante dc_inner ON dc_inner.idDetalleComprobante = rp_inner.idDetalleComprobante
                 INNER JOIN Comprobante c_inner ON c_inner.idComprobante = dc_inner.idComprobante
                 WHERE ep_inner.idEgreso = DetalleVenta.idEgreso AND dc_inner.precioUnitario > 1
                 LIMIT 1),
                COALESCE(Producto.precioDolar, 0) * $tc * 1.18
            )";

            $comisionTiendaExpr = "0";

            $costosComponentesSub = "COALESCE((SELECT SUM(
                COALESCE(
                    (SELECT CASE WHEN c_inner.moneda = 'DOLAR' THEN dc_inner.precioUnitario * $subqueryTipoCambioCosto ELSE dc_inner.precioUnitario END
                     FROM EgresoProducto ep_inner
                     INNER JOIN RegistroProducto rp_inner ON rp_inner.idRegistro = ep_inner.idRegistro
                     INNER JOIN DetalleComprobante dc_inner ON dc_inner.idDetalleComprobante = rp_inner.idDetalleComprobante
                     INNER JOIN Comprobante c_inner ON c_inner.idComprobante = dc_inner.idComprobante
                     WHERE ep_inner.idEgreso = dv_comp.idEgreso AND dc_inner.precioUnitario > 1
                     LIMIT 1),
                    COALESCE(p_comp.precioDolar, 0) * $tc * 1.18
                ) * dv_comp.cantidad
            )
            FROM DetalleVenta dv_comp
            LEFT JOIN Producto p_comp ON dv_comp.idProducto = p_comp.idProducto
            WHERE dv_comp.idVenta = DetalleVenta.idVenta
            AND dv_comp.precioVenta <= 0.01) / 
            GREATEST((SELECT COUNT(*) FROM DetalleVenta dv_main WHERE dv_main.idVenta = DetalleVenta.idVenta AND dv_main.precioVenta > 0.01), 1)
            , 0)";

            $ventasTienda = Venta::query()
                ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
                ->leftJoin('Usuario', 'Venta.idUser', '=', 'Usuario.idUser')
                ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
                ->selectRaw("Venta.idVenta, Venta.fechaVenta, Venta.idUser, Usuario.user as nombre_usuario,
                             GROUP_CONCAT(Producto.modelo SEPARATOR ', ') as modelo,
                             (SELECT GROUP_CONCAT(DISTINCT MetodoPago.nombreMetodo SEPARATOR ', ') FROM PagoVenta JOIN MetodoPago ON PagoVenta.idMetodoPago = MetodoPago.idMetodoPago WHERE PagoVenta.idVenta = Venta.idVenta) as metodos_pago,
                             SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                             SUM((($costoVentaExpr) + ($comisionTiendaExpr)) * DetalleVenta.cantidad + ($costosComponentesSub)) as costos,
                             0 as comision_tienda")
                ->where('DetalleVenta.precioVenta', '>', 0.01)
                ->whereRaw("UPPER(Venta.canal) = 'TIENDA'")
                ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
                ->groupBy('Venta.idVenta', 'Venta.fechaVenta', 'Venta.idUser', 'Usuario.user')
                ->orderByDesc('Venta.fechaVenta')
                ->get()
                ->map(function ($venta) {
                    $venta->ingresos = round($venta->ingresos, 2);
                    $venta->costos   = round($venta->costos, 2);
                    $venta->ganancia = round($venta->ingresos - $venta->costos, 2);
                    $venta->comision_tienda = 0;
                    $venta->margen = $venta->ingresos > 0 ? round(($venta->ganancia / $venta->ingresos) * 100, 2) : 0;
                    return $venta;
                });

            $pagosTienda = \App\Models\PagoVenta::query()
                ->join('Venta', 'PagoVenta.idVenta', '=', 'Venta.idVenta')
                ->join('MetodoPago', 'PagoVenta.idMetodoPago', '=', 'MetodoPago.idMetodoPago')
                ->selectRaw("MetodoPago.nombreMetodo as metodo_pago, SUM(PagoVenta.monto) as total_monto, COUNT(PagoVenta.idPagoVenta) as cantidad_transacciones")
                ->whereRaw("UPPER(Venta.canal) = 'TIENDA'")
                ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
                ->groupBy('MetodoPago.nombreMetodo')
                ->orderByDesc('total_monto')
                ->get();

            $detallePagosTienda = \App\Models\PagoVenta::query()
                ->join('Venta', 'PagoVenta.idVenta', '=', 'Venta.idVenta')
                ->join('MetodoPago', 'PagoVenta.idMetodoPago', '=', 'MetodoPago.idMetodoPago')
                ->leftJoin('Usuario', 'Venta.idUser', '=', 'Usuario.idUser')
                ->leftJoin('CuentasTransferencia', 'PagoVenta.idCuentaBancaria', '=', 'CuentasTransferencia.idCuentaBancaria')
                ->leftJoin('Banco', 'CuentasTransferencia.idBanco', '=', 'Banco.idBanco')
                ->selectRaw("CASE WHEN UPPER(MetodoPago.nombreMetodo) LIKE '%TRANSFERENCIA%' THEN COALESCE(Banco.nombreBanco, MetodoPago.nombreMetodo) ELSE MetodoPago.nombreMetodo END as metodo_banco, PagoVenta.idVenta, Venta.fechaVenta, PagoVenta.fechaPago, PagoVenta.monto, PagoVenta.nroOperacion, Usuario.user as vendedor")
                ->whereRaw("UPPER(Venta.canal) = 'TIENDA'")
                ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
                ->orderByRaw("CASE WHEN UPPER(MetodoPago.nombreMetodo) LIKE '%TRANSFERENCIA%' THEN COALESCE(Banco.nombreBanco, MetodoPago.nombreMetodo) ELSE MetodoPago.nombreMetodo END")
                ->orderByDesc('Venta.fechaVenta')
                ->get()
                ->groupBy('metodo_banco');

            $ventasMesRaw = Venta::query()
                ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
                ->selectRaw('DATE(Venta.fechaVenta) as fecha, SUM(DetalleVenta.cantidad) as total_unidades, SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as total_monto')
                ->where('DetalleVenta.precioVenta', '>', 0.01)
                ->whereRaw("UPPER(Venta.canal) = 'TIENDA'")
                ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
                ->groupBy(\Illuminate\Support\Facades\DB::raw('DATE(Venta.fechaVenta)'))
                ->orderBy('fecha', 'asc')
                ->get();

            // ── Consulta para agrupar por SKU (Modelo) ───────────────────
            $skusTienda = \App\Models\DetalleVenta::query()
                ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
                ->join('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
                ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
                ->selectRaw("Producto.modelo as sku,
                             SUM(DetalleVenta.cantidad) as total_unidades,
                             SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                             SUM((($costoVentaExpr) + ($comisionTiendaExpr)) * DetalleVenta.cantidad + ($costosComponentesSub)) as costos,
                             SUM(($comisionTiendaExpr) * DetalleVenta.cantidad) as comision_tienda")
                ->where('DetalleVenta.precioVenta', '>', 0.01)
                ->whereRaw("UPPER(Venta.canal) = 'TIENDA'")
                ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
                ->groupBy('Producto.modelo')
                ->orderByDesc('ingresos')
                ->get()
                ->map(function ($sku) {
                    $sku->ingresos = round($sku->ingresos, 2);
                    $sku->costos = round($sku->costos, 2);
                    $sku->ganancia = round($sku->ingresos - $sku->costos, 2);
                    $sku->margen = $sku->ingresos > 0 ? round(($sku->ganancia / $sku->ingresos) * 100, 2) : 0;
                    return $sku;
                });

            $skusMayorRotacion = $skusTienda->sortByDesc('total_unidades')->take(10)->values();
            $skusMayorRentabilidad = $skusTienda->sortByDesc('ganancia')->take(10)->values();

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

            return compact('ventasTienda', 'pagosTienda', 'detallePagosTienda', 'ventasMes', 'skusTienda', 'skusMayorRotacion', 'skusMayorRentabilidad');
        });

        return view('analytics.components.tienda.components.tienda_venta_data', $data + [
            'filtros' => compact('anio', 'mes') + $request->only('dia_inicio', 'dia_fin') + ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin],
        ]);
    }
}
