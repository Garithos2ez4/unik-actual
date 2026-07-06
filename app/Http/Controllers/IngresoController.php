<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\IngresoProductoServiceInterface;
use App\Services\UsuarioServiceInterface;
use App\Services\ComprobanteServiceInterface;
use App\Services\HeaderServiceInterface;
use App\Services\DivisionPackServiceInterface;
use App\Services\UnionPackServiceInterface;

class IngresoController extends Controller
{
    protected $userService;
    protected $ingresoService;
    protected $comprobanteService;
    protected $headerService;
    protected $divisionPackService;
    protected $unionPackService;

    public function __construct(HeaderServiceInterface $headerService,
                                UsuarioServiceInterface $userService,
                                IngresoProductoServiceInterface $ingresoService,
                                ComprobanteServiceInterface $comprobanteService,
                                DivisionPackServiceInterface $divisionPackService,
                                UnionPackServiceInterface $unionPackService)
    {
        $this->userService = $userService;
        $this->ingresoService = $ingresoService;
        $this->comprobanteService = $comprobanteService;
        $this->headerService = $headerService;
        $this->divisionPackService = $divisionPackService;
        $this->unionPackService = $unionPackService;
    }
    
    public function index($month,Request $request){
        $userModel = $this->headerService->getModelUser();
        
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 2){
                Carbon::setLocale('es');
                $fechacompleta = $month. '-01';
                $carbonMonth = Carbon::createFromFormat('Y-m-d', $fechacompleta);
                
                $registros = $this->ingresoService->getByMonth($month,250,$request->query('filtro'))->appends($request->all());

                if($request->query('page') || $request->query('filtro')){
                    $view = view('components.lista_ingresos', ['registros' => $registros,'container' => $request->query('container')])->render();
                    return response()->json(['html' => $view]);
                }
                
                $proveedores = $this->ingresoService->getAllLabelProveedor();
                
                $documentos = $this->ingresoService->getAllTipoComprobante();

                $almacenes = \App\Models\Almacen::with('Ubicaciones')->get();

                $estados = [['value' => 'NUEVO', 'name' => 'Nuevo'],
                    ['value' => 'ABIERTO', 'name' => 'Abierto'],
                    ['value' => 'DEFECTUOSO', 'name' => 'Defectuoso'],
                    ['value' => 'DEVOLUCION', 'name' => 'Devolución'],
                    ['value' => 'ENTREGADO', 'name' => 'Entregado'],
                    ['value' => 'GARANTIA', 'name' => 'Garantía']
                    ];
                
                $filtros = ['users' => $this->ingresoService->filtroUsuario($month),
                            'proveedores' => $this->ingresoService->filtroProveedor($month),
                            'almacenes' => $this->ingresoService->filtroAlmacen($month),
                            'estados' => $this->ingresoService->filtroEstado($month)];

                
                return view('ingresos.ingresos',['user' => $userModel,
                                        'registros' => $registros,
                                        'documentos' => $documentos,
                                        'proveedores' => $proveedores,
                                        'fecha' => $carbonMonth,
                                        'almacenes' => $almacenes,
                                        'estados' => $estados,
                                        'filtros' => $filtros
                                        ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return back();
    }
    
    public function insertIngreso(Request $request,$comprobante){
        $userModel = $this->headerService->getModelUser();
        $datacomprobante = $request->input('comprobante');
        $detalle = $request->input('detalle');

        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 8){
                if($datacomprobante){

                    $this->comprobanteService->updateComprobante(decrypt($comprobante),$datacomprobante,$detalle);
                    
                    return redirect(route('documentos',[now()->format('Y-m')]));
                }else{
                    
                    $this->headerService->sendFlashAlerts('Datos Faltantes','Revisa los campos','info','btn-warning');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }
    
    public function searchIngreso(Request $request){
        $query = $request->input('query');
    
        $results = $this->ingresoService->searchAjaxIngreso($query);
    
        return response()->json($results);
    }

    public function getOneIngreso(Request $request){
        $query = $request->input('query');
    
        $results = $this->ingresoService->getOneIngreso($query);
    
        return response()->json($results);
    }
    
    public function deleteIngreso(Request $request){
        $userModel = $this->headerService->getModelUser();
        $idIngreso = $request->input('idingreso');
        $userModel = $this->headerService->getModelUser();
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 8){
                $this->ingresoService->deleteIngreso($idIngreso);
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para realizar esta accion','warning','btn-danger');
        return back();
    }

    public function insertComprobante(Request $request){
        $userModel = $this->headerService->getModelUser();
        $proveedor = $request->input('proveedor');
        $tipoComprobante = $request->input('tipocomprobante');
        $numeroComprobante = $request->input('numerocomprobante');
        
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 8){
                if($proveedor && $tipoComprobante && $numeroComprobante){
                    $array = array();
                    $array['idProveedor'] = $proveedor;
                    $array['idTipoComprobante'] = $tipoComprobante;
                    $array['idUser'] = $userModel->idUser;
                    $array['numeroComprobante'] = $numeroComprobante;
                    $array['moneda'] = 'SOL';
                    $array['totalCompra'] = 0;
                    $array['fechaRegistro'] = now();
                    $array['estado'] = 'PENDIENTE';
                    
                    $operation = $this->comprobanteService->insertComprobante($array);
                    if($operation){
                        return redirect()->route('documento',[encrypt($operation),true]);
                    }else{
                        $this->headerService->sendFlashAlerts('Operacion Fallida','Ocurrio un error en la transaccion','error','btn-danger');
                        return back()->withInput();
                    }
                }else{
                    $this->headerService->sendFlashAlerts('Datos Repetidos','Ya se encuentran en la base de datos','info','btn-danger');
                    return back()->withInput();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }
    
    public function updateRegistro(Request $request){
        $userModel = $this->headerService->getModelUser();
        $idRegistro =  $request->input('idregistro');
        $estado =  $request->input('estado');
        $observacion =  $request->input('observacion');
        $ubicacion_especifica = $request->input('ubicacion_especifica');

        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 2){
                if(isset($idRegistro)){
                    $this->ingresoService->updateRegistro($idRegistro,$estado,$observacion,$ubicacion_especifica);
                }
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para realizar esta accion','warning','btn-danger');
        return back();
    }

    /**
     * AJAX: Verificar si un registro es un pack divisible
     */
    public function verificarPack(Request $request)
    {
        $idRegistro = $request->input('idRegistro');
        $result = $this->divisionPackService->verificarPackDivisible($idRegistro);
        return response()->json($result);
    }

    /**
     * POST: Dividir un pack en sus componentes
     */
    public function dividirPack(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 8) {
                try {
                    $idRegistro = $request->input('idRegistro');
                    $result = $this->divisionPackService->dividirPack($idRegistro);
                    
                    $this->headerService->sendFlashAlerts(
                        'Pack Dividido',
                        $result['message'],
                        'success',
                        'btn-success'
                    );
                    return back();
                } catch (\Exception $e) {
                    $this->headerService->sendFlashAlerts(
                        'Error al Dividir',
                        $e->getMessage(),
                        'error',
                        'btn-danger'
                    );
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta accion', 'warning', 'btn-danger');
        return back();
    }

    /**
     * POST: Reunir componentes en un pack
     */
    public function reunirPack(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 8) {
                try {
                    $idProductoPack = $request->input('idProductoPack');
                    $idRegistrosHijos = $request->input('idRegistrosHijos', []);
                    $result = $this->unionPackService->reunirPack($idProductoPack, $idRegistrosHijos);
                    
                    $this->headerService->sendFlashAlerts(
                        'Pack Reunido',
                        $result['message'],
                        'success',
                        'btn-success'
                    );
                    return back();
                } catch (\Exception $e) {
                    $this->headerService->sendFlashAlerts(
                        'Error al Reunir',
                        $e->getMessage(),
                        'error',
                        'btn-danger'
                    );
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta accion', 'warning', 'btn-danger');
        return back();
    }

    /**
     * AJAX: Obtener componentes disponibles para reunión
     */
    public function getComponentesReunion(Request $request)
    {
        $idProductoPack = $request->input('idProductoPack');
        $result = $this->unionPackService->getComponentesParaReunion($idProductoPack);
        return response()->json($result);
    }

    /**
     * AJAX: Lista de packs que se pueden armar con el stock actual
     */
    public function getPacksParaUnion(Request $request)
    {
        $result = $this->unionPackService->getPacksDisponiblesParaUnion();
        return response()->json($result);
    }

    /**
     * AJAX: Componentes requeridos + disponibles para un pack específico (con modelo)
     */
    public function getComponentesUnion(Request $request)
    {
        $idProductoPack = (int) $request->input('idProductoPack');
        $result = $this->unionPackService->getComponentesRequeridosParaUnion($idProductoPack);
        return response()->json($result);
    }

    /**
     * POST: Unir componentes individuales para formar un pack nuevo
     */
    public function unirComponentesEnPack(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 8) {
                try {
                    $idProductoPack   = (int) $request->input('idProductoPack');
                    $idRegistrosHijos = $request->input('idRegistrosHijos', []);
                    $idAlmacenDestino = (int) $request->input('idAlmacenDestino');

                    $result = $this->unionPackService->unirComponentesEnPack(
                        $idProductoPack,
                        array_map('intval', $idRegistrosHijos),
                        $idAlmacenDestino
                    );

                    $this->headerService->sendFlashAlerts(
                        'Pack Armado',
                        $result['message'],
                        'success',
                        'btn-success'
                    );
                    return back();
                } catch (\Exception $e) {
                    $this->headerService->sendFlashAlerts(
                        'Error al Armar Pack',
                        $e->getMessage(),
                        'error',
                        'btn-danger'
                    );
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta accion', 'warning', 'btn-danger');
        return back();
    }

    /**
     * AJAX: Buscar un registro de pack por su número de serie
     */
    public function buscarPackPorSerie(Request $request)
    {
        $serie = $request->input('serie');
        
        $registro = \App\Models\RegistroProducto::with(['DetalleComprobante.Producto'])
            ->where('numeroSerie', $serie)
            ->first();

        if (!$registro) {
            return response()->json(['success' => false, 'message' => 'No se encontró ningún producto con esta serie en el inventario.']);
        }

        if (!$registro->DetalleComprobante || !$registro->DetalleComprobante->Producto) {
            return response()->json(['success' => false, 'message' => 'No se encontró el producto asociado a esta serie.']);
        }

        $producto = $registro->DetalleComprobante->Producto;
        $estado = $registro->estado;

        $esPack = \App\Models\ProductoPack::where('idProductoPack', $producto->idProducto)->exists();

        if (!$esPack) {
            return response()->json(['success' => false, 'message' => 'El producto asociado a esta serie no es un pack.']);
        }

        if ($estado !== 'NUEVO') {
            return response()->json(['success' => false, 'message' => "El pack no puede ser dividido porque su estado actual es: $estado"]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'idRegistro' => $registro->idRegistro,
                'nombreProducto' => $producto->nombreProducto,
                'serie' => $registro->numeroSerie
            ]
        ]);
    }

    /**
     * AJAX: Sugerencias de series de packs
     */
    public function buscarSeriesPackAjax(Request $request)
    {
        $query = $request->input('query');
        if (strlen($query) < 3) {
            return response()->json([]);
        }

        $registros = \App\Models\RegistroProducto::with(['DetalleComprobante.Producto'])
            ->where('numeroSerie', 'like', "%$query%")
            ->where('estado', 'NUEVO')
            ->take(10)
            ->get();

        $resultados = [];
        foreach ($registros as $reg) {
            $prod = $reg->DetalleComprobante->Producto ?? null;
            if ($prod) {
                // Verificar si es pack
                $esPack = \App\Models\ProductoPack::where('idProductoPack', $prod->idProducto)->exists();
                if ($esPack) {
                    $resultados[] = [
                        'serie' => $reg->numeroSerie,
                        'nombreProducto' => $prod->nombreProducto
                    ];
                }
            }
        }

        return response()->json($resultados);
    }

    /**
     * AJAX: Buscar productos que pueden ser padres (packs) filtrados por grupos específicos.
     */
    public function buscarPadresPackAjax(Request $request)
    {
        $query = $request->input('query');
        if (empty($query)) return response()->json([]);

        // IDs de grupos permitidos: Cabezales, Tintas, Cartuchos...
        $gruposPermitidos = [124, 155, 156, 157, 158, 159, 44, 78, 79];

        $productos = \App\Models\Producto::whereIn('idGrupo', $gruposPermitidos)
            ->where(function ($q) use ($query) {
                $q->where('modelo', 'like', "%$query%")
                  ->orWhere('nombreProducto', 'like', "%$query%")
                  ->orWhere('codigoProducto', 'like', "%$query%")
                  ->orWhere('partNumber', 'like', "%$query%");
            })
            ->select('idProducto', 'nombreProducto', 'modelo', 'codigoProducto', 'partNumber')
            ->take(15)
            ->get();

        $resultados = [];
        foreach ($productos as $prod) {
            $resultados[] = [
                'idProducto' => $prod->idProducto,
                'nombreProducto' => $prod->nombreProducto,
                'modelo' => $prod->modelo,
                'codigo' => $prod->codigoProducto
            ];
        }

        return response()->json($resultados);
    }
}