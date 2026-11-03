<?php

namespace App\Services;

use App\Repositories\ProductoRepositoryInterface;
use App\Repositories\MarcaProductoRepositoryInterface;
use App\Repositories\GrupoProductoRepositoryInterface;
use App\Repositories\CategoriaProductoRepositoryInterface;
use App\Repositories\ProveedorRepositoryInterface;
use App\Repositories\AlmacenRepositoryInterface;
use App\Repositories\CaracteristicasProductoRepositoryInterface;
use App\Repositories\InventarioRepositoryInterface;
use App\Repositories\ProveedorInventarioRepositoryInterface;
use App\Repositories\RegistroProductoRepositoryInterface;
use Exception;

class ProductoService implements ProductoServiceInterface
{
    protected $productoRepository;
    protected $marcaRepository;
    protected $grupoRepository;
    protected $categoriaRepository;
    protected $proveedorRepository;
    protected $almacenRepository;
    protected $inventarioRepository;
    protected $proveedorInventarioRepository;
    protected $caracteristicasProductoRepository;
    protected $registroRepository;

    private $path;

    public function __construct(
        ProductoRepositoryInterface $productoRepository,
        MarcaProductoRepositoryInterface $marcaRepository,
        GrupoProductoRepositoryInterface $grupoRepository,
        CategoriaProductoRepositoryInterface $categoriaRepository,
        ProveedorRepositoryInterface $proveedorRepository,
        AlmacenRepositoryInterface $almacenRepository,
        InventarioRepositoryInterface $inventarioRepository,
        ProveedorInventarioRepositoryInterface $proveedorInventarioRepository,
        CaracteristicasProductoRepositoryInterface $caracteristicasProductoRepository,
        RegistroProductoRepositoryInterface $registroRepository
    ) {
        $this->productoRepository = $productoRepository;
        $this->marcaRepository = $marcaRepository;
        $this->grupoRepository = $grupoRepository;
        $this->categoriaRepository = $categoriaRepository;
        if (app()->environment('local')) {
            $this->path = public_path('storage/productos');
        } else {
            $baseDir = dirname(base_path());
            $this->path = $baseDir . '/public_html/images/productos';
        }
        $this->proveedorRepository = $proveedorRepository;
        $this->almacenRepository = $almacenRepository;
        $this->inventarioRepository = $inventarioRepository;
        $this->proveedorInventarioRepository = $proveedorInventarioRepository;
        $this->caracteristicasProductoRepository = $caracteristicasProductoRepository;
        $this->registroRepository = $registroRepository;
    }

    public function getAllAlmacen()
    {
        $almacenes = $this->almacenRepository->all();
        return $almacenes;
    }

    public function getAllLabelGrupo()
    {
        $grupoModel = $this->grupoRepository->all()->sortBy('nombreGrupo');
        $grupo = $grupoModel->map(function ($group) {
            return [
                'idGrupoProducto' => $group->idGrupoProducto,
                'idCategoria' => $group->idCategoria,
                'nombreGrupo' => $group->nombreGrupo
            ];
        });
        return $grupo;
    }

    public function getAllLabelProveedor()
    {
        $proveedorModel = $this->proveedorRepository->all();
        $proveedor = $proveedorModel->sortBy('nombreProveedor')->map(function ($prov) {
            return [
                'nombreProveedor' => $prov->nombreProveedor,
                'idProveedor' => $prov->idProveedor
            ];
        });
        return $proveedor;
    }

    public function getAllLabelMarca()
    {
        $marcasModel = $this->marcaRepository->all();
        $marcas = $marcasModel->sortBy('nombreMarca')->map(function ($mark) {
            return [
                'nombreMarca' => $mark->nombreMarca,
                'idMarca' => $mark->idMarca
            ];
        });
        return $marcas;
    }

    public function getMarcasBySearch($input)
    {
        // Obtener marcas filtradas por término de búsqueda
        $marcas = $this->productoRepository->getMarcasBySearchTerm($input);
        return $marcas;
    }

    public function getAllLabelCategory()
    {
        $categoriaModel = $this->categoriaRepository->all();
        $categoria = $categoriaModel->map(function ($cat) {
            return [
                'idCategoria' => $cat->idCategoria,
                'nombreCategoria' => $cat->nombreCategoria,
                'GrupoProducto' => $cat->GrupoProducto
            ];
        });
        return $categoria;
    }

    public function getOneLabelCategory($idCategory)
    {
        $categoria = $this->categoriaRepository->getOne('idCategoria', $idCategory)->only(['idCategoria', 'nombreCategoria']);
        return $categoria;
    }

    public function getAllLabelGrupoXCategory($idCategory)
    {
        $grupoModel = $this->grupoRepository->getAllByColumn('idCategoria', $idCategory);
        $grupos = $grupoModel->map(function ($group) {
            return [
                'idGrupoProducto' => $group->idGrupoProducto,
                'idCategoria' => $group->idCategoria,
                'nombreGrupo' => $group->nombreGrupo
            ];
        });
        return $grupos;
    }

    public function getOneLabelGrupo($idGrupo)
    {
        $grupo = $this->grupoRepository->getOne('idGrupoProducto', $idGrupo)->only(['nombreGrupo', 'idGrupoProducto', 'idCategoria']);
        return $grupo;
    }

    public function getAllProductsByColumn($column, $data, $cant, $querys)
    {
        $productos = $this->productoRepository->paginateAllByColumn($column, $data, $cant, $querys);
        return $productos;
    }

    public function getOneProductByColumn($column, $data)
    {
        $producto = $this->productoRepository->getOne($column, $data);
        return $producto;
    }

    public function getLastCodesProducts()
    {
        $codes = $this->productoRepository->getProductsCodes();
        return $codes;
    }

    public function searchAjaxProducts($column, $query)
    {
        $productos = $this->productoRepository->searchTakeList($column, $query, 10);
        return $productos;
    }

    public function searchProducts($input, $cont, $filtros)
    {
        // 1. Intentamos búsqueda intensiva directamente (ahora es mucho más potente)
        $productos = $this->productoRepository->searchIntensiveProducts($input, $cont, $filtros);

        // 2. Si no hay resultados, podríamos intentar buscar solo por Marca (por si acaso)
        if ($productos->isEmpty()) {
            $marca = $this->marcaRepository->searchOne('nombreMarca', $input);
            if ($marca) {
                $productos = $this->productoRepository->paginateAllByColumn('idMarca', $marca->idMarca, $cont, $filtros);
            }
        }

        return $productos;
    }

    public function insertProduct($array, $proveedor, $img1, $img2, $img3, $img4)
    {
        $imgService = new ImageService();
        $array['idProducto'] = $this->generarId();
        $array['imagenProducto1'] = 'productos/IMGPRO' . $array['idProducto'] . '_1.webp';
        $array['imagenProducto2'] = 'productos/IMGPRO' . $array['idProducto'] . '_2.webp';
        $array['imagenProducto3'] = 'productos/IMGPRO' . $array['idProducto'] . '_3.webp';
        $array['imagenProducto4'] = 'productos/IMGPRO' . $array['idProducto'] . '_4.webp';
        $array['codigoProducto'] = $this->generarCodigo($array['codigoProducto']);


        if ($proveedor['stock'] > 0) {
            $array['estadoProductoWeb'] = $array['estadoProductoWeb'];
        } else {
            if ($array['estadoProductoWeb'] == 'DESCONTINUADO') {
                $array['estadoProductoWeb'] = $array['estadoProductoWeb'];
            } else {
                $array['estadoProductoWeb'] = 'AGOTADO';
            }
        }

        // Validar que no exista otro producto con el mismo modelo (case-insensitive)
        if (!empty($array['modelo']) && $this->productoRepository->existsByModelo($array['modelo'])) {
            throw new \InvalidArgumentException('Ya existe un producto con el modelo "' . $array['modelo'] . '". No se permite duplicados.');
        }

        try {
            $newProducto = $this->productoRepository->create($array);

            if ($newProducto) {
                $this->createSeguimiento($array['idProducto'], $proveedor);
                $this->insertInventory($array['idProducto']);
                if ($img1 != null) $imgService->createImage($img1, $array['idProducto'] . '_1', $this->path);
                if ($img2 != null) $imgService->createImage($img2, $array['idProducto'] . '_2', $this->path);
                if ($img3 != null) $imgService->createImage($img3, $array['idProducto'] . '_3', $this->path);
                if ($img4 != null) $imgService->createImage($img4, $array['idProducto'] . '_4', $this->path);
            }
            return $array['idProducto'];
        } catch (Exception $e) {
            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    /**
     * Reemplaza (recicla) un producto fantasma conservando su idProducto.
     * Solo permitido si el producto nunca tuvo ningún ingreso en IngresoProducto.
     */
    public function replaceProduct($idProducto, $array, $proveedor, $img1, $img2, $img3, $img4)
    {
        $idProducto = (int) $idProducto;
        $productoExistente = \App\Models\Catalogo\Producto::find($idProducto);

        if (!$productoExistente) {
            throw new \InvalidArgumentException("El producto ID #{$idProducto} no existe.");
        }

        if (!$productoExistente->esReemplazable()) {
            $ultimaVenta = $this->getFechaUltimaVenta($idProducto);
            $mesesGarantia = $this->obtenerMesesGarantiaProducto($productoExistente, 12);
            if ($ultimaVenta && $ultimaVenta->copy()->addMonths($mesesGarantia)->isFuture()) {
                $expira = $ultimaVenta->copy()->addMonths($mesesGarantia)->format('d/m/Y');
                throw new \InvalidArgumentException("El producto {$productoExistente->codigoProducto} (ID #{$idProducto}) registra una venta del {$ultimaVenta->format('d/m/Y')} con garantía vigente hasta el {$expira} y no puede ser reemplazado.");
            }
            throw new \InvalidArgumentException("El producto {$productoExistente->codigoProducto} (ID #{$idProducto}) ya registra ingresos o garantía vigente en almacén y no puede ser reemplazado.");
        }

        if (!empty($array['modelo']) && $this->productoRepository->existsByModelo($array['modelo'], $idProducto)) {
            throw new \InvalidArgumentException('Ya existe otro producto con el modelo "' . $array['modelo'] . '". No se permite duplicados.');
        }

        // Si se mantiene en el mismo grupo, conserva su codigoProducto original; si cambia de grupo, genera el nuevo correlativo
        $nuevoGrupoId = (int) ($array['idGrupo'] ?? $productoExistente->idGrupo);
        if ($nuevoGrupoId === (int) $productoExistente->idGrupo && !empty($productoExistente->codigoProducto)) {
            $array['codigoProducto'] = $productoExistente->codigoProducto;
        } else {
            if (!empty($array['codigoProducto'])) {
                $array['codigoProducto'] = $this->generarCodigo($array['codigoProducto']);
            } else {
                $grupoObj = \App\Models\Catalogo\GrupoProducto::find($nuevoGrupoId);
                $classifier = app(\App\Services\Deltron\DeltronClassifierService::class);
                $array['codigoProducto'] = $classifier->generarSiguienteCodigoProducto($nuevoGrupoId, $grupoObj ? $grupoObj->nombreGrupo : 'General');
            }
        }

        if (($proveedor['stock'] ?? 0) <= 0 && ($array['estadoProductoWeb'] ?? '') !== 'DESCONTINUADO') {
            $array['estadoProductoWeb'] = 'AGOTADO';
        }

        $array['es_padre'] = 0;
        $array['idProductoPadre'] = null;
        if (!array_key_exists('videoUrl1', $array) && array_key_exists('video1_url', $array)) {
            $array['videoUrl1'] = $array['video1_url'];
            unset($array['video1_url']);
        }
        if (!array_key_exists('videoUrl2', $array) && array_key_exists('video2_url', $array)) {
            $array['videoUrl2'] = $array['video2_url'];
            unset($array['video2_url']);
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($idProducto, $productoExistente, $array, $proveedor, $img1, $img2, $img3, $img4) {
            // 1. Limpiar tablas satélite del producto anterior
            \App\Models\Catalogo\Caracteristicas_Producto::where('idProducto', $idProducto)->delete();
            \App\Models\Inventario\Liquidacion::where('idProducto', $idProducto)->delete();
            \App\Models\Precios\PrecioTienda::where('idProducto', $idProducto)->delete();
            \App\Models\Precios\HistorialPrecioTienda::where('idProducto', $idProducto)->delete();
            \App\Models\Catalogo\Publicacion::where('idProducto', $idProducto)->delete();

            \App\Models\Catalogo\DetalleProducto::updateOrCreate(
                ['idProducto' => $idProducto],
                [
                    'mostrarPrecioWeb' => true,
                    'precio_pase' => null,
                    'tipo_variante' => null,
                    'valor_variante' => null,
                    'codigo_color_hex' => null,
                ]
            );

            // 2. Asegurar inventario físico en 0 para todos los almacenes
            $tieneInv = \App\Models\Inventario\Inventario::where('idProducto', $idProducto)->exists();
            if ($tieneInv) {
                \App\Models\Inventario\Inventario::where('idProducto', $idProducto)->update(['stock' => 0, 'idUbicacionExacta' => null]);
            } else {
                $this->insertInventory($idProducto);
            }

            // 3. Limpiar SKUs anteriores de proveedor y actualizar Inventario_Proveedor
            $invProv = \App\Models\Inventario\Inventario_Proveedor::where('idProducto', $idProducto)->first();
            if ($invProv) {
                \App\Models\Inventario\InventarioProveedorSku::where('idInventarioProveedor', $invProv->idInventarioProveedor)->delete();
                $invProv->idProveedor = (int) ($proveedor['idProveedor'] ?? 1);
                $invProv->stock = (int) ($proveedor['stock'] ?? 0);
                $invProv->estado = 'VIGENTE';
                $invProv->save();
            } else {
                $this->createSeguimiento($idProducto, [
                    'idProveedor' => $proveedor['idProveedor'] ?? 1,
                    'stock' => $proveedor['stock'] ?? 0,
                ]);
            }

            // 4. Procesar imágenes (y limpiar archivos antiguos de los slots no utilizados)
            $imgService = new ImageService();
            $destinationPath = \App\Services\Deltron\DeltronTextHelper::getDestinationPathProductos();
            if (!is_dir($destinationPath)) {
                @mkdir($destinationPath, 0755, true);
            }
            $localPath = public_path('storage/productos');
            $storageAppPath = storage_path('app/public/productos');

            $timestamp = time();
            $images = [1 => $img1, 2 => $img2, 3 => $img3, 4 => $img4];

            foreach ($images as $num => $imgFile) {
                $fileName = 'IMGPRO' . $idProducto . '_' . $num . '.webp';
                $fullDest = $destinationPath . '/' . $fileName;

                if (!is_null($imgFile) && $imgFile !== '') {
                    $saved = $imgService->createImage($imgFile, $idProducto . '_' . $num, $destinationPath);
                    if ($saved) {
                        if (is_dir($localPath) && realpath($destinationPath) !== realpath($localPath)) {
                            @copy($fullDest, $localPath . '/' . $fileName);
                        }
                        if (is_dir($storageAppPath) && realpath($destinationPath) !== realpath($storageAppPath)) {
                            @copy($fullDest, $storageAppPath . '/' . $fileName);
                        }
                        $array['imagenProducto' . $num] = 'productos/' . $fileName . '?v=' . $timestamp;
                        continue;
                    }
                }

                // Si no se envió imagen para este slot, borrar archivo viejo para que no quede foto del producto anterior
                foreach ([$fullDest, $localPath . '/' . $fileName, $storageAppPath . '/' . $fileName] as $oldFile) {
                    if (is_file($oldFile)) {
                        @unlink($oldFile);
                    }
                }
                $array['imagenProducto' . $num] = ($num === 1) ? 'noimagen.webp' : null;
            }

            // 5. Actualizar fila del Producto (dispara regeneración de slugProducto e invalidación de caché web)
            $productoExistente->fill($array);
            $productoExistente->save();

            return $idProducto;
        });
    }
    public function updateProduct($id, $array, $img1, $img2, $img3, $img4)
    {
        $imgService = new ImageService();

        if (!$id) {
            return null;
        }

        try {
            $producto = $this->productoRepository->update($id, $array);

            if ($producto) {
                $destinationPath = \App\Services\Deltron\DeltronApiService::getDestinationPathProductos();
                if (!is_dir($destinationPath)) {
                    @mkdir($destinationPath, 0755, true);
                }

                $timestamp = time();
                $imageUpdates = [];
                $images = [
                    1 => $img1,
                    2 => $img2,
                    3 => $img3,
                    4 => $img4,
                ];

                foreach ($images as $num => $imgFile) {
                    if (!is_null($imgFile)) {
                        $saved = $imgService->createImage($imgFile, $producto->idProducto . '_' . $num, $destinationPath);
                        if ($saved) {
                            $fileName = 'IMGPRO' . $producto->idProducto . '_' . $num . '.webp';
                            $fullDest = $destinationPath . '/' . $fileName;

                            // Sincronizar en public_path('storage/productos') si es diferente
                            $localPath = public_path('storage/productos');
                            if (is_dir($localPath) && realpath($destinationPath) !== realpath($localPath)) {
                                @copy($fullDest, $localPath . '/' . $fileName);
                            }

                            // Sincronizar en storage_path('app/public/productos') si es diferente
                            $storageAppPath = storage_path('app/public/productos');
                            if (is_dir($storageAppPath) && realpath($destinationPath) !== realpath($storageAppPath)) {
                                @copy($fullDest, $storageAppPath . '/' . $fileName);
                            }

                            // Guardar en la BD con parametro ?v=timestamp para forzar refresco de cache
                            $imageUpdates['imagenProducto' . $num] = 'productos/' . $fileName . '?v=' . $timestamp;
                        } else {
                            \Log::error("No se pudo guardar la imagen slot {$num} para el producto {$id}");
                        }
                    }
                }

                if (!empty($imageUpdates)) {
                    $this->productoRepository->update($id, $imageUpdates);
                }

                // Invalidar cache web
                $productoFresh = $this->productoRepository->getOne('idProducto', $id);
                if ($productoFresh && method_exists($productoFresh, 'invalidarCacheWeb')) {
                    $productoFresh->invalidarCacheWeb();
                }
            }

            return $producto;
        } catch (Exception $e) {
            \Log::error('Error en ProductoService::updateProduct: ' . $e->getMessage());
            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    public function insertOrUpdateCaracteristicas($id, $arrayCreate, $arrayUpdate)
    {
        if (!empty($arrayCreate)) {
            foreach ($arrayCreate as $spec =>  $desc) {
                if (!empty($spec) && !empty($desc)) {
                    $this->caracteristicasProductoRepository->insertCaracteristica($spec, $id, $desc);
                }
            }
        }
        if (!empty($arrayUpdate)) {
            foreach ($arrayUpdate as $spec =>  $desc) {
                if (!empty($spec) && !empty($desc)) {
                    $this->caracteristicasProductoRepository->updateCaracteristica($spec, $id, $desc);
                }
            }
        }
    }

    public function deleteCaracteristicaXProduct($idProducto, $idCaracteristica)
    {
        $this->caracteristicasProductoRepository->deleteSpect($idProducto, $idCaracteristica);
    }

    public function validateState($id, $allowAutoAvailable = false)
    {
        $producto = $this->productoRepository->getOne('idProducto', $id);
        if ($producto) {
            $tieneStockFisico = false;
            // Verificar stock en todos los almacenes del inventario físico
            foreach ($producto->Inventario as $inventario) {
                if ($inventario->stock > 0) {
                    $tieneStockFisico = true;
                    break;
                }
            }

            $tieneStockProveedor = false;
            if ($producto->Inventario_Proveedor && $producto->Inventario_Proveedor->stock > 0) {
                $tieneStockProveedor = true;
            }

            $tieneStock = $tieneStockFisico || $tieneStockProveedor;

            $estadoActual = $producto->estadoProductoWeb;
            $nuevoEstado = null;

            if (!$tieneStock) {
                // Si no hay stock y NO está descontinuado, pasar a AGOTADO
                if ($estadoActual != 'DESCONTINUADO') {
                    $nuevoEstado = 'AGOTADO';
                }
            } else {
                // Si HAY stock (físico o de proveedor) y estaba AGOTADO, volver a DISPONIBLE (solo si se permite la transición automática)
                if ($estadoActual == 'AGOTADO' && $allowAutoAvailable) {
                    $nuevoEstado = 'DISPONIBLE';
                }
            }

            if ($nuevoEstado && $estadoActual != $nuevoEstado) {
                $this->productoRepository->update($producto->idProducto, ['estadoProductoWeb' => $nuevoEstado]);
            }
        }
    }

    public function updateUbicacion($idProduct, $array)
    {
        if (!$idProduct) {
            return null;
        }

        try {
            $this->inventarioRepository->updateUbicacion($idProduct, $array);
            return true;
        } catch (Exception $e) {
            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    public function validateDuplicySerial($id, $serial)
    {
        return $this->productoRepository->validateSerial($id, $serial);
    }

    public function updateInventory($idProduct, $array)
    {
        if (!$idProduct) {
            return null;
        }

        try {
            $this->inventarioRepository->update($idProduct, $array);

            return true;
        } catch (Exception $e) {
            // Manejo de excepciones
            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    public function updateSeguimiento($idProducto, $array)
    {
        if (!is_null($idProducto) && !is_null($array)) {
            $this->proveedorInventarioRepository->update($idProducto, $array);
        }
    }

    public function filtroMarcas($column, $data)
    {
        return $this->productoRepository->getMarcasByColumn($column, $data);
    }

    public function filtroEstados($column, $data)
    {
        return $this->productoRepository->getEstadosByColumn($column, $data);
    }

    private function insertInventory($idProducto)
    {
        if (!$idProducto) {
            return null;
        }

        try {
            $almacenes = $this->almacenRepository->all();
            foreach ($almacenes as $almacen) {
                $data = [
                    'idProducto' => $idProducto,
                    'idAlmacen' => $almacen->idAlmacen,
                    'stock' => 0
                ];

                $this->inventarioRepository->create($data);
            }

            return true;
        } catch (Exception $e) {
            // Manejo de excepciones
            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function imageVersions($id)
    {
        if ($id) {
            $producto = $this->productoRepository->getOne('idProducto', $id);
            $data = [
                'imagenProducto1' => $this->generateImageVersion($producto->imagenProducto1),
                'imagenProducto2' => $this->generateImageVersion($producto->imagenProducto2),
                'imagenProducto3' => $this->generateImageVersion($producto->imagenProducto3),
                'imagenProducto4' => $this->generateImageVersion($producto->imagenProducto4)
            ];
            $this->productoRepository->update($id, $data);
        }
    }

    private function generateImageVersion($path)
    {
        if ($path) {
            $basePath = strtok($path, '?');
            return $basePath . '?v=' . time();
        }
        return $path;
    }

    private function createSeguimiento($idProducto, $array)
    {
        if (!$idProducto) {
            return null;
        }

        if (empty($array['idProveedor'])) {
            return true; // No provider selected, do nothing
        }

        try {
            $data = array();
            $data['idInventarioProveedor'] = (int)$this->generateIdSeguimiento();
            $data['idProducto'] = (int)$idProducto;
            $data['idProveedor'] = (int)$array['idProveedor'];
            $data['stock'] = (int)$array['stock'];
            $data['estado'] = 'VIGENTE';

            $this->proveedorInventarioRepository->create($data);
            return true;
        } catch (Exception $e) {
            // Manejo de excepciones
            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    private function generateIdSeguimiento()
    {
        $lastId = $this->proveedorInventarioRepository->getLast();
        $id = $lastId ? $lastId->idInventarioProveedor : 0;
        return $id + 1;
    }

    private function generarId()
    {
        $lastId = $this->productoRepository->getLast();
        $id = $lastId ? $lastId->idProducto : 0;
        return $id + 1;
    }

    private function generarCodigo($string)
    {
        $category = substr($string, 0, 6);

        // Si el código recibido es un prefijo de 6 caracteres o menos (primer producto del grupo)
        if (strlen($string) <= 6) {
            return $category . "0001";
        }

        $number = substr($string, 6, 10);
        $newnumber = str_pad((int)$number + 1, strlen($number), "0", STR_PAD_LEFT);
        return $category . $newnumber;
    }
    public function getYoutubeVideoId(?string $url): ?string
    {
        preg_match("/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|shorts\/))([a-zA-Z0-9_-]{11})/", $url, $matches);
        return $matches[1] ?? null;
    }

    public function existsByModelo(string $modelo, $excludeId = null): bool
    {
        return $this->productoRepository->existsByModelo($modelo, $excludeId);
    }

    public function getFechaUltimoIngreso(int $idProducto): ?\Carbon\Carbon
    {
        return $this->productoRepository->getFechaUltimoIngreso($idProducto);
    }

    public function getFechaUltimaVenta(int $idProducto): ?\Carbon\Carbon
    {
        return $this->productoRepository->getFechaUltimaVenta($idProducto);
    }

    public function obtenerMesesGarantiaProducto($producto, int $defaultMeses = 12): int
    {
        $prodModel = is_numeric($producto) ? $this->productoRepository->getOne('idProducto', $producto) : $producto;
        if (!$prodModel || empty($prodModel->garantia)) {
            return $defaultMeses;
        }

        $gUpper = strtoupper(trim((string) $prodModel->garantia));
        if (str_contains($gUpper, '36') || str_contains($gUpper, '3 AÑO') || str_contains($gUpper, '3 ANO')) {
            return max($defaultMeses, 36);
        }
        if (str_contains($gUpper, '24') || str_contains($gUpper, '2 AÑO') || str_contains($gUpper, '2 ANO')) {
            return max($defaultMeses, 24);
        }
        if (preg_match('/(\d+)\s*MES/i', $gUpper, $m)) {
            return max($defaultMeses, (int) $m[1]);
        }

        return $defaultMeses;
    }

    public function tieneVentaConGarantiaVigente($producto, int $mesesGarantia = 12): bool
    {
        $prodModel = is_numeric($producto) ? $this->productoRepository->getOne('idProducto', $producto) : $producto;
        if (!$prodModel) {
            return false;
        }

        $meses = $this->obtenerMesesGarantiaProducto($prodModel, $mesesGarantia);
        return $this->productoRepository->tieneVentaConGarantiaVigente((int) $prodModel->idProducto, $meses);
    }

    public function countFantasmasAutoReciclables(int $mesesGarantiaLaptop = 12): int
    {
        return $this->productoRepository->countFantasmasAutoReciclables($mesesGarantiaLaptop);
    }

    public function esLaptopConGarantiaVencida($producto, int $meses = 12): bool
    {
        $prodModel = is_numeric($producto) ? $this->productoRepository->getOne('idProducto', $producto) : $producto;
        if (!$prodModel || !empty($prodModel->es_padre)) {
            return false;
        }

        // 1. Debe ser Laptop (Grupos 1: Gamer, 2: Empresarial, 3: Hogar o Categoría 1 excluyendo accesorios)
        $idGrupo = (int) $prodModel->idGrupo;
        $esLaptop = in_array($idGrupo, [1, 2, 3], true)
            || (optional($prodModel->GrupoProducto)->idCategoria === 1 && !in_array($idGrupo, [4, 5, 69, 92, 101], true));

        if (!$esLaptop) {
            return false;
        }

        // 2. Debe tener stock físico en 0 en todos los almacenes
        $stockFisico = (int) \App\Models\Inventario\Inventario::where('idProducto', $prodModel->idProducto)->sum('stock');
        if ($stockFisico > 0) {
            return false;
        }

        // 3. Candado de venta: Si tiene alguna venta cuya garantía sigue vigente, NO tiene garantía vencida
        $mesesGarantia = $this->obtenerMesesGarantiaProducto($prodModel, $meses);
        if ($this->productoRepository->tieneVentaConGarantiaVigente($prodModel->idProducto, $mesesGarantia)) {
            return false;
        }

        // 4. Obtener fecha de último ingreso
        $ultimoIngreso = $this->productoRepository->getFechaUltimoIngreso($prodModel->idProducto);
        if (!$ultimoIngreso) {
            return true; // Sin ingresos previos ni ventas vigentes
        }

        // 5. Último ingreso debe tener más de $mesesGarantia de antigüedad (ej. 12 meses)
        return $ultimoIngreso->lt(now()->subMonths($mesesGarantia));
    }

    public function esReemplazable($producto, int $mesesGarantiaLaptop = 12): bool
    {
        $prodModel = is_numeric($producto) ? $this->productoRepository->getOne('idProducto', $producto) : $producto;
        if (!$prodModel || !empty($prodModel->es_padre)) {
            return false;
        }

        // Candado global: Si el producto registra una venta con garantía vigente, NO puede ser reemplazado
        $mesesGarantia = $this->obtenerMesesGarantiaProducto($prodModel, $mesesGarantiaLaptop);
        if ($this->productoRepository->tieneVentaConGarantiaVigente($prodModel->idProducto, $mesesGarantia)) {
            return false;
        }

        // Caso 1: Nunca tuvo ingresos (ni ventas con garantía vigente)
        if (!$this->productoRepository->tieneIngresos($prodModel->idProducto)) {
            return true;
        }

        // Caso 2: Laptop con garantía vencida (tanto de ingreso como de venta) y sin stock
        return $this->esLaptopConGarantiaVencida($prodModel, $mesesGarantia);
    }

    public function getMotivoReemplazo($producto, int $mesesGarantiaLaptop = 12): ?array
    {
        $prodModel = is_numeric($producto) ? $this->productoRepository->getOne('idProducto', $producto) : $producto;
        if (!$prodModel || !empty($prodModel->es_padre)) {
            return null;
        }

        if (!$this->esReemplazable($prodModel, $mesesGarantiaLaptop)) {
            return null;
        }

        $ultimoIngreso = $this->productoRepository->getFechaUltimoIngreso($prodModel->idProducto);
        $ultimaVenta = $this->productoRepository->getFechaUltimaVenta($prodModel->idProducto);

        if (!$ultimoIngreso && !$ultimaVenta) {
            return [
                'tipo' => 'sin_ingresos',
                'titulo' => 'Sin Ingresos en Almacén',
                'descripcion' => 'Este producto nunca tuvo ingresos ni ventas registradas en almacén.',
                'badge' => 'Sin ingresos (Reemplazable)',
                'color' => 'warning'
            ];
        }

        if ($this->esLaptopConGarantiaVencida($prodModel, $mesesGarantiaLaptop) || !$ultimoIngreso) {
            $fechaRef = $ultimaVenta ?: $ultimoIngreso;
            $mesesTranscurridos = $fechaRef ? (int) $fechaRef->diffInMonths(now()) : $mesesGarantiaLaptop;
            $detalleVenta = $ultimaVenta ? " · Última venta: {$ultimaVenta->format('d/m/Y')}" : '';
            $detalleIngreso = $ultimoIngreso ? "Último ingreso: {$ultimoIngreso->format('d/m/Y')}" : 'Sin ingresos registrados';

            return [
                'tipo' => 'laptop_garantia_vencida',
                'titulo' => 'Garantía Vencida (+12 meses)',
                'descripcion' => "{$detalleIngreso}{$detalleVenta} (hace {$mesesTranscurridos} meses) y stock actual en 0.",
                'badge' => "Garantía vencida (+{$mesesTranscurridos}m)",
                'color' => 'danger'
            ];
        }

        return null;
    }
}
