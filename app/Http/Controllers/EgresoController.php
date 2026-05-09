<?php

namespace App\Http\Controllers;

use App\Services\EgresoProductoServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\HeaderServiceInterface;
use App\Services\ProductoServiceInterface;
use Exception;

class EgresoController extends Controller
{
    protected $headerService;
    protected $egresoService;
    protected $productoService;
    
    public function __construct(HeaderServiceInterface $headerService,
                                EgresoProductoServiceInterface $egresoService,
                                ProductoServiceInterface $productoService)
    {
        $this->headerService = $headerService;
        $this->egresoService = $egresoService;
        $this->productoService = $productoService;
    }
    
    public function index($month){
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();
        
        //variables propias del controlador
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 2){
                Carbon::setLocale('es');
                $carbonMonth = Carbon::createFromFormat('Y-m', $month);
                $egresos = $this->egresoService->getEgresosByMonth($month,150);
                $almacenes = $this->egresoService->getAllAlmacenes();
                
                return view('egresos',['user' => $userModel,
                                        'egresos' => $egresos,
                                        'almacenes' => $almacenes,
                                        'fecha' => $carbonMonth]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }

    public function create(){
        $userModel = $this->headerService->getModelUser();
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 9){
                return view('createegreso',['user' => $userModel]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }
    
    public function insertEgreso(Request $request){
        $userModel = $this->headerService->getModelUser();
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 9){
                $sku = $request->input('sku');
                $idpublicacion = $request->input('idpublicacion');
                $numeroorden = $request->input('numeroorden');
                $fechapedido = $request->input('fechapedido');
                $fechadespacho = $request->input('fechadespacho');
                $registros = $request->input('idregistros');
                
                if(count($registros) < 1){
                    $this->headerService->sendFlashAlerts('Error en el formulario','Verifica que las series esten correctas existan','info','btn-warning');
                    return back();
                }

                $validatePublicacion = '';
                if(is_null($idpublicacion)){
                    $modelPublicacion = $this->egresoService->getPublicacion($sku);
                    $validatePublicacion = $modelPublicacion == null ? null : $modelPublicacion->idPublicacion;
                }else{
                    $validatePublicacion = $idpublicacion;
                }
                
                if(!is_null($validatePublicacion) && !is_null($fechapedido) && !is_null($fechadespacho)){
                        $arrayEgreso = ['idPublicacion' => $validatePublicacion == 'NULO' ? null : $validatePublicacion,
                                        'numeroOrden' => $validatePublicacion == 'NULO' ? null : $numeroorden,
                                        'fechaCompra' => $fechapedido,
                                        'fechaDespacho' => $fechadespacho];
                        
                        try {
                            $productos = $this->egresoService->createEgreso($arrayEgreso,$registros);
                            foreach($productos as $producto){
                                $this->productoService->validateState($producto->idProducto);
                            }
                            $this->headerService->sendFlashAlerts('Egreso registrado','Operacion exitosa','success','btn-success');
                            return back();
                        } catch (\Exception $e) {
                            $this->headerService->sendFlashAlerts('Error al registrar egreso', $e->getMessage(), 'error', 'btn-danger');
                            return back();
                        }
                        
                }else{
                    $this->headerService->sendFlashAlerts('Datos incompletos','Verifica que los datos ingresados existan','info','btn-warning');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }

    public function devolucionEgreso(Request $request){
        $userModel = $this->headerService->getModelUser();
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 9){
                $transaccion = $request->input('transaccion');
                $idegreso = $request->input('idegreso');
                $observacion = $request->input('observacion');
                $fechaDevolucion = $request->input('fecha_devolucion');
                $sku = $request->input('sku');
                $nro_orden = $request->input('nro_orden');
                $fecha_compra = $request->input('fecha_compra');
                $fecha_despacho = $request->input('fecha_despacho');

                if(isset($transaccion) && isset($idegreso)){
                    $dataEgreso = [
                        'sku' => $sku,
                        'numeroOrden' => $nro_orden,
                        'fechaCompra' => $fecha_compra,
                        'fechaDespacho' => $fecha_despacho
                    ];
                    $this->egresoService->updateEgreso($transaccion,$idegreso,$observacion, null, $fechaDevolucion, $dataEgreso);

                    $this->headerService->sendFlashAlerts('Operacion exitosa','No hubo ningún error en la operacion','success','btn-success');
                    return back();
                }else{
                    $this->headerService->sendFlashAlerts('Datos incompletos','Verifica que los datos ingresados existan','info','btn-warning');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }
    
    public function searchRegistro(Request $request){
        $query = $request->input('query');
        $results = $this->egresoService->searchAjaxRegistro($query);
    
        return response()->json($results);
    }

    public function getOneRegistro(Request $request){
        $query = $request->input('query');
        $results = $this->egresoService->getOneAjaxRegistro($query);
    
        return response()->json($results);
    }

    public function searchEgreso(Request $request){
        $query = $request->input('query');
        $results = $this->egresoService->searchAjaxEgreso($query,5);
    
        return response()->json($results);
    }
    
}