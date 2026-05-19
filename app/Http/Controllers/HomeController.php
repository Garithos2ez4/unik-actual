<?php

namespace App\Http\Controllers;

use App\Services\CalculadoraServiceInterface;
use App\Services\DashboardServiceInterface;
use App\Services\HeaderServiceInterface;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected $dashboardService;
    protected $headerService;
    protected $calculadoraService;

    public function __construct(
        DashboardServiceInterface $dashboardService,
        HeaderServiceInterface $headerService,
        CalculadoraServiceInterface $calculadoraService
    ) {
        $this->dashboardService = $dashboardService;
        $this->headerService = $headerService;
        $this->calculadoraService = $calculadoraService;
    }

    public function index(Request $request)
    {
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        //variables del controlador
        $productosStockMin = $this->dashboardService->getStockMinProducts()->total();
        $totalProductos = $this->dashboardService->getTotalProducts();
        $productosMostSold = $this->dashboardService->getMostSoldProducts();
        $publicacionesMostSold = $this->dashboardService->getMostSoldPublicaciones();

        // Nueva lógica para productos top del mes (Solo ventas que NO han sido devueltas)
        $productosMostSoldMonth = \App\Models\Producto::query()
            ->join('DetalleComprobante', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->join('RegistroProducto', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('EgresoProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->select('Producto.*', \DB::raw('COUNT(EgresoProducto.idEgreso) as total_ventas'))
            ->whereMonth('EgresoProducto.fechaCompra', now()->month)
            ->whereYear('EgresoProducto.fechaCompra', now()->year)
            ->whereNotExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('devoluciones')
                    ->whereRaw('devoluciones.idEgreso = EgresoProducto.idEgreso');
            })
            ->groupBy('Producto.idProducto')
            ->orderBy('total_ventas', 'desc')
            ->take(3)
            ->get();

        $reclamosUrgentes = \App\Models\ReclamoPlataforma::where('estadoGeneral', 'ABIERTO')
            ->where('fechaMaxRespuesta', '<=', now()->addDays(3))
            ->count();

        // Ranking de 5 productos con más stock (Suma de todos los almacenes)
        $productosMostStock = \App\Models\Producto::query()
            ->join('Inventario', 'Inventario.idProducto', '=', 'Producto.idProducto')
            ->select('Producto.*', \DB::raw('SUM(Inventario.stock) as total_stock'))
            ->groupBy('Producto.idProducto')
            ->orderBy('total_stock', 'desc')
            ->take(5)
            ->get();

        // Top 5 publicaciones con mayor monto vendido (Mes actual)
        $publicacionesTopMonto = \App\Models\Publicacion::query()
            ->join('EgresoProducto', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->select('Publicacion.*', \DB::raw('SUM(Publicacion.precioPublicacion) as total_monto'))
            ->whereMonth('EgresoProducto.fechaCompra', now()->month)
            ->whereYear('EgresoProducto.fechaCompra', now()->year)
            ->whereNotExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('devoluciones')
                    ->whereRaw('devoluciones.idEgreso = EgresoProducto.idEgreso');
            })
            ->groupBy('Publicacion.idPublicacion')
            ->orderBy('total_monto', 'desc')
            ->take(5)
            ->get();

        // Top 5 publicaciones con mayor monto vendido (Histórico)
        $publicacionesTopMontoHist = \App\Models\Publicacion::query()
            ->join('EgresoProducto', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->select('Publicacion.*', \DB::raw('SUM(Publicacion.precioPublicacion) as total_monto'))
            ->whereNotExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('devoluciones')
                    ->whereRaw('devoluciones.idEgreso = EgresoProducto.idEgreso');
            })
            ->groupBy('Publicacion.idPublicacion')
            ->orderBy('total_monto', 'desc')
            ->take(5)
            ->get();

        // Top 3 SKUs con más ventas (Mes actual)
        $skusMostSoldMonth = \App\Models\Publicacion::query()
            ->join('EgresoProducto', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->select('Publicacion.sku', 'Publicacion.titulo', \DB::raw('COUNT(EgresoProducto.idEgreso) as total_ventas'))
            ->whereMonth('EgresoProducto.fechaCompra', now()->month)
            ->whereYear('EgresoProducto.fechaCompra', now()->year)
            ->whereNotExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('devoluciones')
                    ->whereRaw('devoluciones.idEgreso = EgresoProducto.idEgreso');
            })
            ->groupBy('Publicacion.sku', 'Publicacion.titulo')
            ->orderBy('total_ventas', 'desc')
            ->take(3)
            ->get();

        // Top 5 productos con más fallas (Devoluciones o Defectuosos con observación)
        $productosConFallas = \App\Models\Producto::query()
            ->join('DetalleComprobante', 'Producto.idProducto', '=', 'DetalleComprobante.idProducto')
            ->join('RegistroProducto', 'DetalleComprobante.idDetalleComprobante', '=', 'RegistroProducto.idDetalleComprobante')
            ->leftJoin('devoluciones', 'RegistroProducto.idRegistro', '=', 'devoluciones.idRegistro')
            ->select('Producto.nombreProducto', 'Producto.modelo', \DB::raw('COUNT(DISTINCT RegistroProducto.idRegistro) as total_fallas'))
            ->where(function($q) {
                $q->where(function($q2) {
                    $q2->whereIn('RegistroProducto.estado', ['DEFECTUOSO', 'GARANTIA'])
                       ->whereNotNull('RegistroProducto.observacion')
                       ->where('RegistroProducto.observacion', '!=', '');
                })
                ->orWhere(function($q2) {
                    $q2->whereNotNull('devoluciones.idDevolucion')
                       ->whereNotNull('devoluciones.motivo')
                       ->where('devoluciones.motivo', '!=', '');
                });
            })
            ->groupBy('Producto.idProducto', 'Producto.nombreProducto', 'Producto.modelo')
            ->orderBy('total_fallas', 'desc')
            ->take(5)
            ->get();

        // Ventas de los últimos 7 días
        $ventas7DiasRaw = \App\Models\EgresoProducto::query()
            ->select(\DB::raw('DATE(fechaCompra) as fecha'), \DB::raw('COUNT(*) as total'))
            ->where('fechaCompra', '>=', now()->subDays(6)->startOfDay())
            ->groupBy(\DB::raw('DATE(fechaCompra)'))
            ->orderBy('fecha', 'asc')
            ->get();

        // Rellenar días sin ventas
        $ventas7Dias = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $found = $ventas7DiasRaw->firstWhere('fecha', $date);
            $ventas7Dias[] = [
                'fecha' => now()->subDays($i)->format('d/m'),
                'total' => $found ? $found->total : 0
            ];
        }


        $registros = $this->dashboardService->getRegistrosXEstados();
        $inventario = $this->dashboardService->getAllInventory()->sum('stock');
        $almacenes = $this->dashboardService->getAllInventory()->unique('idAlmacen')->pluck('Almacen');
        $colors = ['#ff5733', '#33c6ff', '#75e93c', '#f4d84d'];
        $stock = array();

        foreach ($almacenes as $almacen) {
            $stock[] = ['almacen' => $almacen, 'cantidad' => $this->dashboardService->getInventoryByAlmacen($almacen->idAlmacen)->sum('stock')];
        }

        if ($request->query('query')) {
            return response()->json([
                view('components.dashboard_content', [
                    'user' => $userModel,
                    'registros' => $registros,
                    'inventario' => $inventario,
                    'stock' => $stock,
                    'colors' => $colors,
                    'productos' => $totalProductos,
                    'stockMin' => $productosStockMin,
                    'productosMostSold' => $productosMostSold,
                    'productosMostSoldMonth' => $productosMostSoldMonth,
                    'skusMostSoldMonth' => $skusMostSoldMonth,
                    'publicacionesMostSold' => $publicacionesMostSold,
                    'reclamosUrgentes' => $reclamosUrgentes,
                    'productosMostStock' => $productosMostStock,
                    'publicacionesTopMonto' => $publicacionesTopMonto,
                    'publicacionesTopMontoHist' => $publicacionesTopMontoHist,
                    'productosConFallas' => $productosConFallas,
                    'ventas7Dias' => $ventas7Dias
                ])->render(),
            ]);
        }

        return view('dashboard', [
            'user' => $userModel,
            'registros' => $registros,
            'inventario' => $inventario,
            'stock' => $stock,
            'colors' => $colors,
            'productos' => $totalProductos,
            'stockMin' => $productosStockMin,
            'productosMostSold' => $productosMostSold,
            'productosMostSoldMonth' => $productosMostSoldMonth,
            'skusMostSoldMonth' => $skusMostSoldMonth,
            'publicacionesMostSold' => $publicacionesMostSold,
            'reclamosUrgentes' => $reclamosUrgentes,
            'productosMostStock' => $productosMostStock,
            'publicacionesTopMonto' => $publicacionesTopMonto,
            'publicacionesTopMontoHist' => $publicacionesTopMontoHist,
            'productosConFallas' => $productosConFallas,
            'ventas7Dias' => $ventas7Dias
        ]);
    }

    public function analytics()
    {
        $userModel = $this->headerService->getModelUser();

        $tc = $this->calculadoraService->getTasaCambio();

        // Ventas del mes actual con Monto (S/)
        $ventasMesRaw = \App\Models\EgresoProducto::query()
            ->join('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->select(
                \DB::raw('DATE(EgresoProducto.fechaCompra) as fecha'),
                \DB::raw('COUNT(EgresoProducto.idEgreso) as total_unidades'),
                \DB::raw("SUM(COALESCE(Publicacion.precioPublicacion, Producto.precioDolar * $tc)) as total_monto")
            )
            ->whereMonth('EgresoProducto.fechaCompra', now()->month)
            ->whereYear('EgresoProducto.fechaCompra', now()->year)
            ->groupBy(\DB::raw('DATE(EgresoProducto.fechaCompra)'))
            ->orderBy('fecha', 'asc')
            ->get();

        $ventasMes = [];
        $diasEnMes = now()->daysInMonth;
        for ($i = 1; $i <= $diasEnMes; $i++) {
            $date = now()->startOfMonth()->addDays($i - 1)->format('Y-m-d');
            $found = $ventasMesRaw->firstWhere('fecha', $date);
            $ventasMes[] = [
                'fecha' => now()->startOfMonth()->addDays($i - 1)->format('d/m'),
                'total' => $found ? $found->total_unidades : 0,
                'monto' => $found ? $found->total_monto : 0
            ];
        }

        // Top 5 productos con más fallas
        $productosConFallas = \App\Models\Producto::query()
            ->join('DetalleComprobante', 'Producto.idProducto', '=', 'DetalleComprobante.idProducto')
            ->join('RegistroProducto', 'DetalleComprobante.idDetalleComprobante', '=', 'RegistroProducto.idDetalleComprobante')
            ->leftJoin('devoluciones', 'RegistroProducto.idRegistro', '=', 'devoluciones.idRegistro')
            ->select('Producto.nombreProducto', 'Producto.modelo', \DB::raw('COUNT(DISTINCT RegistroProducto.idRegistro) as total_fallas'))
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
            ->groupBy('Producto.idProducto', 'Producto.nombreProducto', 'Producto.modelo')
            ->orderBy('total_fallas', 'desc')
            ->take(10) // Traemos 10 para la página de analítica
            ->get();

        // Top 5 SKUs con más ventas (Mes)
        $skusMostSoldMonth = \App\Models\Publicacion::query()
            ->join('EgresoProducto', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->select('Publicacion.sku', 'Publicacion.titulo', \DB::raw('COUNT(EgresoProducto.idEgreso) as total_ventas'))
            ->whereMonth('EgresoProducto.fechaCompra', now()->month)
            ->whereYear('EgresoProducto.fechaCompra', now()->year)
            ->groupBy('Publicacion.sku', 'Publicacion.titulo')
            ->orderBy('total_ventas', 'desc')
            ->take(5)
            ->get();
        // Métricas por plataforma (Mes actual)
        $metricasPlataformas = \App\Models\EgresoProducto::query()
            ->leftJoin('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->leftJoin('CuentasPlataforma', 'Publicacion.idCuentaPlataforma', '=', 'CuentasPlataforma.idCuentaPlataforma')
            ->leftJoin('Plataforma', 'CuentasPlataforma.idPlataforma', '=', 'Plataforma.idPlataforma')
            ->join('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->select(
                \DB::raw('COALESCE(Plataforma.nombrePlataforma, "VENTA DIRECTA") as plataforma'),
                \DB::raw('COUNT(EgresoProducto.idEgreso) as total_pedidos'),
                \DB::raw("SUM(COALESCE(Publicacion.precioPublicacion, Producto.precioDolar * $tc)) as total_monto")
            )
            ->whereMonth('EgresoProducto.fechaCompra', now()->month)
            ->whereYear('EgresoProducto.fechaCompra', now()->year)
            ->groupBy('plataforma')
            ->orderBy('total_monto', 'desc')
            ->get();

        // Top 5 Productos con más ingresos (Mes actual)
        $productosMostRevenueMonth = \App\Models\Producto::query()
            ->join('DetalleComprobante', 'Producto.idProducto', '=', 'DetalleComprobante.idProducto')
            ->join('RegistroProducto', 'DetalleComprobante.idDetalleComprobante', '=', 'RegistroProducto.idDetalleComprobante')
            ->join('EgresoProducto', 'RegistroProducto.idRegistro', '=', 'EgresoProducto.idRegistro')
            ->leftJoin('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->select(
                'Producto.idProducto',
                'Producto.nombreProducto',
                'Producto.modelo',
                \DB::raw('COUNT(EgresoProducto.idEgreso) as total_unidades'),
                \DB::raw("SUM(COALESCE(Publicacion.precioPublicacion, Producto.precioDolar * $tc)) as total_ingreso")
            )
            ->whereMonth('EgresoProducto.fechaCompra', now()->month)
            ->whereYear('EgresoProducto.fechaCompra', now()->year)
            ->groupBy('Producto.idProducto', 'Producto.nombreProducto', 'Producto.modelo')
            ->orderBy('total_ingreso', 'desc')
            ->take(5)
            ->get();

        // Top 5 Productos más vendidos por cantidad (Mes actual)
        $productosMostSoldMonth = \App\Models\Producto::query()
            ->join('DetalleComprobante', 'Producto.idProducto', '=', 'DetalleComprobante.idProducto')
            ->join('RegistroProducto', 'DetalleComprobante.idDetalleComprobante', '=', 'RegistroProducto.idDetalleComprobante')
            ->join('EgresoProducto', 'RegistroProducto.idRegistro', '=', 'EgresoProducto.idRegistro')
            ->select(
                'Producto.idProducto',
                'Producto.nombreProducto',
                'Producto.modelo',
                \DB::raw('COUNT(EgresoProducto.idEgreso) as total_unidades')
            )
            ->whereMonth('EgresoProducto.fechaCompra', now()->month)
            ->whereYear('EgresoProducto.fechaCompra', now()->year)
            ->whereNotExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('devoluciones')
                    ->whereRaw('devoluciones.idEgreso = EgresoProducto.idEgreso');
            })
            ->groupBy('Producto.idProducto', 'Producto.nombreProducto', 'Producto.modelo')
            ->orderBy('total_unidades', 'desc')
            ->take(5)
            ->get();

        // Top 3 mejores meses de venta (Histórico)
        $topBestMonths = \App\Models\EgresoProducto::query()
            ->join('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('Publicacion', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion')
            ->select(
                \DB::raw("DATE_FORMAT(EgresoProducto.fechaCompra, '%Y-%m') as mes_raw"),
                \DB::raw("DATE_FORMAT(EgresoProducto.fechaCompra, '%M %Y') as mes_nombre"),
                \DB::raw("SUM(COALESCE(Publicacion.precioPublicacion, Producto.precioDolar * $tc)) as total_monto")
            )
            ->whereNotIn('EgresoProducto.numeroOrden', ['2026', '2026/SN'])
            ->groupBy('mes_raw', 'mes_nombre')
            ->orderBy('total_monto', 'desc')
            ->take(3)
            ->get();

        return view('analytics.index', [
            'user' => $userModel,
            'ventasMes' => $ventasMes,
            'productosConFallas' => $productosConFallas,
            'skusMostSoldMonth' => $skusMostSoldMonth,
            'metricasPlataformas' => $metricasPlataformas,
            'productosMostRevenueMonth' => $productosMostRevenueMonth,
            'productosMostSoldMonth' => $productosMostSoldMonth,
            'topBestMonths' => $topBestMonths
        ]);
    }

    public function dashboardInventario($estado, Request $request)
    {
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        $tipo = $this->caseRegistro($estado);
        $registros = $tipo[0];
        $data = $tipo[1];

        if ($request->query('page')) {
            $view = view('components.lista_registros_dashboard', ['registros' => $registros, 'data' => $data, 'container' => $request->query('container')])->render();
            return response()->json(['html' => $view]);
        }

        return view('dashboard_inventario', [
            'user' => $userModel,
            'registros' => $registros,
            'data' => $data
        ]);
    }

    public function stockMinDashboard()
    {
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        $productos = $this->dashboardService->getStockMinProducts();
        return view('stockmin_dashboard', [
            'user' => $userModel,
            'productos' => $productos,
            'tc' => $this->calculadoraService->getTasaCambio()
        ]);
    }

    private function caseRegistro($estado)
    {
        switch (decrypt($estado)) {
            case 'NUEVO':
                $registros = $this->dashboardService->getNuevosInventario();
                $data = ['icon' => 'boxes', 'pestania' => 'Nuevos', 'titulo' => 'Productos Nuevos', 'color' => 'bg-sistema-uno'];
                break;
            case 'ENTREGADO':
                $registros = $this->dashboardService->getEntregadosInventario();
                $data = ['icon' => 'cart', 'pestania' => 'Entregados', 'titulo' => 'Productos Entregados', 'color' => 'bg-green'];
                break;
            case 'DEVOLUCION':
                $registros = $this->dashboardService->getDevolucionesInventario();
                $data = ['icon' => 'truck', 'pestania' => 'Devoluciones', 'titulo' => 'Productos Devueltos', 'color' => 'bg-warning'];
                break;
            case 'ABIERTO':
                $registros = $this->dashboardService->getAbiertosInventario();
                $data = ['icon' => 'dropbox', 'pestania' => 'Abiertos', 'titulo' => 'Productos Abiertos', 'color' => 'bg-purple'];
                break;
            case 'DEFECTUOSO':
                $registros = $this->dashboardService->getDefectuososInventario();
                $data = ['icon' => 'x-lg', 'pestania' => 'Defectuosos', 'titulo' => 'Productos Defectuosos', 'color' => 'bg-danger'];
                break;
            case 'GARANTIA':
                $registros = $this->dashboardService->getGarantiaInventario();
                $data = ['icon' => 'award', 'pestania' => 'Garantías', 'titulo' => 'Productos en Garantía', 'color' => 'bg-marron'];
                break;
            default:
                return back();
        }

        return $response = [$registros, $data];
    }
}
