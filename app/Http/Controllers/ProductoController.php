<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use App\Services\HeaderServiceInterface;
use App\Services\CalculadoraServiceInterface;
use App\Services\PreciosService;

use App\Services\ProductoServiceInterface;
use Exception;

class ProductoController extends Controller
{
    protected $headerService;
    protected $calculadoraService;
    protected $productoService;

    public function __construct(HeaderServiceInterface $headerService,
                                CalculadoraServiceInterface $calculadoraService,
                                ProductoServiceInterface $productoService)
    {
        $this->headerService = $headerService;
        $this->calculadoraService = $calculadoraService;
        $this->productoService = $productoService;
    }

    public function index($idCategory,$idGrupo,Request $request){
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        //variables del controlador
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 2){
                // Obtener datos comunes
                $productos = $this->productoService->getAllProductsByColumn('idGrupo',decrypt($idGrupo),15,$request->query('filtro'))->appends($request->all());
                $almacenes = $this->productoService->getAllAlmacen();

                // Si es petición AJAX (paginación o filtro)
                if($request->query('page') || $request->query('filtro')){
                    $view = view('components.lista_producto', [
                        'productos' => $productos,
                        'container' => $request->query('container'),
                        'almacenes' => $almacenes,
                        'tc' => $this->calculadoraService->getTasaCambio()
                    ])->render();
                    return response()->json(['html' => $view]);
                }

                // Carga inicial completa de la página
                $grupo = $this->productoService->getOneLabelGrupo(decrypt($idGrupo));
                $grupos = $this->productoService->getAllLabelGrupoXCategory(decrypt($idCategory));
                $categoria = $this->productoService->getOneLabelCategory(decrypt($idCategory));
                $categorias = $this->productoService->getAllLabelCategory();

                $filtros = [
                    'marcas' => $this->productoService->filtroMarcas('idGrupo',decrypt($idGrupo)),
                    'estados' => $this->productoService->filtroEstados('idGrupo',decrypt($idGrupo))
                ];

                return view('productos.productos',[
                    'user' => $userModel,
                    'grupos' => $grupos,
                    'categorias' => $categorias,
                    'grupo' => $grupo,
                    'productos' => $productos,
                    'categoria' => $categoria,
                    'almacenes' => $almacenes,
                    'tc' => $this->calculadoraService->getTasaCambio(),
                    'filtros' => $filtros
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }
    public function update($idProducto){
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        //variables del controlador
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 2){
                $producto = $this->productoService->getOneProductByColumn('idProducto',decrypt($idProducto));
                $marcas = $this->productoService->getAllLabelMarca();
                $proveedor = $this->productoService->getAllLabelProveedor();
                $grupos = $this->productoService->getAllLabelGrupo();
                $almacenes = $this->productoService->getAllAlmacen();

                return view('productos.producto',['user' => $userModel,
                                        'producto' => $producto,
                                        'marcas' => $marcas,
                                        'proveedor' => $proveedor,
                                        'grupos' => $grupos,
                                        'almacenes' => $almacenes,
                                        'tc' => $this->calculadoraService->getTasaCambio(),
                                        'igv' => $this->calculadoraService->getIgv(),
                                        'tasaFija' => $this->calculadoraService->getTasaFija()->tasaCambio
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }

    public function create(Request $request)
    {
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        //variables del controlador
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 2) {
                //llamamos a los services
                $marcas = $this->productoService->getAllLabelMarca();
                $grupos = $this->productoService->getAllLabelGrupo();
                $proveedor = $this->productoService->getAllLabelProveedor();
                $almacenes = $this->productoService->getAllAlmacen();
                $categorias = $this->productoService->getAllLabelCategory();
                $tipos = app(\App\Services\ConfiguracionServiceInterface::class)->getAllTipoProductos();

                $latestProductCodes = $this->productoService->getLastCodesProducts();

                $productoCopiar = null;
                if ($request->has('copy_from')) {
                    $productoCopiar = $this->productoService->getOneProductByColumn('idProducto', $request->copy_from);
                }

                return view('createproducto', [
                    'user' => $userModel,
                    'marcas' => $marcas,
                    'grupos' => $grupos,
                    'categorias' => $categorias,
                    'tipos' => $tipos,
                    'proveedor' => $proveedor,
                    'almacenes' => $almacenes,
                    'codigos' => $latestProductCodes,
                    'tc' => $this->calculadoraService->getTasaCambio(),
                    'igv' => $this->calculadoraService->getIgv(),
                    'tasaFija' => $this->calculadoraService->getTasaFija()->tasaCambio,
                    'productoCopiar' => $productoCopiar
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }

    public function details($idProducto){
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        //variables del controlador
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 2){
                $producto = $this->productoService->getOneProductByColumn('idProducto',$idProducto);
                $grupo = $this->productoService->getOneLabelGrupo($producto->idGrupo);
                $carGrupos = collect($producto->GrupoProducto->Caracteristicas_Grupo);
                $carProductos = collect($producto->Caracteristicas_Producto);
                $options = $carGrupos->filter(function ($carGrupo) use ($carProductos) {
                    return !$carProductos->contains(function ($carProducto) use ($carGrupo) {
                        return $carGrupo->idCaracteristica == $carProducto->idCaracteristica;
                        });
                    });
                return view('createdetails',['user' => $userModel,
                                            'producto'=>$producto,
                                            'grupo'=>$grupo,
                                            'options' => $options
                            ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }

    public function searchModelProduct(Request $request){
        $query = $request->input('query');

        $results = $this->productoService->searchAjaxProducts('modelo',$query);

        return response()->json($results);
    }

    public function searchProduct(Request $request){
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 2){
                //variables del controlador
                $input = $request->input('search');
                $productos = $this->productoService->searchProducts($input, 25, $request->query('filtro'));
                $almacenes = $this->productoService->getAllAlmacen();
                
                // Obtener marcas filtradas por búsqueda si hay término de búsqueda
                if($input) {
                    $marcasFiltradas = $this->productoService->getMarcasBySearch($input);
                } else {
                    $marcasFiltradas = $this->productoService->getAllLabelMarca();
                }

                if($request->query('page') || $request->query('filtro')){
                    // Obtener marcas filtradas también para la paginación AJAX
                    if($input) {
                        $marcasFiltradas = $this->productoService->getMarcasBySearch($input);
                    } else {
                        $marcasFiltradas = $this->productoService->getAllLabelMarca();
                    }
                    
                    $view = view('components.lista_producto', ['productos' => $productos,
                                                                'container' => $request->query('container'),
                                                                'almacenes' => $almacenes,
                                                                'marcas' => $marcasFiltradas,
                                                                'tc' => $this->calculadoraService->getTasaCambio()])->render();
                    return response()->json(['html' => $view]);
                }
                $filtros = [
                    'marcas' => $marcasFiltradas,
                    'estados' => Producto::select('estadoProductoWeb')->whereNotNull('estadoProductoWeb')->distinct()->get()
                ];

                return view('buscarproducto',['user' => $userModel,
                                                'productos' => $productos,
                                                'tc' => $this->calculadoraService->getTasaCambio(),
                                                'filtros' => $filtros,
                                                'almacenes' => $almacenes]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }

    public function createDetails(Request $request){
        $userModel = $this->headerService->getModelUser();
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 2){
                $arrayProduct = array();
                $arrayProveedor = array();

                $nombre = $request->input('name');
                $upc = $request->input('upc');
                $modelo = $request->input('modelo');
                $partnumber = $request->input('partnumber');
                $garantia = $request->input('garantia');
                $marca = $request->input('marca');
                $grupo = $request->input('grupo');
                $estado = $request->input('estado');
                $stockproveedor = $request->input('stockproveedor');
                $proveedor = $request->input('proveedor');
                $descripcion = $request->input('desc');
                $codigo = $request->input('codigo');
                $tipoprecio = $request->input('tipoprecio');
                $stockminimo = $request->input('stockminimo');
                $video1 = $request->input('video1');
                $video2 = $request->input('video2');
                $tc_fijo = $request->input('tc_fijo');

                 // Obtener ID de videos
                $videoId1 = $this->productoService->getYoutubeVideoId($video1);
                $videoId2 = $this->productoService->getYoutubeVideoId($video2);

                
                if(!empty($tipoprecio)){
                    $inputPrecio = $request->input('precio') ?? 0;
                    $inputGanancia = $request->input('ganancia') ?? 0;

                    if($tipoprecio == 'SOL'){
                        $precio = $inputPrecio / $this->calculadoraService->getTasaCambio();
                        $ganancia = $inputGanancia / $this->calculadoraService->getTasaCambio();

                    }else{
                        $precio = $inputPrecio;
                        $ganancia = $inputGanancia;
                    }
                }else{
                    $precio = 0;
                    $ganancia = 0;
                }

                if(isset($nombre, $upc, $modelo, $partnumber)){
                    $validateNombre = $this->productoService->getOneProductByColumn('nombreProducto',$nombre);
                    $validateUpc = $this->productoService->getOneProductByColumn('UPC',$upc);
                    $validateModelo = $this->productoService->existsByModelo($modelo);
                    $validatePartNumber = $this->productoService->getOneProductByColumn('partNumber',$partnumber);
                }else{
                    $this->headerService->sendFlashAlerts('Error en los datos','Revisa los campos enviados','error','btn-danger');
                        return back()->withInput();
                }

                $switchupc = false;

                if($upc == 0){
                    $switchupc = true;
                }else{
                    if(empty($validateUpc)){
                        $switchupc = true;
                    }else{
                        $switchupc = false;
                    }
                }

                if($partnumber == 0){
                    $switchPartNumber = true;
                }else{
                    if(empty($validatePartNumber)){
                        $switchPartNumber = true;
                    }else{
                        $switchPartNumber = false;
                    }
                }

                if(!empty($codigo)){
                    if(!empty($validateNombre)){
                        $this->headerService->sendFlashAlerts('Titulo existente','Ya se encuentra registrado','info','btn-warning');
                        return back()->withInput();
                    }else if(!$switchupc){
                        $this->headerService->sendFlashAlerts('UPC existente','Ya se encuentra registrado','info','btn-warning');
                        return back()->withInput();
                    }else if(!empty($validateModelo)){
                        $this->headerService->sendFlashAlerts('Modelo existente','Ya se encuentra registrado','info','btn-warning');
                        return back()->withInput();
                    }else if(!$switchPartNumber){
                        $this->headerService->sendFlashAlerts('Part number existente','Ya se encuentra registrado','info','btn-warning');
                        return back()->withInput();
                    }else{
                        $idProducto = 0;
                        try{
                            try{
                                $img1 = null;
                                $img2 = null;
                                $img3 = null;
                                $img4 = null;

                                if ($request->hasFile('imgone')) {
                                    $img1 = $request->file('imgone');
                                }

                                if ($request->hasFile('imgtwo')) {
                                    $img2 = $request->file('imgtwo');
                                }

                                if ($request->hasFile('imgtree')) {
                                    $img3 = $request->file('imgtree');
                                }

                                if ($request->hasFile('imgfour')) {
                                    $img4 = $request->file('imgfour');
                                }

                                $arrayProduct['nombreProducto'] = $nombre;
                                $arrayProduct['codigoProducto'] = $codigo;
                                $arrayProduct['UPC'] = $upc;
                                $arrayProduct['partNumber'] = $partnumber;
                                $arrayProduct['idMarca'] = $marca;
                                $arrayProduct['idGrupo'] = $grupo;
                                $arrayProduct['modelo'] = $modelo;
                                $arrayProduct['precioDolar'] = $precio;
                                $arrayProduct['gananciaExtra'] = $ganancia;
                                $arrayProduct['garantia'] = $garantia;
                                $arrayProduct['descripcionProducto'] = $descripcion;
                                $arrayProduct['estadoProductoWeb'] = $estado;
                                $arrayProduct['stockMin'] = $stockminimo;
                                //Usar tasa de cambio
                                $arrayProduct['usar_tc_fijo'] = $request->boolean('usar_tc_fijo');
                                $arrayProduct['tc_fijo'] = $tc_fijo;

                                //Videos
                                $arrayProduct['video1_url'] = $videoId1;
                                $arrayProduct['video2_url'] = $videoId2;
                                


                                $arrayProveedor['stock'] = $stockproveedor;
                                $arrayProveedor['idProveedor'] = $proveedor;

                                $success = $this->productoService->insertProduct($arrayProduct,$arrayProveedor,$img1,$img2,$img3,$img4);
                                $idProducto = $success;

                                $this->productoService->validateState($idProducto);

                            }catch(Exception $e){
                                \Log::error('Error en insert/validate: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
                                $this->headerService->sendFlashAlerts('Error en la operacion','Hubo un error en la transaccion','error','btn-danger');
                                return back()->withInput();
                            }

                            return redirect()->route('details',['idProducto' => $idProducto]);

                        }catch(Exception $e){
                            \Log::error('Error general createDetails: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
                            $this->headerService->sendFlashAlerts('Error en la operacion','Hubo un error en la transaccion','error','btn-danger');
                            return back()->withInput();
                        }
                    }
                }else{
                    $this->headerService->sendFlashAlerts('Generacion Fallida','Hubo un error en la generacion del codigo','error','btn-danger');
                    return back()->withInput();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }

    public function updateProduct($idProducto,Request $request){
        $userModel = $this->headerService->getModelUser();
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 2){
                $arrayProduct = array();
                $titulo = $request->input('titulo');
                $marca = $request->input('marca');
                $precio = 0;
                $estado = $request->input('estado');
                $garantia = $request->input('garantia');
                $upc = $request->input('upc');
                $modelo = $request->input('modelo');
                $partnumber = $request->input('partnumber');
                $stock = $request->input('stock');
                $stockproveedor = $request->input('stockproveedor');
                $proveedor = $request->input('proveedor');
                $descripcion = $request->input('descripcion');
                $tipoprecio = $request->input('tipoprecio');
                $stockminimo = $request->input('stockminimo');
                //videos
                $video1 = $request->input('videoUrl1');
                $video2 = $request->input('videoUrl2');

                try{
                    // Solo actualizar si el campo fue enviado (no está disabled)
                    if($request->has('titulo')){
                        $arrayProduct['nombreProducto'] = $titulo;
                    }
                    if($request->has('tipoprecio') && !empty($tipoprecio)){
                        if($tipoprecio == 'SOL'){
                            $precio = $request->input('precio') / $this->calculadoraService->getTasaCambio();
                            $ganancia = $request->input('ganancia')/ $this->calculadoraService->getTasaCambio();
                        }else{
                            $precio = $request->input('precio');
                            $ganancia = $request->input('ganancia');
                        }
                    }else{
                        $precio = null;
                    }

                    if ($request->has('ganancia') && !is_null($ganancia)) {
                        $arrayProduct['gananciaExtra']= $ganancia;
                    }

                    if ($request->has('precio') && !is_null($precio)) {
                        $arrayProduct['precioDolar'] = $precio;
                    }

                    if($request->has('garantia')){
                        $arrayProduct['garantia'] = $garantia;
                    }

                    if($request->has('upc')){
                        $arrayProduct['UPC'] = $upc;
                    }

                    if($request->has('modelo')){
                        if(!empty($modelo) && $this->productoService->existsByModelo($modelo, decrypt($idProducto))){
                            $this->headerService->sendFlashAlerts('Modelo existente','Ya se encuentra registrado con otro producto','info','btn-warning');
                            return redirect()->back();
                        }
                        $arrayProduct['modelo'] = $modelo;
                    }

                    if($request->has('partnumber')){
                        $arrayProduct['partNumber'] = $partnumber;
                    }

                    if($request->has('descripcion')){
                        $arrayProduct['descripcionProducto'] = $descripcion;
                    }

                    if($request->has('estado')){
                        $arrayProduct['estadoProductoWeb'] = $estado;
                    }

                    if($request->has('marca')){
                        $arrayProduct['idMarca'] = $marca;
                    }

                    if($request->has('stockminimo')){
                        $arrayProduct['stockMin'] = $stockminimo;
                    }
                    
                    $arrayProduct['usar_tc_fijo'] = $request->boolean('usar_tc_fijo');   
                    
                    if($request->has('tc_fijo')){
                        $arrayProduct['tc_fijo'] = $request->input('tc_fijo');
                    }

                    // Procesar URLs de YouTube y guardar solo el ID
                    // Solo se actualiza si el usuario envió un valor nuevo.
                    // Si el campo viene vacío, se mantiene el valor existente en BD.
                    if (!empty($video1)) {
                        $videoId1 = $this->productoService->getYoutubeVideoId($video1);
                        $arrayProduct['videoUrl1'] = $videoId1 ?: null;
                    }

                    if (!empty($video2)) {
                        $videoId2 = $this->productoService->getYoutubeVideoId($video2);
                        $arrayProduct['videoUrl2'] = $videoId2 ?: null;
                    }

                    $this->productoService->updateProduct(decrypt($idProducto),$arrayProduct,$request->file('imgone'),$request->file('imgtwo'),$request->file('imgtree'),$request->file('imgfour'));

                    if (!is_null($stock)){
                        $this->productoService->updateInventory(decrypt($idProducto),$stock);
                    }

                    if (!is_null($proveedor) && !is_null($stockproveedor)) {
                        $arraySeguimiento = array();
                        $arraySeguimiento['idProveedor'] = $proveedor;
                        $arraySeguimiento['stock'] = $stockproveedor;

                        $this->productoService->updateSeguimiento(decrypt($idProducto),$arraySeguimiento);
                    }

                    $this->productoService->validateState(decrypt($idProducto));

                    return redirect()->back();

                }catch(Exception $e){
                    \Log::error('Error en updateProduct: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
                    $this->headerService->sendFlashAlerts('Error en la operacion','Hubo un error valida peus hijo ','error','btn-danger');
                    return redirect()->back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }

    public function insertOrUpdateDetails(Request $request){
        $userModel = $this->headerService->getModelUser();
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 2){
                $idProducto = $request->input('idproducto');
                $updateCaracteristicas = $request->input('updatecaracteristicas', []);
                $insertCaracteristicas = $request->input('insertcaracteristicas', []);
                $proba = false;
                try{
                    $this->productoService->insertOrUpdateCaracteristicas($idProducto,$insertCaracteristicas,$updateCaracteristicas);
                    $proba = true;
                }catch(Exception $e){
                    $proba = false;
                }

                if(!$proba){$this->headerService->sendFlashAlerts('Error en la operacion','Hubo un error en la transaccion','error','btn-danger');}

                return redirect()->back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }

    public function deleteDetail($idProducto,Request $request){
        $idCaracteristica = $request->input('idcaracteristica');
        $userModel = $this->headerService->getModelUser();
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 2){
                if(isset($idCaracteristica)){
                    $this->productoService->deleteCaracteristicaXProduct(decrypt($idProducto),$idCaracteristica);
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }

    public function calculate(Request $request)
    {
        $precio = $request->input('price');
        $moneda = $request->input('type');
        $grupo = $request->input('idGrupo');
        $estado = $request->input('state');
        $ganancia = $request->input('ganancia');

        $servicePrecio = new PreciosService;
        $precios = array();
        $precios[] = ['calculado' => $servicePrecio->getPrecioCalculado($precio,$grupo,$moneda,$estado)];
        $precios[] = ['total' => $servicePrecio->getPrecioTotal($precio,$grupo,$moneda,$estado,$ganancia)];
        $results = $precios;

        return response()->json($results);
    }

    public function quickCreateGrupo(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 2 || $acceso->idVista == 7) {
                $categoria = $request->input('categoria');
                $grupo = $request->input('grupo');
                $img = $request->file('img');
                $tipo = $request->input('tipo');

                if (empty($categoria) || empty($grupo) || empty($tipo) || empty($img)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Todos los campos son obligatorios, incluyendo la imagen.'
                    ], 400);
                }

                try {
                    $configService = app(\App\Services\ConfiguracionServiceInterface::class);
                    $configService->createGrupoProducto($categoria, $grupo, $tipo, $img);

                    $newGrupo = \App\Models\GrupoProducto::orderBy('idGrupoProducto', 'desc')->first();

                    if ($newGrupo) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Grupo creado correctamente.',
                            'grupo' => [
                                'idGrupoProducto' => $newGrupo->idGrupoProducto,
                                'nombreGrupo' => $newGrupo->nombreGrupo,
                                'idCategoria' => $newGrupo->idCategoria,
                                'nombreCategoria' => optional($newGrupo->CategoriaProducto)->nombreCategoria ?? ''
                            ]
                        ]);
                    }

                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudo obtener el grupo recién creado.'
                    ], 500);

                } catch (Exception $e) {
                    \Log::error('Error in quickCreateGrupo: ' . $e->getMessage());
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al crear el grupo: ' . $e->getMessage()
                    ], 500);
                }
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Acceso denegado.'
        ], 403);
    }

    // --- MÉTODOS PARA CONFIGURACIÓN DE PACKS ---

    public function getPackComponents($idProducto)
    {
        $idPadre = decrypt($idProducto);
        $components = \App\Models\ProductoPack::with('ProductoHijo')
            ->where('idProductoPack', $idPadre)
            ->get();
            
        $formatted = $components->map(function($comp) {
            return [
                'idProductoHijo' => $comp->idProductoHijo,
                'nombreProducto' => $comp->ProductoHijo->nombreProducto ?? 'Producto Desconocido',
                'cantidad' => $comp->cantidad,
                'porcentaje_costo' => $comp->porcentaje_costo
            ];
        });

        return response()->json(['success' => true, 'components' => $formatted]);
    }

    public function addPackComponent(Request $request, $idProducto)
    {
        try {
            $idPadre = decrypt($idProducto);
            $idHijo = $request->input('idHijo');
            $cantidad = $request->input('cantidad', 1);
            $porcentajeCosto = $request->input('porcentaje_costo', 0);

            if (!$idHijo || $cantidad < 1 || $porcentajeCosto < 0) {
                return response()->json(['success' => false, 'message' => 'Datos inválidos.']);
            }

            if ($idHijo == $idPadre) {
                return response()->json(['success' => false, 'message' => 'Un producto no puede ser componente de sí mismo.']);
            }

            $exists = \App\Models\ProductoPack::where('idProductoPack', $idPadre)
                ->where('idProductoHijo', $idHijo)
                ->first();

            if ($exists) {
                $exists->cantidad += $cantidad;
                $exists->porcentaje_costo = $porcentajeCosto;
                $exists->save();
            } else {
                \App\Models\ProductoPack::create([
                    'idProductoPack' => $idPadre,
                    'idProductoHijo' => $idHijo,
                    'cantidad' => $cantidad,
                    'porcentaje_costo' => $porcentajeCosto
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Componente agregado correctamente.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al agregar componente: ' . $e->getMessage()]);
        }
    }

    public function removePackComponent($idProducto, $idHijo)
    {
        try {
            $idPadre = decrypt($idProducto);
            \App\Models\ProductoPack::where('idProductoPack', $idPadre)
                ->where('idProductoHijo', $idHijo)
                ->delete();

            return response()->json(['success' => true, 'message' => 'Componente removido correctamente.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al remover componente: ' . $e->getMessage()]);
        }
    }

    // --- MÉTODOS PARA HERRAMIENTAS DE SERVICIO (RESETEADORES) ---

    public function getSeriesHerramienta($idProducto)
    {
        try {
            // El ID enviado desde openServiciosModal({{ $producto->idProducto }}) viene sin encriptar.
            $idProd = $idProducto;
            
            // Buscar todas las series (RegistroProducto) asociadas a este producto, excluyendo las entregadas
            $series = \App\Models\RegistroProducto::whereHas('DetalleComprobante', function($q) use ($idProd) {
                $q->where('idProducto', $idProd);
            })->where('estado', '!=', 'ENTREGADO')->with(['Almacen'])->get();

            $formatted = $series->map(function($serie) {
                return [
                    'idRegistro' => $serie->idRegistro,
                    'numeroSerie' => $serie->numeroSerie,
                    'estado' => $serie->estado,
                    'almacen' => $serie->Almacen->descripcion ?? 'Desconocido',
                    'es_herramienta' => (bool)$serie->es_herramienta
                ];
            });

            return response()->json(['success' => true, 'series' => $formatted]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al obtener series: ' . $e->getMessage()]);
        }
    }

    public function toggleHerramienta(Request $request)
    {
        try {
            $idRegistro = $request->input('idRegistro');
            $es_herramienta = $request->boolean('es_herramienta');

            if (!$idRegistro) {
                return response()->json(['success' => false, 'message' => 'ID de registro no proporcionado.']);
            }

            $registro = \App\Models\RegistroProducto::find($idRegistro);
            
            if (!$registro) {
                return response()->json(['success' => false, 'message' => 'Serie no encontrada.']);
            }

            $registro->es_herramienta = $es_herramienta;
            $registro->save();

            return response()->json(['success' => true, 'message' => 'Estado de herramienta actualizado correctamente.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar serie: ' . $e->getMessage()]);
        }
    }
}
