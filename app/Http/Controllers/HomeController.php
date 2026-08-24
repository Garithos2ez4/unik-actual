<?php

namespace App\Http\Controllers;

use App\Services\DashboardServiceInterface;
use App\Services\HeaderServiceInterface;
use App\Services\FalabellaOrderSyncService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected $dashboardService;
    protected $headerService;
    protected $falabellaOrderSyncService;

    public function __construct(
        DashboardServiceInterface $dashboardService,
        HeaderServiceInterface $headerService,
        FalabellaOrderSyncService $falabellaOrderSyncService
    ) {
        $this->dashboardService = $dashboardService;
        $this->headerService = $headerService;
        $this->falabellaOrderSyncService = $falabellaOrderSyncService;
    }

    public function checkNewOrders(Request $request)
    {
        // 1. Web Orders
        $newOrders = \App\Models\Web\PedidoWeb::with('cliente')
            ->where('estado', 'PENDIENTE')
            ->orderBy('idPedidoWeb', 'asc')
            ->get();

        // 2. Ripley Orders (Trigger sync and fetch)
        try {
            \Illuminate\Support\Facades\Artisan::call('ripley:sync-orders');
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'cURL error 6') === false && strpos($e->getMessage(), 'cURL error 28') === false) {
                \Illuminate\Support\Facades\Log::error("Error sincronizando Ripley desde API checkNewOrders: " . $e->getMessage());
            }
        }

        $ripleyOrders = \App\Models\Ecommerce\RipleyOrder::whereIn('status', ['WAITING_ACCEPTANCE', 'SHIPPING'])
            ->where('created_at_ripley', '>=', now()->subDays(5))
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'new_orders' => $newOrders,
            'ripley_orders' => $ripleyOrders
        ]);
    }

    public function index(Request $request)
    {
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        //variables del controlador
        $productosStockMin = \Illuminate\Support\Facades\Cache::remember('dash_stock_min', 60, function () {
            return $this->dashboardService->getStockMinProducts()->total();
        });

        $totalProductos = \Illuminate\Support\Facades\Cache::remember('dash_total_prod', 60, function () {
            return $this->dashboardService->getTotalProducts();
        });

        $productosMostSold = \Illuminate\Support\Facades\Cache::remember('dash_most_sold_prod', 60, function () {
            return $this->dashboardService->getMostSoldProducts();
        });

        $publicacionesMostSold = \Illuminate\Support\Facades\Cache::remember('dash_most_sold_pub', 60, function () {
            return $this->dashboardService->getMostSoldPublicaciones();
        });

        // Nueva lógica para productos top del mes (Solo ventas que NO han sido devueltas)
        $productosMostSoldMonth = \Illuminate\Support\Facades\Cache::remember('dash_prod_most_sold_month', 60, function () {
            return \App\Models\Catalogo\Producto::query()
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
        });

        $reclamosUrgentes = \Illuminate\Support\Facades\Cache::remember('dash_reclamos_urg', 60, function () {
            return \App\Models\Reclamos\ReclamoPlataforma::where('estadoGeneral', 'ABIERTO')
                ->where('fechaMaxRespuesta', '<=', now()->addDays(3))
                ->count();
        });

        // Ranking de 5 productos con más stock (Suma de todos los almacenes)
        $productosMostStock = \Illuminate\Support\Facades\Cache::remember('dash_prod_most_stock', 60, function () {
            return \App\Models\Catalogo\Producto::query()
                ->join('Inventario', 'Inventario.idProducto', '=', 'Producto.idProducto')
                ->select('Producto.*', \DB::raw('SUM(Inventario.stock) as total_stock'))
                ->groupBy('Producto.idProducto')
                ->orderBy('total_stock', 'desc')
                ->take(5)
                ->get();
        });

        // Top 5 publicaciones con mayor monto vendido (Mes actual)
        $publicacionesTopMonto = \Illuminate\Support\Facades\Cache::remember('dash_pub_top_monto', 60, function () {
            return \App\Models\Catalogo\Publicacion::query()
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
        });

        // Top 5 publicaciones con mayor monto vendido (Histórico)
        $publicacionesTopMontoHist = \Illuminate\Support\Facades\Cache::remember('dash_pub_top_monto_hist', 720, function () {
            return \App\Models\Catalogo\Publicacion::query()
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
        });

        // Top 3 SKUs con más ventas (Mes actual)
        $skusMostSoldMonth = \Illuminate\Support\Facades\Cache::remember('dash_sku_most_sold', 60, function () {
            return \App\Models\Catalogo\Publicacion::query()
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
        });

        // Top 5 productos con más fallas (Devoluciones o Defectuosos con observación)
        $productosConFallas = \Illuminate\Support\Facades\Cache::remember('dash_prod_fallas', 60, function () {
            return \App\Models\Catalogo\Producto::query()
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
                ->take(5)
                ->get();
        });

        // Ventas de los últimos 7 días
        $ventas7DiasRaw = \Illuminate\Support\Facades\Cache::remember('dash_ventas_7_dias', 60, function () {
            return \App\Models\Inventario\EgresoProducto::query()
                ->select(\DB::raw('DATE(fechaCompra) as fecha'), \DB::raw('COUNT(*) as total'))
                ->where('fechaCompra', '>=', now()->subDays(6)->startOfDay())
                ->groupBy(\DB::raw('DATE(fechaCompra)'))
                ->orderBy('fecha', 'asc')
                ->get();
        });

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


        $topReabastecimiento = collect();
        if ($userModel->Accesos->contains('idVista', 10)) {
            $topReabastecimiento = \Illuminate\Support\Facades\Cache::remember('dash_top_reabastecimiento_10', 60, function () {
                return \App\Models\Catalogo\Producto::query()
                    ->select('Producto.idProducto', 'Producto.nombreProducto', 'Producto.modelo', 'Producto.codigoProducto', 'Producto.imagenProducto1')
                    ->selectRaw("COALESCE((SELECT stock FROM Inventario WHERE Inventario.idProducto = Producto.idProducto AND Inventario.idAlmacen = 1 LIMIT 1), 0) as stock_tienda")
                    ->selectRaw("COALESCE((SELECT stock FROM Inventario WHERE Inventario.idProducto = Producto.idProducto AND Inventario.idAlmacen = 2 LIMIT 1), 0) as stock_alm2")
                    ->selectRaw("COALESCE((SELECT stock FROM Inventario WHERE Inventario.idProducto = Producto.idProducto AND Inventario.idAlmacen = 3 LIMIT 1), 0) as stock_alm3")
                    ->selectRaw("COALESCE((SELECT stock FROM Inventario WHERE Inventario.idProducto = Producto.idProducto AND Inventario.idAlmacen = 4 LIMIT 1), 0) as stock_alm4")
                    ->selectRaw("COALESCE((SELECT SUM(cantidad) FROM DetalleVenta WHERE DetalleVenta.idProducto = Producto.idProducto AND estado = 'COMPLETADO'), 0) as total_ventas")
                    ->havingRaw('stock_tienda <= 2 AND (stock_alm2 > 0 OR stock_alm3 > 0 OR stock_alm4 > 0)')
                    ->orderBy('total_ventas', 'desc')
                    ->limit(10)
                    ->get();
            });
        }


        $registros = \Illuminate\Support\Facades\Cache::remember('dash_registros', 60, function () {
            return $this->dashboardService->getRegistrosXEstados();
        });

        $inventarioInfo = \Illuminate\Support\Facades\Cache::remember('dash_inventario_stock', 60, function () {
            $allInventory = $this->dashboardService->getAllInventory();
            $inventarioTotal = $allInventory->sum('stock');
            $almacenesList = $allInventory->unique('idAlmacen')->pluck('Almacen');

            $stockArray = [];
            foreach ($almacenesList as $almacen) {
                $stockArray[] = ['almacen' => $almacen, 'cantidad' => $this->dashboardService->getInventoryByAlmacen($almacen->idAlmacen)->sum('stock')];
            }

            return [
                'inventario' => $inventarioTotal,
                'stock' => $stockArray
            ];
        });

        $inventario = $inventarioInfo['inventario'];
        $stock = $inventarioInfo['stock'];
        $colors = ['#ff5733', '#33c6ff', '#75e93c', '#f4d84d'];

        // Devoluciones de hoy (Falabella) para Resumen Gerencial
        $devolucionesHoy = collect();
        $tieneAccesoAnalitica = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 13) {
                $tieneAccesoAnalitica = true;
                break;
            }
        }

        if ($tieneAccesoAnalitica) {
            // Auto-sync devoluciones Falabella
            $cacheKeyDevoluciones = 'falabella_returns_sync_' . now()->toDateString();
            if (!\Illuminate\Support\Facades\Cache::has($cacheKeyDevoluciones)) {
                try {
                    $this->falabellaOrderSyncService->syncReturnsByDateRange(now()->toDateString(), now()->toDateString());
                    \Illuminate\Support\Facades\Cache::put($cacheKeyDevoluciones, true, now()->addMinutes(60));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Error auto-sync devoluciones en dashboard: ' . $e->getMessage());
                }
            }
            $devolucionesHoy = $this->falabellaOrderSyncService->getReturns(now()->toDateString(), now()->toDateString(), null);

            // Auto-sync bot de precios Falabella (1 vez al dÃ­a en segundo plano)
            $cacheKeyBot = 'falabella_bot_run_' . now()->toDateString();
            if (!\Illuminate\Support\Facades\Cache::has($cacheKeyBot)) {
                try {
                    // Ejecutar de forma asíncrona en Windows (para no congelar el inicio de sesión durante 5+ minutos)
                    pclose(popen('start /B php ' . base_path('artisan') . ' bot:falabella-prices', 'r'));
                    \Illuminate\Support\Facades\Cache::put($cacheKeyBot, true, now()->endOfDay());
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Error auto-run bot falabella: ' . $e->getMessage());
                }
            }
        }

        $productosOldStock = \Illuminate\Support\Facades\Cache::remember('dashboard_old_stock', now()->addMinutes(720), function () {
            return \App\Models\Catalogo\Producto::select('Producto.idProducto', 'Producto.nombreProducto', 'Producto.imagenProducto1')
                ->join('DetalleComprobante', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
                ->join('RegistroProducto', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
                ->join('IngresoProducto', 'IngresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
                ->where('RegistroProducto.estado', 'NUEVO')
                ->where('IngresoProducto.fechaIngreso', '<=', now()->subYear())
                ->selectRaw('COUNT(RegistroProducto.idRegistro) as stock_estancado')
                ->selectRaw('MIN(IngresoProducto.fechaIngreso) as fecha_mas_antigua')
                ->groupBy('Producto.idProducto', 'Producto.nombreProducto', 'Producto.imagenProducto1')
                ->havingRaw('COUNT(RegistroProducto.idRegistro) > 0')
                ->orderBy('fecha_mas_antigua', 'asc')
                ->take(10)
                ->get();
        });
        //   \Log::info("Count de productosOldStock: " . $productosOldStock->count());

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
                    'productosOldStock' => $productosOldStock,
                    'publicacionesTopMonto' => $publicacionesTopMonto,
                    'publicacionesTopMontoHist' => $publicacionesTopMontoHist,
                    'productosConFallas' => $productosConFallas,
                    'ventas7Dias' => $ventas7Dias,
                    'devolucionesHoy' => $devolucionesHoy,
                ])->render(),
            ]);
        }

        $alertasPrecio = collect();
        if ($tieneAccesoAnalitica) {
            $alertasPrecio = \App\Models\Ecommerce\AlertaPrecio::where('estado', 'pendiente')->get();
        }
        $almacenes = \Illuminate\Support\Facades\Cache::remember('dash_almacenes', 3600, function () {
            return \App\Models\Inventario\Almacen::all();
        });

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
            'productosOldStock' => $productosOldStock,
            'publicacionesTopMonto' => $publicacionesTopMonto,
            'publicacionesTopMontoHist' => $publicacionesTopMontoHist,
            'productosConFallas' => $productosConFallas,
            'ventas7Dias' => $ventas7Dias,
            'devolucionesHoy' => $devolucionesHoy,
            'alertasPrecio' => $alertasPrecio,
            'topReabastecimiento' => $topReabastecimiento,
            'almacenes' => $almacenes,
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

    public function stockMinDashboard(Request $request)
    {
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        $productos = $this->dashboardService->getStockMinProducts()->appends($request->all());
        $tc = app(\App\Services\CalculadoraServiceInterface::class)->getTasaCambio();
        $almacenes = \App\Models\Inventario\Almacen::with('Ubicaciones')->get();

        if ($request->query('page') || $request->ajax()) {
            $view = view('components.lista_producto', [
                'productos' => $productos,
                'tc' => $tc,
                'container' => $request->query('container', 'container-list-products-dashboard'),
                'almacenes' => $almacenes
            ])->render();

            $view = mb_convert_encoding($view, 'UTF-8', 'UTF-8');

            return response()->json(['html' => $view]);
        }

        return view('stockmin_dashboard', [
            'user' => $userModel,
            'productos' => $productos,
            'tc' => $tc,
            'almacenes' => $almacenes
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
    public function ignorarAlerta($id)
    {
        $alerta = \App\Models\Ecommerce\AlertaPrecio::find($id);
        if ($alerta) {
            $alerta->update(['estado' => 'procesada']);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    }
}
