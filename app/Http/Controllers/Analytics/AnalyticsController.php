<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\HeaderServiceInterface;
use App\Services\CalculadoraServiceInterface;
use App\Services\GananciaQueryService;
use App\Services\EnvioProvinciaServiceInterface;
use App\Models\Ventas\Venta;
use App\Models\Ventas\DetalleVenta;
use App\Models\Inventario\EgresoProducto;
use App\Models\Catalogo\Producto;

class AnalyticsController extends Controller
{
    protected $headerService;
    protected $calculadoraService;
    protected $gananciaQueryService;
    protected $envioProvinciaService;

    public function __construct(
        HeaderServiceInterface $headerService,
        CalculadoraServiceInterface $calculadoraService,
        GananciaQueryService $gananciaQueryService,
        EnvioProvinciaServiceInterface $envioProvinciaService
    ) {
        $this->headerService = $headerService;
        $this->calculadoraService = $calculadoraService;
        $this->gananciaQueryService = $gananciaQueryService;
        $this->envioProvinciaService = $envioProvinciaService;
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
    private function applyInventarioFilter($query, $isVenta = true)
    {
        if ($isVenta) {
            // Excluir ventas devueltas/anuladas para que no inflen las mÃ©tricas
            $query->where('DetalleVenta.estado', '!=', 'DEVUELTO');
            return $query;
        } else {
            // Para compras/gastos, sÃ­ filtramos los comprobantes de INVENTARIO para no inflar los costos
            $query->whereNotExists(function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('RegistroProducto')
                    ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
                    ->join('Comprobante', 'DetalleComprobante.idComprobante', '=', 'Comprobante.idComprobante')
                    ->whereColumn('RegistroProducto.idRegistro', 'EgresoProducto.idRegistro')
                    ->where('Comprobante.numeroComprobante', 'LIKE', 'INVENTARIO%');
            });
        }
        return $query;
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

        // â”€â”€ Helper Variables para la transiciÃ³n de sistema â”€â”€â”€â”€â”€â”€â”€â”€
        $fechaTransicion = '2026-05-25';
        $ordenesIgnoradas = ['2026', '2026/SN', '2026-SN'];
        $subqueryTipoCambioEgreso = "(SELECT COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE(EgresoProducto.fechaCompra))) ASC LIMIT 1), $tc))";
        $precioPubExpr = "COALESCE(Publicacion.precioPublicacion, COALESCE(Producto.precioDolar, 0) * $subqueryTipoCambioEgreso * 1.20)";

        // â”€â”€ 1. Tendencia de ventas (diarias) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $qVentas1 = DetalleVenta::query()
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->selectRaw('DATE(Venta.fechaVenta) as fecha, DetalleVenta.cantidad as cantidad, (DetalleVenta.precioVenta * DetalleVenta.cantidad) as monto')
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->where(function ($q) {
                $this->applyInventarioFilter($q, true);
            })
            ->where('Venta.fechaVenta', '>=', $fechaTransicion);

        $qEgresos1 = EgresoProducto::query()
            ->join('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->selectRaw("DATE(EgresoProducto.fechaCompra) as fecha, 1 as cantidad, $precioPubExpr as monto")
            ->whereBetween('EgresoProducto.fechaCompra', [$fechaInicio, $fechaFin])
            ->where('EgresoProducto.fechaCompra', '<', $fechaTransicion)
            ->whereNotIn('EgresoProducto.numeroOrden', $ordenesIgnoradas)
            ->where(function ($q) {
                $this->applyInventarioFilter($q, false);
            });

        $ventasMesRaw = DB::query()
            ->fromSub($qVentas1->unionAll($qEgresos1), 'unioned')
            ->selectRaw('fecha, SUM(cantidad) as total_unidades, SUM(monto) as total_monto')
            ->groupBy('fecha')
            ->orderBy('fecha', 'asc')
            ->get();

        // Rellenar dÃ­as vacÃ­os
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

        // â”€â”€ 2. Top SKUs mÃ¡s vendidos â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $qVentas2 = DetalleVenta::query()
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->join('Publicacion', 'DetalleVenta.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->select('Publicacion.sku', 'Publicacion.titulo', 'DetalleVenta.cantidad as cantidad')
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->where(function ($q) {
                $this->applyInventarioFilter($q, true);
            })
            ->where('Venta.fechaVenta', '>=', $fechaTransicion)
            ->whereNotNull('DetalleVenta.idPublicacion');

        $qEgresos2 = EgresoProducto::query()
            ->join('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->selectRaw('Publicacion.sku, Publicacion.titulo, 1 as cantidad')
            ->whereBetween('EgresoProducto.fechaCompra', [$fechaInicio, $fechaFin])
            ->where('EgresoProducto.fechaCompra', '<', $fechaTransicion)
            ->whereNotIn('EgresoProducto.numeroOrden', $ordenesIgnoradas)
            ->where(function ($q) {
                $this->applyInventarioFilter($q, false);
            });

        $skusMostSoldMonth = DB::query()
            ->fromSub($qVentas2->unionAll($qEgresos2), 'unioned')
            ->selectRaw('sku, titulo, SUM(cantidad) as total_ventas')
            ->groupBy('sku', 'titulo')
            ->orderByDesc('total_ventas')
            ->limit(5)
            ->get();

        // â”€â”€ 3. MÃ©tricas por plataforma â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $qVentas3 = DetalleVenta::query()
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->leftJoin('Publicacion', 'DetalleVenta.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->leftJoin('CuentasPlataforma', 'Publicacion.idCuentaPlataforma', '=', 'CuentasPlataforma.idCuentaPlataforma')
            ->leftJoin('Plataforma', 'CuentasPlataforma.idPlataforma', '=', 'Plataforma.idPlataforma')
            ->selectRaw('COALESCE(Plataforma.nombrePlataforma, "VENTA DIRECTA") as plataforma, DetalleVenta.cantidad as cantidad, (DetalleVenta.precioVenta * DetalleVenta.cantidad) as monto')
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->where(function ($q) {
                $this->applyInventarioFilter($q, true);
            })
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
            ->whereNotIn('EgresoProducto.numeroOrden', $ordenesIgnoradas)
            ->where(function ($q) {
                $this->applyInventarioFilter($q, false);
            });

        $metricasPlataformas = DB::query()
            ->fromSub($qVentas3->unionAll($qEgresos3), 'unioned')
            ->selectRaw('plataforma, SUM(cantidad) as total_pedidos, SUM(monto) as total_monto')
            ->groupBy('plataforma')
            ->orderByDesc('total_monto')
            ->get();

        // â”€â”€ 4. Top productos por ingreso (monto S/) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $qVentas4 = DetalleVenta::query()
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->join('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->selectRaw('Producto.idProducto, Producto.nombreProducto, Producto.modelo, DetalleVenta.cantidad as cantidad, (DetalleVenta.precioVenta * DetalleVenta.cantidad) as monto')
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->where(function ($q) {
                $this->applyInventarioFilter($q, true);
            })
            ->where('Venta.fechaVenta', '>=', $fechaTransicion);

        $qEgresos4 = EgresoProducto::query()
            ->join('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->selectRaw("Producto.idProducto, Producto.nombreProducto, Producto.modelo, 1 as cantidad, $precioPubExpr as monto")
            ->whereBetween('EgresoProducto.fechaCompra', [$fechaInicio, $fechaFin])
            ->where('EgresoProducto.fechaCompra', '<', $fechaTransicion)
            ->whereNotIn('EgresoProducto.numeroOrden', $ordenesIgnoradas)
            ->where(function ($q) {
                $this->applyInventarioFilter($q, false);
            });

        $productosMostRevenueMonth = DB::query()
            ->fromSub($qVentas4->unionAll($qEgresos4), 'unioned')
            ->selectRaw('idProducto, nombreProducto, modelo, SUM(cantidad) as total_unidades, SUM(monto) as total_ingreso')
            ->groupBy('idProducto', 'nombreProducto', 'modelo')
            ->orderByDesc('total_ingreso')
            ->limit(5)
            ->get();

        // â”€â”€ 5. Top productos por cantidad vendida â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $qVentas5 = DetalleVenta::query()
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->join('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->select('Producto.idProducto', 'Producto.nombreProducto', 'Producto.modelo', 'DetalleVenta.cantidad as cantidad')
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->where(function ($q) {
                $this->applyInventarioFilter($q, true);
            })
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
            ->whereNotIn('EgresoProducto.numeroOrden', $ordenesIgnoradas)
            ->where(function ($q) {
                $this->applyInventarioFilter($q, false);
            });

        $productosMostSoldMonth = DB::query()
            ->fromSub($qVentas5->unionAll($qEgresos5), 'unioned')
            ->selectRaw('idProducto, nombreProducto, modelo, SUM(cantidad) as total_unidades')
            ->groupBy('idProducto', 'nombreProducto', 'modelo')
            ->orderByDesc('total_unidades')
            ->limit(5)
            ->get();

        // â”€â”€ 6. Top productos con fallas (solo RegistroProducto)
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

        // â”€â”€ 7. Top mejores meses histÃ³ricos (Global) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

        // â”€â”€ 8. CÃ¡lculos de Costos y MÃ¡rgenes â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $exprs = $this->gananciaQueryService->getSqlExpressions($tc);
        extract($exprs);

        $comisionSvc = app(\App\Services\ComisionPlataformaService::class);

        $qVentas8 = DetalleVenta::query()
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->join('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->selectRaw("Producto.idProducto, Producto.nombreProducto, Producto.modelo, 
                         (DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos, 
                         ((($costoVentaExpr) + ($comisionFalabellaExpr)) * DetalleVenta.cantidad) + ($costosComponentesSub) as costos,
                         DetalleVenta.cantidad as cantidad_vendida")
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('DetalleVenta.precioVenta', '>', 0.10)
            ->where(function ($q) {
                $this->applyInventarioFilter($q, true);
            })
            ->where('Venta.fechaVenta', '>=', $fechaTransicion);

        $falabellaExprEgreso = $comisionSvc->getComisionExpr('FALABELLA', 'GrupoProducto.idCategoria', 'GrupoProducto.idGrupoProducto', $precioPubExpr);
        $comisionFalabellaEgreso = "CASE WHEN UPPER(Plataforma.nombrePlataforma) LIKE '%FALABELLA%' THEN {$falabellaExprEgreso} ELSE 0 END";

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
                            NULLIF(CASE WHEN RegistroProducto.es_herramienta = 1 THEN 0
                                         WHEN Comprobante.numeroComprobante LIKE '%INVENTARIO%' THEN NULL
                                         WHEN DetalleComprobante.precioUnitario > 0 OR Producto.idGrupo IN ($gruposCostoBajo) THEN 
                                (CASE WHEN Comprobante.moneda = 'DOLAR' THEN DetalleComprobante.precioUnitario * $subqueryTipoCambioEgresoCosto ELSE DetalleComprobante.precioUnitario END) 
                            ELSE NULL END, NULL),
                            (SELECT CASE WHEN c2.moneda = 'DOLAR' THEN dc2.precioUnitario * COALESCE((SELECT hs.tasa_cambio FROM historial_tipo_cambio hs ORDER BY ABS(DATEDIFF(hs.fecha, DATE(c2.fechaRegistro))) ASC LIMIT 1), $tc) ELSE dc2.precioUnitario END
                             FROM DetalleComprobante dc2
                             INNER JOIN Comprobante c2 ON c2.idComprobante = dc2.idComprobante
                             WHERE dc2.idProducto = Producto.idProducto
                               AND dc2.precioUnitario > 0
                               AND c2.numeroComprobante NOT LIKE '%INVENTARIO%'
                             ORDER BY dc2.idDetalleComprobante DESC
                             LIMIT 1),
                            CASE WHEN RegistroProducto.es_herramienta = 1 THEN 0 ELSE COALESCE(Producto.precioDolar, 0) * $tc * 1.18 END
                         ) + ($comisionFalabellaEgreso)) as costos,
                         1 as cantidad_vendida")
            ->whereBetween('EgresoProducto.fechaCompra', [$fechaInicio, $fechaFin])
            ->where('EgresoProducto.fechaCompra', '<', $fechaTransicion)
            ->where(function ($q) {
                $this->applyInventarioFilter($q, false);
            })
            ->whereNotIn('EgresoProducto.numeroOrden', $ordenesIgnoradas);

        $margenesProductos = DB::query()
            ->fromSub($qVentas8->unionAll($qEgresos8), 'unioned')
            ->selectRaw('idProducto, nombreProducto, modelo, SUM(ingresos) as total_ingresos, SUM(costos) as total_costos, (SUM(ingresos) - SUM(costos)) as ganancia_neta, SUM(cantidad_vendida) as total_cantidad')
            ->groupBy('idProducto', 'nombreProducto', 'modelo')
            ->having('total_ingresos', '>', 0)
            ->get()
            ->map(function ($item) {
                $item->margen_porcentaje = $item->total_ingresos > 0 ? ($item->ganancia_neta / $item->total_ingresos) * 100 : 0;
                $item->ganancia_neta_unitaria = $item->total_cantidad > 0 ? ($item->ganancia_neta / $item->total_cantidad) : 0;
                $item->margen_porcentaje_unitario = $item->total_cantidad > 0 && ($item->total_ingresos / $item->total_cantidad) > 0 ? ($item->ganancia_neta_unitaria / ($item->total_ingresos / $item->total_cantidad)) * 100 : 0;
                return $item;
            });

        $productosMasGanancia = $margenesProductos->sortByDesc('ganancia_neta')->take(5)->values();
        $productosMenosGanancia = $margenesProductos->sortBy('ganancia_neta')->take(5)->values();
        $productosMasGananciaUnitaria = $margenesProductos->sortByDesc('ganancia_neta_unitaria')->take(5)->values();
        $productosMenosGananciaUnitaria = $margenesProductos->sortBy('ganancia_neta_unitaria')->take(5)->values();

        // â”€â”€ 9. Top 5 Productos MÃ¡s Enviados â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $topEnviados = \App\Models\Envios\EnvioProvinciaProducto::query()
            ->join('envio_provincias', 'envio_provincia_productos.idEnvioProvincia', '=', 'envio_provincias.idEnvioProvincia')
            ->join('Producto', 'envio_provincia_productos.idProducto', '=', 'Producto.idProducto')
            ->selectRaw('Producto.nombreProducto, Producto.modelo, SUM(envio_provincia_productos.cantidad) as total_enviado')
            ->whereBetween('envio_provincias.fecha_envio', [$fechaInicio, $fechaFin])
            ->groupBy('Producto.idProducto', 'Producto.nombreProducto', 'Producto.modelo')
            ->orderByDesc('total_enviado')
            ->limit(5)
            ->get();

        // â”€â”€ 10. Top 5 Provincias MÃ¡s Solicitadas â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $topProvincias = $this->envioProvinciaService->getTopProvincias($fechaInicio, $fechaFin, 5);

        // â”€â”€ 11. Top 5 EnvÃ­os por Monto â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $topEnviosPorMonto = \App\Models\Ventas\Venta::query()
            ->join('Cliente', 'Venta.idCliente', '=', 'Cliente.idCliente')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('envio_provincias')
                    ->whereColumn('envio_provincias.idCliente', 'Venta.idCliente')
                    ->whereColumn(DB::raw('DATE(envio_provincias.fecha_envio)'), DB::raw('DATE(Venta.fechaVenta)'));
            })
            ->selectRaw('MAX(Venta.idVenta) as idEnvioProvincia, MAX(DATE(Venta.fechaVenta)) as fecha_envio, Cliente.numeroDocumento, Cliente.nombre, Cliente.apellidoPaterno, SUM(Venta.totalVenta) as monto_total')
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->groupBy('Cliente.numeroDocumento', 'Cliente.nombre', 'Cliente.apellidoPaterno')
            ->orderByDesc('monto_total')
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
            'productosMasGananciaUnitaria' => $productosMasGananciaUnitaria,
            'productosMenosGananciaUnitaria' => $productosMenosGananciaUnitaria,
            'topBestMonths' => $topBestMonths,
            'topEnviados' => $topEnviados,
            'topProvincias' => $topProvincias,
            'topEnviosPorMonto' => $topEnviosPorMonto,
            'filtros' => compact('anio', 'mes') + $request->only('dia_inicio', 'dia_fin') + ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin],
        ]);
    }



    public function tienda(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        if (!$this->validateAccess($userModel, 13)) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaÃ±a', 'warning', 'btn-danger');
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

        $version = \Illuminate\Support\Facades\Cache::get('analytics_tienda_version', 1);
        $cacheKey = "tienda_data_{$fechaInicio}_{$fechaFin}_v{$version}";

        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(30), function () use ($fechaInicio, $fechaFin, $tc) {
            $gruposCostoBajo = $this->calculadoraService->getGruposCostoExcepcion();
            $exprs = $this->gananciaQueryService->getSqlExpressions($tc);
            extract($exprs);

            $comisionTiendaExpr = "0";

            $ventasTienda = Venta::query()
                ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
                ->leftJoin('Usuario', 'Venta.idUser', '=', 'Usuario.idUser')
                ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
                ->selectRaw("Venta.idVenta, Venta.fechaVenta, Venta.idUser, Usuario.user as nombre_usuario,
                             GROUP_CONCAT(Producto.modelo SEPARATOR ', ') as modelo,
                             (SELECT GROUP_CONCAT(DISTINCT MetodoPago.nombreMetodo SEPARATOR ', ') FROM PagoVenta JOIN MetodoPago ON PagoVenta.idMetodoPago = MetodoPago.idMetodoPago WHERE PagoVenta.idVenta = Venta.idVenta) as metodos_pago,
                             SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                             SUM(((($costoVentaExpr) + ($comisionTiendaExpr)) * DetalleVenta.cantidad + ($costosComponentesSub))) as costos,
                             0 as comision_tienda")
                ->where('DetalleVenta.precioVenta', '>', 0.10)
                ->where('DetalleVenta.estado', '!=', 'DEVUELTO')
                ->whereRaw("UPPER(Venta.canal) = 'TIENDA'")
                ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
                ->groupBy('Venta.idVenta', 'Venta.fechaVenta', 'Venta.idUser', 'Usuario.user')
                ->orderByDesc('Venta.fechaVenta')
                ->get()
                ->map(fn($venta) => $this->gananciaQueryService->formatVentaItem($venta, true));

            $pagosTienda = \App\Models\Ventas\PagoVenta::query()
                ->join('Venta', 'PagoVenta.idVenta', '=', 'Venta.idVenta')
                ->join('MetodoPago', 'PagoVenta.idMetodoPago', '=', 'MetodoPago.idMetodoPago')
                ->selectRaw("MetodoPago.nombreMetodo as metodo_pago, SUM(PagoVenta.monto) as total_monto, COUNT(PagoVenta.idPagoVenta) as cantidad_transacciones")
                ->whereRaw("UPPER(Venta.canal) = 'TIENDA'")
                ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
                ->whereExists(function ($query) {
                    $query->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('DetalleVenta')
                        ->whereColumn('DetalleVenta.idVenta', 'Venta.idVenta')
                        ->where('DetalleVenta.precioVenta', '>', 0.10)
                        ->where('DetalleVenta.estado', '!=', 'DEVUELTO');
                })
                ->groupBy('MetodoPago.nombreMetodo')
                ->orderByDesc('total_monto')
                ->get();

            $detallePagosTienda = \App\Models\Ventas\PagoVenta::query()
                ->join('Venta', 'PagoVenta.idVenta', '=', 'Venta.idVenta')
                ->join('MetodoPago', 'PagoVenta.idMetodoPago', '=', 'MetodoPago.idMetodoPago')
                ->leftJoin('Usuario', 'Venta.idUser', '=', 'Usuario.idUser')
                ->leftJoin('CuentasTransferencia', 'PagoVenta.idCuentaBancaria', '=', 'CuentasTransferencia.idCuentaBancaria')
                ->leftJoin('Banco', 'CuentasTransferencia.idBanco', '=', 'Banco.idBanco')
                ->selectRaw("CASE WHEN UPPER(MetodoPago.nombreMetodo) LIKE '%TRANSFERENCIA%' THEN COALESCE(Banco.nombreBanco, MetodoPago.nombreMetodo) ELSE MetodoPago.nombreMetodo END as metodo_banco, PagoVenta.idVenta, Venta.fechaVenta, PagoVenta.fechaPago, PagoVenta.monto, PagoVenta.nroOperacion, Usuario.user as vendedor")
                ->whereRaw("UPPER(Venta.canal) = 'TIENDA'")
                ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
                ->whereExists(function ($query) {
                    $query->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('DetalleVenta')
                        ->whereColumn('DetalleVenta.idVenta', 'Venta.idVenta')
                        ->where('DetalleVenta.precioVenta', '>', 0.10)
                        ->where('DetalleVenta.estado', '!=', 'DEVUELTO');
                })
                ->orderByRaw("CASE WHEN UPPER(MetodoPago.nombreMetodo) LIKE '%TRANSFERENCIA%' THEN COALESCE(Banco.nombreBanco, MetodoPago.nombreMetodo) ELSE MetodoPago.nombreMetodo END")
                ->orderByDesc('Venta.fechaVenta')
                ->get()
                ->groupBy('metodo_banco');

            $ventasMesRaw = Venta::query()
                ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
                ->selectRaw('DATE(Venta.fechaVenta) as fecha, SUM(DetalleVenta.cantidad) as total_unidades, SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as total_monto')
                ->where('DetalleVenta.precioVenta', '>', 0.10)
                ->where(function ($q) {
                    $this->applyInventarioFilter($q, true);
                })
                ->whereRaw("UPPER(Venta.canal) = 'TIENDA'")
                ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
                ->groupBy(\Illuminate\Support\Facades\DB::raw('DATE(Venta.fechaVenta)'))
                ->orderBy('fecha', 'asc')
                ->get();

            // â”€â”€ Consulta para agrupar por SKU (Modelo) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $skusTienda = \App\Models\Ventas\DetalleVenta::query()
                ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
                ->join('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
                ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
                ->selectRaw("Producto.modelo as sku,
                             SUM(DetalleVenta.cantidad) as total_unidades,
                             SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                             SUM(((($costoVentaExpr) + ($comisionTiendaExpr)) * DetalleVenta.cantidad + ($costosComponentesSub))) as costos,
                             SUM(($comisionTiendaExpr) * DetalleVenta.cantidad) as comision_tienda")
                ->where('DetalleVenta.precioVenta', '>', 0.10)
                ->where(function ($q) {
                    $this->applyInventarioFilter($q, true);
                })
                ->whereRaw("UPPER(Venta.canal) = 'TIENDA'")
                ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
                ->groupBy('Producto.modelo')
                ->orderByDesc('ingresos')
                ->get()
                ->map(function ($sku) {
                    $sku->ingresos = round($sku->ingresos, 2);
                    $sku->costos = round($sku->costos, 2);
                    $sku->ganancia = round($sku->ingresos - $sku->costos, 2);
                    $sku->margen = $sku->costos > 0 ? round(($sku->ganancia / $sku->costos) * 100, 2) : 0;
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

    public function productoHistorialIndex(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        if (!$this->validateAccess($userModel, 13)) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaÃ±a', 'warning', 'btn-danger');
            return redirect()->route('dashboard', ['user' => $userModel]);
        }

        return view('analytics.producto_historial', [
            'userModel' => $userModel,
            'user' => $userModel
        ]);
    }

    public function productoHistorialData(Request $request)
    {
        $idProducto = $request->input('idProducto');

        if (!$idProducto) {
            return response()->json([]);
        }

        $historial = \Illuminate\Support\Facades\DB::table('DetalleVenta')
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->select(
                'Venta.fechaVenta',
                'Venta.canal',
                'Venta.numeroOrden',
                'Venta.idVenta',
                'DetalleVenta.cantidad',
                'DetalleVenta.precioVenta',
                'DetalleVenta.estado'
            )
            ->where('DetalleVenta.idProducto', $idProducto)
            ->orderByDesc('Venta.fechaVenta')
            ->get()
            ->map(function ($item) {
                return [
                    'fecha' => \Carbon\Carbon::parse($item->fechaVenta)->format('d/m/Y H:i'),
                    'fecha_sort' => \Carbon\Carbon::parse($item->fechaVenta)->timestamp,
                    'canal' => $item->canal ?? 'Desconocido',
                    'orden' => $item->numeroOrden ?? 'V-' . $item->idVenta,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => number_format($item->precioVenta, 2),
                    'total' => number_format($item->precioVenta * $item->cantidad, 2),
                    'estado' => $item->estado
                ];
            });

        return response()->json($historial);
    }
}
