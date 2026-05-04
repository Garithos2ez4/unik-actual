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

    public function __construct(DashboardServiceInterface $dashboardService,
                                HeaderServiceInterface $headerService,
                                CalculadoraServiceInterface $calculadoraService)
    {
        $this->dashboardService = $dashboardService;
        $this->headerService = $headerService;
        $this->calculadoraService = $calculadoraService;
    }

    public function index(Request $request){
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();
        
        //variables del controlador
        $productosStockMin = $this->dashboardService->getStockMinProducts()->total();
        $totalProductos = $this->dashboardService->getTotalProducts();
        $productosMostStock = $this->dashboardService->getMostStockProducts();
        $registros = $this->dashboardService->getRegistrosXEstados();
        $inventario = $this->dashboardService->getAllInventory()->sum('stock');
        $almacenes = $this->dashboardService->getAllInventory()->unique('idAlmacen')->pluck('Almacen');
        $colors = ['#ff5733','#33c6ff','#75e93c','#f4d84d'];
        $stock = array();

        foreach($almacenes as $almacen){
            $stock[] = ['almacen' => $almacen,'cantidad' => $this->dashboardService->getInventoryByAlmacen($almacen->idAlmacen)->sum('stock')];
        }

        if($request->query('query')){
            return response()->json([
                view('components.dashboard_content',['user' => $userModel,
                                                    'registros' => $registros,
                                                    'inventario' => $inventario,
                                                    'stock' => $stock,
                                                    'colors' => $colors,
                                                    'productos' => $totalProductos,
                                                    'stockMin' => $productosStockMin,
                                                    'productosMostStock' => $productosMostStock
                                                ])->render(),
            ]);
        }
        
        return view('dashboard',['user' => $userModel,
                                    'registros' => $registros,
                                    'inventario' => $inventario,
                                    'stock' => $stock,
                                    'colors' => $colors,
                                    'productos' => $totalProductos,
                                    'stockMin' => $productosStockMin,
                                    'productosMostStock' => $productosMostStock
                                ]);
    }

    public function dashboardInventario($estado,Request $request){
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();
        
        $tipo = $this->caseRegistro($estado);
        $registros = $tipo[0];
        $data = $tipo[1];

        if($request->query('page')){
            $view = view('components.lista_registros_dashboard', ['registros' => $registros,'data' => $data,'container' => $request->query('container')])->render();
            return response()->json(['html' => $view]);
        }

        return view('dashboard_inventario',['user' => $userModel,
                                            'registros' => $registros,
                                            'data' => $data]);
    }

    public function stockMinDashboard(){
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        $productos = $this->dashboardService->getStockMinProducts();
        return view('stockmin_dashboard',['user' => $userModel,
                                        'productos' => $productos,
                                        'tc' => $this->calculadoraService->getTasaCambio()]);
    }

    private function caseRegistro($estado){
        switch(decrypt($estado)){
            case 'NUEVO':
                $registros = $this->dashboardService->getNuevosInventario();
                $data = ['icon' => 'boxes','pestania' => 'Nuevos' , 'titulo' => 'Productos Nuevos','color' => 'bg-sistema-uno'];
                break;
            case 'ENTREGADO':
                $registros = $this->dashboardService->getEntregadosInventario();
                $data = ['icon' => 'cart','pestania' => 'Entregados' , 'titulo' => 'Productos Entregados','color' => 'bg-green'];
                break;
            case 'DEVOLUCION':
                $registros = $this->dashboardService->getDevolucionesInventario();
                $data = ['icon' => 'truck','pestania' => 'Devoluciones' , 'titulo' => 'Productos Devueltos','color' => 'bg-warning'];
                break;
            case 'ABIERTO':
                $registros = $this->dashboardService->getAbiertosInventario();
                $data = ['icon' => 'dropbox','pestania' => 'Abiertos' , 'titulo' => 'Productos Abiertos','color' => 'bg-purple'];
                break;
            case 'DEFECTUOSO':
                $registros = $this->dashboardService->getDefectuososInventario();
                $data = ['icon' => 'x-lg','pestania' => 'Defectuosos' , 'titulo' => 'Productos Defectuosos','color' => 'bg-danger'];
                break;
            case 'GARANTIA':
                $registros = $this->dashboardService->getGarantiaInventario();
                $data = ['icon' => 'award','pestania' => 'Garantías' , 'titulo' => 'Productos en Garantía','color' => 'bg-marron'];
                break;
            default:
                return back();
        }

        return $response = [$registros,$data];
    }
}