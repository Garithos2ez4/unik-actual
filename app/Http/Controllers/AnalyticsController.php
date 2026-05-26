<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\HeaderServiceInterface;
use App\Services\CalculadoraServiceInterface;
use App\Models\Venta;
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

    public function index(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        // Validar acceso (idVista == 13 = Resumen Ejecutivo)
        $tieneAcceso = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 13) {
                $tieneAcceso = true;
                break;
            }
        }

        if (!$tieneAcceso) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
            return redirect()->route('dashboard', ['user' => $userModel]);
        }

        // Tasa de cambio para el histórico de Egresos
        $tc = $this->calculadoraService->getTasaCambio();

        // ── Filtros de fecha ──────────────────────────────────
        Carbon::setLocale('es');
        $anio = (int) $request->query('anio', now()->year);
        $mes  = (int) $request->query('mes', now()->month);
        $diaInicio = $request->query('dia_inicio');
        $diaFin    = $request->query('dia_fin');

        // Por defecto, todo el mes seleccionado
        $fechaInicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFin    = Carbon::create($anio, $mes, 1)->endOfMonth()->endOfDay();

        // Si envían un rango, aplicarlo (incluso si envían solo uno de los dos)
        if ($diaInicio || $diaFin) {
            if ($diaInicio) {
                $fechaInicio = Carbon::parse($diaInicio)->startOfDay();
            }
            if ($diaFin) {
                $fechaFin = Carbon::parse($diaFin)->endOfDay();
            } else {
                // Si envían dia_inicio pero no dia_fin, asumimos que quieren ver solo ese día específico
                $fechaFin = Carbon::parse($diaInicio)->endOfDay();
            }
        }

        // ── Helper para construir UNION ALL por métrica ────────
        // Todas las métricas nuevas de Venta aplican >= 2026-05-25
        // Todas las métricas antiguas de EgresoProducto aplican < 2026-05-25

        // ── 1. Tendencia de ventas (diarias) ──────────────────
        $qVentas1 = DB::table('DetalleVenta')
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->select(
                DB::raw('DATE(Venta.fechaVenta) as fecha'),
                'DetalleVenta.cantidad as cantidad',
                DB::raw('(DetalleVenta.precioVenta * DetalleVenta.cantidad) as monto')
            )
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('Venta.fechaVenta', '>=', '2026-05-25');

        $qEgresos1 = DB::table('EgresoProducto')
            ->join('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->select(
                DB::raw('DATE(EgresoProducto.fechaCompra) as fecha'),
                DB::raw('1 as cantidad'),
                DB::raw("COALESCE(Publicacion.precioPublicacion, Producto.precioDolar * $tc) as monto")
            )
            ->whereBetween('EgresoProducto.fechaCompra', [$fechaInicio, $fechaFin])
            ->where('EgresoProducto.fechaCompra', '<', '2026-05-25');

        $ventasMesRaw = DB::query()
            ->fromSub($qVentas1->unionAll($qEgresos1), 'unioned')
            ->select('fecha', DB::raw('SUM(cantidad) as total_unidades'), DB::raw('SUM(monto) as total_monto'))
            ->groupBy('fecha')
            ->orderBy('fecha', 'asc')
            ->get();

        // Rellenar días sin ventas con 0
        $ventasMes = [];
        $current = $fechaInicio->copy()->startOfDay();
        $end = $fechaFin->copy()->startOfDay();
        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');
            $found = $ventasMesRaw->firstWhere('fecha', $dateStr);
            $ventasMes[] = [
                'fecha' => $current->format('d/m'),
                'total' => $found ? $found->total_unidades : 0,
                'monto' => $found ? round($found->total_monto, 2) : 0
            ];
            $current->addDay();
        }

        // ── 2. Top SKUs más vendidos ──────────────────────────
        $qVentas2 = DB::table('DetalleVenta')
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->join('Publicacion', 'DetalleVenta.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->select('Publicacion.sku', 'Publicacion.titulo', 'DetalleVenta.cantidad as cantidad')
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('Venta.fechaVenta', '>=', '2026-05-25')
            ->whereNotNull('DetalleVenta.idPublicacion');

        $qEgresos2 = DB::table('EgresoProducto')
            ->join('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->select('Publicacion.sku', 'Publicacion.titulo', DB::raw('1 as cantidad'))
            ->whereBetween('EgresoProducto.fechaCompra', [$fechaInicio, $fechaFin])
            ->where('EgresoProducto.fechaCompra', '<', '2026-05-25');

        $skusMostSoldMonth = DB::query()
            ->fromSub($qVentas2->unionAll($qEgresos2), 'unioned')
            ->select('sku', 'titulo', DB::raw('SUM(cantidad) as total_ventas'))
            ->groupBy('sku', 'titulo')
            ->orderBy('total_ventas', 'desc')
            ->take(5)
            ->get();

        // ── 3. Métricas por plataforma ────────────────────────
        $qVentas3 = DB::table('DetalleVenta')
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->leftJoin('Publicacion', 'DetalleVenta.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->leftJoin('CuentasPlataforma', 'Publicacion.idCuentaPlataforma', '=', 'CuentasPlataforma.idCuentaPlataforma')
            ->leftJoin('Plataforma', 'CuentasPlataforma.idPlataforma', '=', 'Plataforma.idPlataforma')
            ->select(
                DB::raw('COALESCE(Plataforma.nombrePlataforma, "VENTA DIRECTA") as plataforma'),
                'DetalleVenta.cantidad as cantidad',
                DB::raw('(DetalleVenta.precioVenta * DetalleVenta.cantidad) as monto')
            )
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('Venta.fechaVenta', '>=', '2026-05-25');

        $qEgresos3 = DB::table('EgresoProducto')
            ->leftJoin('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->leftJoin('CuentasPlataforma', 'Publicacion.idCuentaPlataforma', '=', 'CuentasPlataforma.idCuentaPlataforma')
            ->leftJoin('Plataforma', 'CuentasPlataforma.idPlataforma', '=', 'Plataforma.idPlataforma')
            ->join('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->select(
                DB::raw('COALESCE(Plataforma.nombrePlataforma, "VENTA DIRECTA") as plataforma'),
                DB::raw('1 as cantidad'),
                DB::raw("COALESCE(Publicacion.precioPublicacion, Producto.precioDolar * $tc) as monto")
            )
            ->whereBetween('EgresoProducto.fechaCompra', [$fechaInicio, $fechaFin])
            ->where('EgresoProducto.fechaCompra', '<', '2026-05-25');

        $metricasPlataformas = DB::query()
            ->fromSub($qVentas3->unionAll($qEgresos3), 'unioned')
            ->select('plataforma', DB::raw('SUM(cantidad) as total_pedidos'), DB::raw('SUM(monto) as total_monto'))
            ->groupBy('plataforma')
            ->orderBy('total_monto', 'desc')
            ->get();

        // ── 4. Top productos por ingreso (monto S/) ──────────
        $qVentas4 = DB::table('DetalleVenta')
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->join('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->select(
                'Producto.idProducto', 'Producto.nombreProducto', 'Producto.modelo',
                'DetalleVenta.cantidad as cantidad',
                DB::raw('(DetalleVenta.precioVenta * DetalleVenta.cantidad) as monto')
            )
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('Venta.fechaVenta', '>=', '2026-05-25');

        $qEgresos4 = DB::table('EgresoProducto')
            ->join('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->select(
                'Producto.idProducto', 'Producto.nombreProducto', 'Producto.modelo',
                DB::raw('1 as cantidad'),
                DB::raw("COALESCE(Publicacion.precioPublicacion, Producto.precioDolar * $tc) as monto")
            )
            ->whereBetween('EgresoProducto.fechaCompra', [$fechaInicio, $fechaFin])
            ->where('EgresoProducto.fechaCompra', '<', '2026-05-25');

        $productosMostRevenueMonth = DB::query()
            ->fromSub($qVentas4->unionAll($qEgresos4), 'unioned')
            ->select('idProducto', 'nombreProducto', 'modelo', DB::raw('SUM(cantidad) as total_unidades'), DB::raw('SUM(monto) as total_ingreso'))
            ->groupBy('idProducto', 'nombreProducto', 'modelo')
            ->orderBy('total_ingreso', 'desc')
            ->take(5)
            ->get();

        // ── 5. Top productos por cantidad vendida ─────────────
        $qVentas5 = DB::table('DetalleVenta')
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->join('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->select(
                'Producto.idProducto', 'Producto.nombreProducto', 'Producto.modelo',
                'DetalleVenta.cantidad as cantidad'
            )
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->where('Venta.fechaVenta', '>=', '2026-05-25');

        $qEgresos5 = DB::table('EgresoProducto')
            ->join('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->select(
                'Producto.idProducto', 'Producto.nombreProducto', 'Producto.modelo',
                DB::raw('1 as cantidad')
            )
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('devoluciones')
                    ->whereRaw('devoluciones.idEgreso = EgresoProducto.idEgreso');
            })
            ->whereBetween('EgresoProducto.fechaCompra', [$fechaInicio, $fechaFin])
            ->where('EgresoProducto.fechaCompra', '<', '2026-05-25');

        $productosMostSoldMonth = DB::query()
            ->fromSub($qVentas5->unionAll($qEgresos5), 'unioned')
            ->select('idProducto', 'nombreProducto', 'modelo', DB::raw('SUM(cantidad) as total_unidades'))
            ->groupBy('idProducto', 'nombreProducto', 'modelo')
            ->orderBy('total_unidades', 'desc')
            ->take(5)
            ->get();

        // ── 6. Top productos con fallas (solo RegistroProducto)
        $productosConFallas = Producto::query()
            ->join('DetalleComprobante', 'Producto.idProducto', '=', 'DetalleComprobante.idProducto')
            ->join('RegistroProducto', 'DetalleComprobante.idDetalleComprobante', '=', 'RegistroProducto.idDetalleComprobante')
            ->leftJoin('devoluciones', 'RegistroProducto.idRegistro', '=', 'devoluciones.idRegistro')
            ->select(
                'Producto.nombreProducto',
                'Producto.modelo',
                DB::raw('COUNT(DISTINCT RegistroProducto.idRegistro) as total_fallas')
            )
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereIn('RegistroProducto.estado', ['DEFECTUOSO', 'GARANTIA'])
                        ->whereNotNull('RegistroProducto.observacion')
                        ->where('RegistroProducto.observacion', '!=', '');
                })
                ->orWhere(function ($q2) {
                    $q2->whereNotNull('devoluciones.idDevolucion')
                        ->whereNotNull('devoluciones.motivo')
                        ->where('devoluciones.motivo', '!=', '');
                });
            })
            // Opcional: filtrar por fechas si se desea que las fallas sean relativas al rango
            // pero el dashboard original no lo hacía. Lo dejamos como global.
            ->groupBy('Producto.idProducto', 'Producto.nombreProducto', 'Producto.modelo')
            ->orderBy('total_fallas', 'desc')
            ->take(10)
            ->get();

        // ── 7. Top mejores meses históricos (Global) ──────────
        // Para calcular el global mes a mes sin importar el filtro:
        $qVentas7 = DB::table('Venta')
            ->select(
                DB::raw("DATE_FORMAT(fechaVenta, '%Y-%m') as mes_raw"),
                DB::raw("DATE_FORMAT(fechaVenta, '%M %Y') as mes_nombre"),
                'totalVenta as monto'
            )
            ->where('fechaVenta', '>=', '2026-05-25');

        $qEgresos7 = DB::table('EgresoProducto')
            ->join('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->select(
                DB::raw("DATE_FORMAT(EgresoProducto.fechaCompra, '%Y-%m') as mes_raw"),
                DB::raw("DATE_FORMAT(EgresoProducto.fechaCompra, '%M %Y') as mes_nombre"),
                DB::raw("COALESCE(Publicacion.precioPublicacion, Producto.precioDolar * $tc) as monto")
            )
            ->whereNotIn('EgresoProducto.numeroOrden', ['2026', '2026/SN'])
            ->where('EgresoProducto.fechaCompra', '<', '2026-05-25');

        $topBestMonths = DB::query()
            ->fromSub($qVentas7->unionAll($qEgresos7), 'unioned')
            ->select('mes_raw', 'mes_nombre', DB::raw('SUM(monto) as total_monto'))
            ->groupBy('mes_raw', 'mes_nombre')
            ->orderBy('total_monto', 'desc')
            ->take(3)
            ->get();

        // ── Datos para los filtros de la vista ────────────────
        $filtros = [
            'anio' => $anio,
            'mes' => $mes,
            'dia_inicio' => $diaInicio,
            'dia_fin' => $diaFin,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ];

        return view('analytics.index', [
            'user' => $userModel,
            'ventasMes' => $ventasMes,
            'productosConFallas' => $productosConFallas,
            'skusMostSoldMonth' => $skusMostSoldMonth,
            'metricasPlataformas' => $metricasPlataformas,
            'productosMostRevenueMonth' => $productosMostRevenueMonth,
            'productosMostSoldMonth' => $productosMostSoldMonth,
            'topBestMonths' => $topBestMonths,
            'filtros' => $filtros,
        ]);
    }
}
