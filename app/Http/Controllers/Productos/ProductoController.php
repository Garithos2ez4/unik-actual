<?php

namespace App\Http\Controllers\Productos;
use App\Http\Controllers\Controller;

use App\Models\Catalogo\Producto;
use Illuminate\Http\Request;
use App\Services\HeaderServiceInterface;
use App\Services\CalculadoraServiceInterface;

use App\Services\ProductoServiceInterface;
use Exception;

class ProductoController extends Controller
{
    protected $headerService;
    protected $calculadoraService;
    protected $productoService;

    public function __construct(
        HeaderServiceInterface $headerService,
        CalculadoraServiceInterface $calculadoraService,
        ProductoServiceInterface $productoService
    ) {
        $this->headerService = $headerService;
        $this->calculadoraService = $calculadoraService;
        $this->productoService = $productoService;
    }

    public function index($idCategory, $idGrupo, Request $request)
    {
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        //variables del controlador
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 2) {
                // Obtener datos comunes
                $productos = $this->productoService->getAllProductsByColumn('idGrupo', decrypt($idGrupo), 15, $request->query('filtro'))->appends($request->all());
                $almacenes = \App\Models\Inventario\Almacen::with('Ubicaciones')->get();

                // Si es petición AJAX (paginación o filtro)
                if ($request->query('page') || $request->query('filtro')) {
                    $view = view('components.lista_producto', [
                        'productos' => $productos,
                        'container' => $request->query('container'),
                        'almacenes' => $almacenes,
                        'tc' => $this->calculadoraService->getTasaCambio()
                    ])->render();

                    // Limpiar posibles caracteres UTF-8 mal formados de la base de datos
                    $view = mb_convert_encoding($view, 'UTF-8', 'UTF-8');

                    return response()->json(['html' => $view]);
                }

                // Carga inicial completa de la página
                $grupo = $this->productoService->getOneLabelGrupo(decrypt($idGrupo));
                $grupos = $this->productoService->getAllLabelGrupoXCategory(decrypt($idCategory));
                $categoria = $this->productoService->getOneLabelCategory(decrypt($idCategory));
                $categorias = $this->productoService->getAllLabelCategory();

                $filtros = [
                    'marcas' => $this->productoService->filtroMarcas('idGrupo', decrypt($idGrupo)),
                    'estados' => $this->productoService->filtroEstados('idGrupo', decrypt($idGrupo))
                ];



                return view('productos.productos', [
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
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }
    public function update($idProducto)
    {
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        //variables del controlador
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 2) {
                $producto = $this->productoService->getOneProductByColumn('idProducto', $this->resolverIdProducto($idProducto));
                $marcas = $this->productoService->getAllLabelMarca();
                $proveedor = $this->productoService->getAllLabelProveedor();
                $grupos = $this->productoService->getAllLabelGrupo();
                $almacenes = \App\Models\Inventario\Almacen::with('Ubicaciones')->get();

                $tcSunat = (float)$this->calculadoraService->getTasaCambio();

                return view('productos.producto', [
                    'user' => $userModel,
                    'producto' => $producto,
                    'marcas' => $marcas,
                    'proveedor' => $proveedor,
                    'grupos' => $grupos,
                    'almacenes' => $almacenes,
                    'tc' => $tcSunat,
                    'igv' => $this->calculadoraService->getIgv(),
                    'tasaFija' => $this->calculadoraService->getTasaFija()->tasaCambio
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
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
                $almacenes = \App\Models\Inventario\Almacen::with('Ubicaciones')->get();
                $categorias = $this->productoService->getAllLabelCategory();
                $tipos = app(\App\Services\ConfiguracionServiceInterface::class)->getAllTipoProductos();

                $latestProductCodes = $this->productoService->getLastCodesProducts();

                $productoCopiar = null;
                if ($request->has('copy_from')) {
                    $productoCopiar = $this->productoService->getOneProductByColumn('idProducto', $request->copy_from);
                }

                $deltronData = null;
                $deltronMarcaId = null;
                if ($request->has('from_deltron')) {
                    $deltronSku = trim($request->input('from_deltron'));
                    if (!empty($deltronSku)) {
                        try {
                            $deltronService = app(\App\Services\Deltron\DeltronApiService::class);
                            $deltronData = $deltronService->getOrFetchFichaSku($deltronSku);
                            if ($deltronData) {
                                if (empty($deltronData['descripcion_completa'])) {
                                    $descBase = \App\Services\Deltron\DeltronApiService::cleanDeltronText($deltronData['descripcion_larga'] ?? ($deltronData['descripcion_corta'] ?? ''));
                                    $specsBase = \App\Services\Deltron\DeltronApiService::parseSpecsHtmlToText($deltronData['specs_html'] ?? '');
                                    if (!empty($descBase) && !empty($specsBase)) {
                                        $deltronData['descripcion_completa'] = $descBase . "\n\n" . $specsBase;
                                    } elseif (!empty($specsBase)) {
                                        $deltronData['descripcion_completa'] = $specsBase;
                                    } else {
                                        $deltronData['descripcion_completa'] = $descBase;
                                    }
                                }

                                if (empty($deltronData['modelo_real'])) {
                                    $deltronData['modelo_real'] = \App\Services\Deltron\DeltronApiService::extraerModeloRealDesdeSpecs($deltronData['specs_html'] ?? '');
                                }

                                if (!empty($deltronData['brand_name'])) {
                                    $brandSearch = trim($deltronData['brand_name']);
                                    $matchedMarca = \App\Models\Catalogo\MarcaProducto::where('nombreMarca', 'LIKE', "%{$brandSearch}%")->first();
                                    if ($matchedMarca) {
                                        $deltronMarcaId = $matchedMarca->idMarca;
                                    }
                                }

                                // Extraer imagenes de Deltron (800x800)
                                $deltronImages = [];
                                if (!empty($deltronData['images']['carrusel']) && is_array($deltronData['images']['carrusel'])) {
                                    foreach ($deltronData['images']['carrusel'] as $imgItem) {
                                        $imgUrl = is_array($imgItem) ? ($imgItem['url'] ?? '') : (is_string($imgItem) ? $imgItem : '');
                                        if ($imgUrl && !str_contains($imgUrl, 'blanco.jpg')) {
                                            $deltronImages[] = $imgUrl;
                                        }
                                    }
                                }
                                if (empty($deltronImages) && !empty($deltronData['images']['listado'])) {
                                    $listadoUrl = $deltronData['images']['listado'];
                                    if (!str_contains($listadoUrl, 'blanco.jpg')) {
                                        $deltronImages[] = $listadoUrl;
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            \Log::warning('No se pudo cargar ficha Deltron en create: ' . $e->getMessage());
                        }
                    }
                }

                return view('productos.partials.createproducto', [
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
                    'productoCopiar' => $productoCopiar,
                    'deltronData' => $deltronData,
                    'deltronMarcaId' => $deltronMarcaId,
                    'deltronImages' => $deltronImages ?? []
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function toggleStatus(Request $request)
    {
        $request->validate([
            'idProducto' => 'required|integer',
            'status' => 'required|boolean'
        ]);

        $producto = \App\Models\Catalogo\Producto::with(['Inventario', 'Inventario_Proveedor'])->find($request->idProducto);
        if (!$producto) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado'], 404);
        }

        if ($request->status) {
            $nuevoEstado = 'DISPONIBLE';
            if ($producto->Inventario_Proveedor && $producto->Inventario_Proveedor->stock == 0) {
                $producto->Inventario_Proveedor->stock = 1;
                $producto->Inventario_Proveedor->save();
            }
        } else {
            // Verificar si tiene stock fisico o de proveedor
            $stockFisico = $producto->Inventario ? $producto->Inventario->sum('stock') : 0;
            $stockProv = $producto->Inventario_Proveedor ? $producto->Inventario_Proveedor->stock : 0;
            $tieneStock = ($stockFisico + $stockProv) > 0;

            // Si tiene stock pero el usuario lo desactiva, pasa a DESCONTINUADO
            // Si no tiene stock (esta en 0), pasa a AGOTADO
            $nuevoEstado = $tieneStock ? 'DESCONTINUADO' : 'AGOTADO';
        }

        $producto->estadoProductoWeb = $nuevoEstado;
        $producto->save();
        $producto->invalidarCacheWeb();

        return response()->json([
            'success' => true,
            'estado' => $nuevoEstado,
            'stock_proveedor' => $producto->Inventario_Proveedor ? $producto->Inventario_Proveedor->stock : null,
            'message' => 'El estado se ha actualizado a ' . $nuevoEstado
        ]);
    }



    public function details($idProducto)
    {
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        //variables del controlador
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 2) {
                $producto = $this->productoService->getOneProductByColumn('idProducto', $idProducto);
                $grupo = $this->productoService->getOneLabelGrupo($producto->idGrupo);
                $carGrupos = collect($producto->GrupoProducto->Caracteristicas_Grupo);
                $carProductos = collect($producto->Caracteristicas_Producto);
                $options = $carGrupos->filter(function ($carGrupo) use ($carProductos) {
                    return !$carProductos->contains(function ($carProducto) use ($carGrupo) {
                        return $carGrupo->idCaracteristica == $carProducto->idCaracteristica;
                    });
                });
                return view('createdetails', [
                    'user' => $userModel,
                    'producto' => $producto,
                    'grupo' => $grupo,
                    'options' => $options
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function searchModelProduct(Request $request)
    {
        $query = $request->input('query');
        if (empty($query)) {
            return response()->json([]);
        }

        $queryBuilder = Producto::query()
            ->where(function ($q) use ($query) {
                $q->where('modelo', 'LIKE', '%' . $query . '%')
                  ->orWhere('nombreProducto', 'LIKE', '%' . $query . '%')
                  ->orWhere('codigoProducto', 'LIKE', '%' . $query . '%')
                  ->orWhere('partNumber', 'LIKE', '%' . $query . '%');
            });

        if ($request->boolean('vendibles', false) || $request->boolean('no_padre', false)) {
            $queryBuilder->where('es_padre', 0);
        }

        if ($request->boolean('solo_padres', false)) {
            $queryBuilder->where('es_padre', 1);
        }

        $results = $queryBuilder->take(20)->get();

        return response()->json($results);
    }

    public function searchProduct(Request $request)
    {
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 2) {
                //variables del controlador
                $input = $request->input('search');
                $productos = $this->productoService->searchProducts($input, 25, $request->query('filtro'));
                $almacenes = \App\Models\Inventario\Almacen::with('Ubicaciones')->get();

                // Obtener marcas filtradas por búsqueda si hay término de búsqueda
                if ($input) {
                    $marcasFiltradas = $this->productoService->getMarcasBySearch($input);
                } else {
                    $marcasFiltradas = $this->productoService->getAllLabelMarca();
                }

                if ($request->query('page') || $request->query('filtro')) {
                    // Obtener marcas filtradas también para la paginación AJAX
                    if ($input) {
                        $marcasFiltradas = $this->productoService->getMarcasBySearch($input);
                    } else {
                        $marcasFiltradas = $this->productoService->getAllLabelMarca();
                    }

                    $view = view('components.lista_producto', [
                        'productos' => $productos,
                        'container' => $request->query('container'),
                        'almacenes' => $almacenes,
                        'marcas' => $marcasFiltradas,
                        'tc' => $this->calculadoraService->getTasaCambio()
                    ])->render();

                    $view = mb_convert_encoding($view, 'UTF-8', 'UTF-8');
                    return response()->json(['html' => $view]);
                }
                $filtros = [
                    'marcas' => $marcasFiltradas,
                    'estados' => Producto::select('estadoProductoWeb')->whereNotNull('estadoProductoWeb')->distinct()->get()
                ];

                return view('buscarproducto', [
                    'user' => $userModel,
                    'productos' => $productos,
                    'tc' => $this->calculadoraService->getTasaCambio(),
                    'filtros' => $filtros,
                    'almacenes' => $almacenes
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function createDetails(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 2) {
                $arrayProduct = array();
                $arrayProveedor = array();

                $nombre = $request->input('name');
                if ($nombre && str_starts_with($nombre, 'B64:')) {
                    $nombre = base64_decode(substr($nombre, 4));
                }
                
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
                if ($descripcion && str_starts_with($descripcion, 'B64:')) {
                    $descripcion = base64_decode(substr($descripcion, 4));
                }
                
                $codigo = $request->input('codigo');
                $tipoprecio = $request->input('tipoprecio');
                $stockminimo = $request->input('stockminimo');
                $video1 = $request->input('video1');
                $video2 = $request->input('video2');
                $tc_fijo = $request->input('tc_fijo');

                // Obtener ID de videos
                $videoId1 = $this->productoService->getYoutubeVideoId($video1);
                $videoId2 = $this->productoService->getYoutubeVideoId($video2);


                if (!empty($tipoprecio)) {
                    $inputPrecio = $request->input('precio') ?? 0;
                    $inputGanancia = $request->input('ganancia') ?? 0;

                    if ($tipoprecio == 'SOL') {
                        $usar_tc_fijo_submit = $request->boolean('usar_tc_fijo');
                        $tc_fijo_submit = $request->input('tc_fijo');

                        if ($usar_tc_fijo_submit) {
                            $tcAUsar = $this->calculadoraService->getTasaCambio(); // SUNAT
                        } else {
                            if (!empty($tc_fijo_submit) && $tc_fijo_submit > 0) {
                                $tcAUsar = $tc_fijo_submit; // Personalizado
                            } else {
                                $tcAUsar = $this->calculadoraService->getTasaFija()->tasaCambio; // Global Fijo
                            }
                        }

                        $precio = $inputPrecio / $tcAUsar;
                        $ganancia = $inputGanancia / $tcAUsar;
                    } else {
                        $precio = $inputPrecio;
                        $ganancia = $inputGanancia;
                    }
                } else {
                    $precio = 0;
                    $ganancia = 0;
                }

                if (isset($nombre, $upc, $modelo, $partnumber)) {
                    $validateNombre = $this->productoService->getOneProductByColumn('nombreProducto', $nombre);
                    $validateUpc = $this->productoService->getOneProductByColumn('UPC', $upc);
                    $validateModelo = $this->productoService->existsByModelo($modelo);
                    $validatePartNumber = $this->productoService->getOneProductByColumn('partNumber', $partnumber);
                } else {
                    $this->headerService->sendFlashAlerts('Error en los datos', 'Revisa los campos enviados', 'error', 'btn-danger');
                    return back()->withInput();
                }

                $switchupc = false;

                if ($upc == 0) {
                    $switchupc = true;
                } else {
                    if (empty($validateUpc)) {
                        $switchupc = true;
                    } else {
                        $switchupc = false;
                    }
                }

                if ($partnumber == 0) {
                    $switchPartNumber = true;
                } else {
                    if (empty($validatePartNumber)) {
                        $switchPartNumber = true;
                    } else {
                        $switchPartNumber = false;
                    }
                }

                if (!empty($codigo)) {
                    if (!empty($validateNombre)) {
                        $this->headerService->sendFlashAlerts('Titulo existente', 'Ya se encuentra registrado', 'info', 'btn-warning');
                        return back()->withInput();
                    } else if (!$switchupc) {
                        $this->headerService->sendFlashAlerts('UPC existente', 'Ya se encuentra registrado', 'info', 'btn-warning');
                        return back()->withInput();
                    } else if (!empty($validateModelo)) {
                        $this->headerService->sendFlashAlerts('Modelo existente', 'Ya se encuentra registrado', 'info', 'btn-warning');
                        return back()->withInput();
                    } else if (!$switchPartNumber) {
                        $this->headerService->sendFlashAlerts('Part number existente', 'Ya se encuentra registrado', 'info', 'btn-warning');
                        return back()->withInput();
                    } else {
                        $idProducto = 0;
                        try {
                            try {
                                $img1 = null;
                                $img2 = null;
                                $img3 = null;
                                $img4 = null;

                                if ($request->hasFile('imgone')) {
                                    $img1 = $request->file('imgone');
                                } elseif ($request->filled('deltron_img1')) {
                                    $img1 = $request->input('deltron_img1');
                                }

                                if ($request->hasFile('imgtwo')) {
                                    $img2 = $request->file('imgtwo');
                                } elseif ($request->filled('deltron_img2')) {
                                    $img2 = $request->input('deltron_img2');
                                }

                                if ($request->hasFile('imgtree')) {
                                    $img3 = $request->file('imgtree');
                                } elseif ($request->filled('deltron_img3')) {
                                    $img3 = $request->input('deltron_img3');
                                }

                                if ($request->hasFile('imgfour')) {
                                    $img4 = $request->file('imgfour');
                                } elseif ($request->filled('deltron_img4')) {
                                    $img4 = $request->input('deltron_img4');
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

                                $success = $this->productoService->insertProduct($arrayProduct, $arrayProveedor, $img1, $img2, $img3, $img4);
                                $idProducto = $success;

                                $this->productoService->validateState($idProducto);

                                if ($request->has('mostrarPrecioWeb') || $request->has('precio_pase')) {
                                    $mostrarWeb = $request->input('mostrarPrecioWeb') == '1' ? true : false;
                                    $precioPase = $request->input('precio_pase') ? floatval($request->input('precio_pase')) : null;
            
                                    \App\Models\Catalogo\DetalleProducto::updateOrCreate(
                                        ['idProducto' => $idProducto],
                                        [
                                            'mostrarPrecioWeb' => $mostrarWeb,
                                            'precio_pase' => $precioPase
                                        ]
                                    );
                                }

                                // Si provino de Deltron, guardar la vinculación en inventario_proveedor_skus
                                if ($request->filled('deltron_sku')) {
                                    $invProv = \App\Models\Inventario\Inventario_Proveedor::where('idProducto', $idProducto)->first();
                                    if ($invProv) {
                                        \App\Models\Inventario\InventarioProveedorSku::updateOrCreate(
                                            [
                                                'idInventarioProveedor' => $invProv->idInventarioProveedor,
                                                'idProveedor' => 1,
                                            ],
                                            [
                                                'sku_proveedor' => strtoupper(trim($request->input('deltron_sku'))),
                                                'part_number' => $request->input('partnumber') ?: $request->input('deltron_pn'),
                                                'mini_codigo' => $request->input('deltron_mini'),
                                                'precio_b2b' => floatval($request->input('precio')),
                                                'hay_mas' => $request->boolean('deltron_hay_mas', false),
                                            ]
                                        );
                                    }
                                }
                            } catch (Exception $e) {
                                \Log::error('Error en insert/validate: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
                                $this->headerService->sendFlashAlerts('Error en la operacion', 'Hubo un error en la transaccion', 'error', 'btn-danger');
                                return back()->withInput();
                            }

                            return redirect()->route('details', ['idProducto' => $idProducto]);
                        } catch (Exception $e) {
                            \Log::error('Error general createDetails: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
                            $this->headerService->sendFlashAlerts('Error en la operacion', 'Hubo un error en la transaccion', 'error', 'btn-danger');
                            return back()->withInput();
                        }
                    }
                } else {
                    $this->headerService->sendFlashAlerts('Generacion Fallida', 'Hubo un error en la generacion del codigo', 'error', 'btn-danger');
                    return back()->withInput();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }
    public function obtenerUbicacionHtml($id)
    {
        try {
            $producto = Producto::with(['Inventario', 'Inventario_Proveedor.Preveedor'])->findOrFail($id);
            $almacenes = \App\Models\Inventario\Almacen::with('Ubicaciones')->get();

            $seriesDisponibles = \App\Models\Inventario\RegistroProducto::with(['UbicacionExacta', 'Almacen'])
                ->whereNotIn('estado', ['ENTREGADO', 'INVALIDO', 'GARANTIA', 'DEFECTUOSO', 'ABIERTO', 'ELIMINADO', 'DIVIDIDO', 'REUNIDO'])
                ->whereHas('DetalleComprobante', function ($q) use ($id) {
                    $q->where('idProducto', $id);
                })
                ->get();

            $html = view('productos.partials.modal_ubicacion_body', compact('producto', 'almacenes', 'seriesDisponibles'))->render();
            $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

            return response()->json(['html' => $html]);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerUbicacionHtml: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['error' => 'Error interno del servidor.'], 500);
        }
    }



    public function updateProduct($idProducto, Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 2) {
                $arrayProduct = array();
                $titulo = $request->input('titulo');
                if ($titulo && str_starts_with($titulo, 'B64:')) {
                    $titulo = base64_decode(substr($titulo, 4));
                }
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
                if ($descripcion && str_starts_with($descripcion, 'B64:')) {
                    $descripcion = base64_decode(substr($descripcion, 4));
                }
                
                $tipoprecio = $request->input('tipoprecio');
                $stockminimo = $request->input('stockminimo');
                //videos
                $video1 = $request->input('videoUrl1');
                $video2 = $request->input('videoUrl2');

                try {
                    \DB::beginTransaction();
                    // Solo actualizar si el campo fue enviado (no está disabled)
                    if ($request->has('titulo')) {
                        $arrayProduct['nombreProducto'] = $titulo;
                    }
                    if ($request->has('tipoprecio') && !empty($tipoprecio)) {
                        if ($tipoprecio == 'SOL') {
                            // Determinar el TC correcto que se estaba usando
                            $usar_tc_fijo_submit = $request->boolean('usar_tc_fijo');
                            $tc_fijo_submit = $request->input('tc_fijo');

                            if ($usar_tc_fijo_submit) {
                                $tcAUsar = $this->calculadoraService->getTasaCambio(); // SUNAT
                            } else {
                                if (!empty($tc_fijo_submit) && $tc_fijo_submit > 0) {
                                    $tcAUsar = $tc_fijo_submit; // Personalizado
                                } else {
                                    $tcAUsar = $this->calculadoraService->getTasaFija()->tasaCambio; // Global Fijo
                                }
                            }

                            $precio = $request->input('precio') / $tcAUsar;
                            $ganancia = $request->input('ganancia') / $tcAUsar;
                        } else {
                            $precio = $request->input('precio');
                            $ganancia = $request->input('ganancia');
                        }
                    } else {
                        $precio = null;
                    }

                    if ($request->has('ganancia') && !is_null($ganancia)) {
                        $arrayProduct['gananciaExtra'] = $ganancia;
                    }

                    if ($request->has('precio') && !is_null($precio)) {
                        $arrayProduct['precioDolar'] = $precio;
                    }

                    if ($request->has('garantia')) {
                        $arrayProduct['garantia'] = $garantia;
                    }

                    if ($request->has('upc')) {
                        $arrayProduct['UPC'] = $upc;
                    }

                    if ($request->has('modelo')) {
                        if (!empty($modelo) && $this->productoService->existsByModelo($modelo, decrypt($idProducto))) {
                            $this->headerService->sendFlashAlerts('Modelo existente', 'Ya se encuentra registrado con otro producto', 'info', 'btn-warning');
                            return redirect()->back();
                        }
                        $arrayProduct['modelo'] = $modelo;
                    }

                    if ($request->has('partnumber')) {
                        $arrayProduct['partNumber'] = $partnumber;
                    }

                    if ($request->has('descripcion')) {
                        $arrayProduct['descripcionProducto'] = $descripcion;
                    }

                    if ($request->has('estado')) {
                        $arrayProduct['estadoProductoWeb'] = $estado;
                    }

                    if ($request->has('marca')) {
                        $arrayProduct['idMarca'] = $marca;
                    }

                    if ($request->has('stockminimo')) {
                        $arrayProduct['stockMin'] = $stockminimo;
                    }

                    $arrayProduct['usar_tc_fijo'] = $request->boolean('usar_tc_fijo');

                    if ($request->has('tc_fijo')) {
                        $arrayProduct['tc_fijo'] = $request->input('tc_fijo');
                    }


                    if (!empty($video1)) {
                        $videoId1 = $this->productoService->getYoutubeVideoId($video1);
                        $arrayProduct['videoUrl1'] = $videoId1 ?: null;
                    }

                    if (!empty($video2)) {
                        $videoId2 = $this->productoService->getYoutubeVideoId($video2);
                        $arrayProduct['videoUrl2'] = $videoId2 ?: null;
                    }

                    $this->productoService->updateProduct(decrypt($idProducto), $arrayProduct, $request->file('imgone'), $request->file('imgtwo'), $request->file('imgtree'), $request->file('imgfour'));

                    if (!is_null($stock)) {
                        $this->productoService->updateInventory(decrypt($idProducto), $stock);
                    }

                    $ubicacion = $request->input('idUbicacionExacta');
                    if (!is_null($ubicacion)) {
                        $this->productoService->updateUbicacion(decrypt($idProducto), $ubicacion);
                    }

                    if (!is_null($proveedor) && !is_null($stockproveedor)) {
                        $arraySeguimiento = array();
                        $arraySeguimiento['idProveedor'] = $proveedor;
                        $arraySeguimiento['stock'] = $stockproveedor;

                        $this->productoService->updateSeguimiento(decrypt($idProducto), $arraySeguimiento);
                    }

                    $this->productoService->validateState(decrypt($idProducto));

                    // -- Precio de Tienda --
                    if ($request->has('precioTienda')) {
                        $nuevoPrecio = floatval($request->input('precioTienda'));
                        $realId = decrypt($idProducto);
                        $precioActual = \App\Models\Precios\PrecioTienda::where('idProducto', $realId)->first();

                        if ($precioActual) {
                            $precioAnterior = $precioActual->precioTienda;
                            if (abs($precioAnterior - $nuevoPrecio) > 0.001) {
                                // Registrar historial
                                \App\Models\Precios\HistorialPrecioTienda::create([
                                    'idProducto'     => $realId,
                                    'precioAnterior'  => $precioAnterior,
                                    'precioNuevo'     => $nuevoPrecio,
                                    'idUser'          => $userModel->idUser,
                                ]);
                                $precioActual->update([
                                    'precioTienda' => $nuevoPrecio,
                                    'updated_at'   => now(),
                                ]);
                            }
                        } else {
                            \App\Models\Precios\PrecioTienda::create([
                                'idProducto'   => $realId,
                                'precioTienda' => $nuevoPrecio,
                                'updated_at'   => now(),
                            ]);
                            // Primer registro: historial 0 -> nuevo
                            if ($nuevoPrecio > 0) {
                                \App\Models\Precios\HistorialPrecioTienda::create([
                                    'idProducto'     => $realId,
                                    'precioAnterior'  => 0,
                                    'precioNuevo'     => $nuevoPrecio,
                                    'idUser'          => $userModel->idUser,
                                ]);
                            }
                        }
                    }

                    if ($request->has('mostrarPrecioWeb') || $request->has('precio_pase')) {
                        $realId = decrypt($idProducto);
                        $mostrarWeb = $request->input('mostrarPrecioWeb') == '1' ? true : false;
                        $precioPase = $request->input('precio_pase') ? floatval($request->input('precio_pase')) : null;

                        \App\Models\Catalogo\DetalleProducto::updateOrCreate(
                            ['idProducto' => $realId],
                            [
                                'mostrarPrecioWeb' => $mostrarWeb,
                                'precio_pase' => $precioPase
                            ]
                        );
                    }

                    \DB::commit();
                    $this->headerService->sendFlashAlerts('Producto actualizado', 'Los datos e imagenes se actualizaron correctamente.', 'success', 'btn-success');
                    return redirect()->back();
                } catch (\Exception $e) {
                    \DB::rollBack();
                    \Log::error('Error en updateProduct: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
                    $this->headerService->sendFlashAlerts('Error en la operacion', 'Hubo un error al actualizar el producto: ' . $e->getMessage(), 'error', 'btn-danger');
                    return redirect()->back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function insertOrUpdateDetails(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 2) {
                $idProducto = $request->input('idproducto');
                $updateCaracteristicas = $request->input('updatecaracteristicas', []);
                $insertCaracteristicas = $request->input('insertcaracteristicas', []);
                $proba = false;
                try {
                    $this->productoService->insertOrUpdateCaracteristicas($idProducto, $insertCaracteristicas, $updateCaracteristicas);
                    $proba = true;
                } catch (Exception $e) {
                    $proba = false;
                }

                if (!$proba) {
                    $this->headerService->sendFlashAlerts('Error en la operacion', 'Hubo un error en la transaccion', 'error', 'btn-danger');
                }

                return redirect()->back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function deleteDetail($idProducto, Request $request)
    {
        $idCaracteristica = $request->input('idcaracteristica');
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 2) {
                if (isset($idCaracteristica)) {
                    $this->productoService->deleteCaracteristicaXProduct(decrypt($idProducto), $idCaracteristica);
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
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

                    $newGrupo = \App\Models\Catalogo\GrupoProducto::orderBy('idGrupoProducto', 'desc')->first();

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

    public function crearProductoPadre(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $request->validate([
            'nombreProducto' => 'required|string|max:255',
            'modelo' => 'required|string|max:100',
            'idMarca' => 'required|integer',
            'idGrupo' => 'required|integer',
        ]);

        $grupo = \App\Models\Catalogo\GrupoProducto::findOrFail($request->input('idGrupo'));
        $deltronService = app()->make(\App\Services\Deltron\DeltronApiService::class);
        $codigoPadre = $deltronService->generarSiguienteCodigoProducto($grupo->idGrupoProducto, $grupo->nombreGrupo);

        $padre = Producto::create([
            'idMarca' => $request->input('idMarca'),
            'idGrupo' => $request->input('idGrupo'),
            'codigoProducto' => $codigoPadre,
            'nombreProducto' => trim($request->input('nombreProducto')),
            'modelo' => trim($request->input('modelo')),
            'partNumber' => trim($request->input('partNumber') ?: $request->input('modelo')),
            'precioDolar' => floatval($request->input('precioDolar', 0)),
            'gananciaExtra' => 0.00,
            'garantia' => $request->input('garantia', '12 meses'),
            'descripcionProducto' => $request->input('descripcionProducto', ''),
            'imagenProducto1' => $request->input('imagenProducto1') ?: "asset('storage/images/noimagen.webp')",
            'estadoProductoWeb' => 'DISPONIBLE',
            'stockMin' => 1,
            'usar_tc_fijo' => 1,
            'es_padre' => 1,
            'idProductoPadre' => null,
        ]);

        \App\Models\Catalogo\DetalleProducto::updateOrCreate(
            ['idProducto' => $padre->idProducto],
            [
                'mostrarPrecioWeb' => true,
                'tipo_variante' => 'COLOR',
                'valor_variante' => '',
                'codigo_color_hex' => '',
            ]
        );

        // Si se selecciono vincular el producto actual donde se abrio el modal:
        $idActual = $request->input('idProductoActual');
        if ($request->boolean('vincular_actual') && $idActual) {
            $hijo = Producto::find($idActual);
            if ($hijo && $hijo->idProducto != $padre->idProducto) {
                $hijo->idProductoPadre = $padre->idProducto;
                $hijo->es_padre = 0;
                $hijo->save();

                $colorNombre = trim($request->input('color_actual', ''));
                $colorHex = trim($request->input('color_hex_actual', '#6c757d'));

                \App\Models\Catalogo\DetalleProducto::updateOrCreate(
                    ['idProducto' => $hijo->idProducto],
                    [
                        'tipo_variante' => 'COLOR',
                        'valor_variante' => $colorNombre ?: 'Variante',
                        'codigo_color_hex' => $colorHex ?: '#6c757d',
                        'mostrarPrecioWeb' => true,
                    ]
                );
            }
        }

        $this->headerService->sendFlashAlerts(
            'Producto Padre Creado',
            "Se creo el contenedor padre {$padre->nombreProducto} ({$padre->modelo}) exitosamente.",
            'success',
            'btn-success'
        );

        return redirect()->route('producto', [encrypt($padre->idProducto)]);
    }

    private function resolverIdProducto($id)
    {
        if (empty($id)) {
            return null;
        }

        if (is_numeric($id)) {
            return (int) $id;
        }

        try {
            $decrypted = decrypt($id);
            return is_numeric($decrypted) ? (int) $decrypted : $decrypted;
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return is_numeric($id) ? (int) $id : $id;
        } catch (\Exception $e) {
            return is_numeric($id) ? (int) $id : $id;
        }
    }

    public function convertirEnPadre(Request $request, $idProducto)
    {
        $id = $this->resolverIdProducto($idProducto);
        $producto = Producto::findOrFail($id);

        $producto->es_padre = 1;
        $producto->idProductoPadre = null;
        $producto->save();

        \App\Models\Catalogo\DetalleProducto::updateOrCreate(
            ['idProducto' => $producto->idProducto],
            [
                'mostrarPrecioWeb' => true,
                'tipo_variante' => 'COLOR',
                'valor_variante' => '',
                'codigo_color_hex' => '',
            ]
        );

        $this->headerService->sendFlashAlerts(
            'Convertido a Padre',
            "El producto {$producto->modelo} ahora es un Contenedor Padre de variantes.",
            'success',
            'btn-success'
        );

        return back();
    }

    public function vincularVariante(Request $request, $idPadre)
    {
        $userModel = $this->headerService->getModelUser();
        $idPadre = $this->resolverIdProducto($idPadre);
        $idHijo = $this->resolverIdProducto($request->input('idProductoHijo'));

        if (!$idPadre || !$idHijo) {
            $this->headerService->sendFlashAlerts('Error', 'No se especifico el producto padre o hijo.', 'warning', 'btn-danger');
            return back();
        }

        $padre = Producto::findOrFail($idPadre);
        $hijo = Producto::findOrFail($idHijo);

        if ($hijo->idProducto == $padre->idProducto) {
            $this->headerService->sendFlashAlerts('Error', 'Un producto no puede ser variante de si mismo', 'warning', 'btn-danger');
            return back();
        }

        if ($padre->idProductoPadre) {
            $this->headerService->sendFlashAlerts('Error', 'El producto seleccionado como padre ya es una variante de otro producto', 'warning', 'btn-danger');
            return back();
        }

        if ($hijo->es_padre && $hijo->variantes()->exists()) {
            $this->headerService->sendFlashAlerts('Error', 'El producto hijo ya tiene variantes asociadas. Desvincula sus variantes primero.', 'warning', 'btn-danger');
            return back();
        }

        $color = trim($request->input('color', ''));
        $colorHex = trim($request->input('color_hex', '#6c757d'));

        $hijo->idProductoPadre = $padre->idProducto;
        $hijo->es_padre = 0;
        $hijo->save();

        $padre->es_padre = 1;
        $padre->save();

        // Asegurar DetalleProducto para el padre si no existe
        \App\Models\Catalogo\DetalleProducto::firstOrCreate(
            ['idProducto' => $padre->idProducto],
            [
                'tipo_variante' => 'COLOR',
                'valor_variante' => '',
                'codigo_color_hex' => '',
                'mostrarPrecioWeb' => true
            ]
        );

        \App\Models\Catalogo\DetalleProducto::updateOrCreate(
            ['idProducto' => $hijo->idProducto],
            [
                'tipo_variante' => 'COLOR',
                'valor_variante' => $color ?: 'Variante',
                'codigo_color_hex' => $colorHex ?: '#6c757d',
                'mostrarPrecioWeb' => true
            ]
        );

        $this->headerService->sendFlashAlerts('Variante Vinculada', "Se vinculo {$hijo->modelo} al producto padre {$padre->modelo}", 'success', 'btn-success');
        return back();
    }

    public function desvincularVariante(Request $request, $idHijo)
    {
        $idHijo = $this->resolverIdProducto($idHijo);
        $hijo = Producto::findOrFail($idHijo);
        $idPadreAnterior = $hijo->idProductoPadre;

        $hijo->idProductoPadre = null;
        $hijo->save();

        if ($idPadreAnterior) {
            $tieneMasHijos = Producto::where('idProductoPadre', $idPadreAnterior)->exists();
            if (!$tieneMasHijos) {
                Producto::where('idProducto', $idPadreAnterior)->update(['es_padre' => 0]);
            }
        }

        $this->headerService->sendFlashAlerts('Variante Desvinculada', "El producto {$hijo->modelo} ahora es un producto individual independiente.", 'success', 'btn-success');
        return back();
    }
}

