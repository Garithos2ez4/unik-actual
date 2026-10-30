<?php

namespace App\Services\Deltron;

use App\Models\Catalogo\DetalleProducto;
use App\Models\Catalogo\GrupoProducto;
use App\Models\Catalogo\MarcaProducto;
use App\Models\Catalogo\Producto;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Inventario;
use App\Models\Inventario\Inventario_Proveedor;
use App\Models\Inventario\InventarioProveedorSku;
use App\Services\ImageService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeltronImportService
{
    protected ?DeltronApiService $apiService;
    protected DeltronClassifierService $classifierService;

    public function __construct(
        ?DeltronApiService $apiService = null,
        ?DeltronClassifierService $classifierService = null
    ) {
        $this->apiService = $apiService;
        $this->classifierService = $classifierService ?: new DeltronClassifierService();
    }

    public function setApiService(DeltronApiService $apiService): self
    {
        $this->apiService = $apiService;
        return $this;
    }

    protected function getApiService(): DeltronApiService
    {
        if (!$this->apiService) {
            $this->apiService = app(DeltronApiService::class);
        }
        return $this->apiService;
    }

    /**
     * Obtiene todos los productos en oferta de Deltron con stock masivo en Lima que no existen en Unike.
     */
    public function getCandidatosOfertasAltoStock(): array
    {
        $cat = $this->getApiService()->getCatalogo();
        if (empty($cat)) {
            $snapshotPath = storage_path('app/deltron_catalogo_snapshot.json');
            if (file_exists($snapshotPath)) {
                $cat = @json_decode(file_get_contents($snapshotPath), true) ?: [];
            }
        }

        $existingSkus = InventarioProveedorSku::whereNotNull('sku_proveedor')
            ->pluck('sku_proveedor')
            ->map(fn($s) => strtoupper(trim($s)))
            ->flip()
            ->toArray();

        $existingModelos = [];
        $existingModelosLimpios = [];
        foreach (Producto::whereNotNull('modelo')->where('modelo', '!=', '')->pluck('modelo') as $m) {
            $mUpper = strtoupper(trim($m));
            $existingModelos[$mUpper] = true;
            $mClean = preg_replace('/[^A-Z0-9]/', '', $mUpper);
            if (!empty($mClean)) {
                $existingModelosLimpios[$mClean] = true;
            }
        }

        $existingPartNumbers = [];
        foreach (Producto::whereNotNull('partNumber')->where('partNumber', '!=', '')->where('partNumber', '!=', '0')->pluck('partNumber') as $pn) {
            $pnUpper = strtoupper(trim($pn));
            $pnClean = preg_replace('/[^A-Z0-9]/', '', $pnUpper);
            if (!empty($pnClean)) {
                $existingPartNumbers[$pnClean] = true;
            }
        }

        $grupos = GrupoProducto::with('CategoriaProducto')->get()->keyBy('idGrupoProducto');
        $marcas = MarcaProducto::all();

        $candidatos = [];

        foreach ($cat as $item) {
            $sku = strtoupper(trim($item['sku'] ?? ''));
            if (empty($sku)) continue;

            $pn = strtoupper(trim($item['part_number'] ?? ''));
            $pnClean = preg_replace('/[^A-Z0-9]/', '', $pn);

            // Obtener el atributo modelo oficial de Deltron
            $modeloReal = DeltronTextHelper::extraerModeloDesdeItem($item);
            $modClean = $modeloReal ? preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($modeloReal))) : '';

            // Verificar si ya existe registrado en Unike (por SKU, Part Number o Modelo oficial de Deltron)
            if (isset($existingSkus[$sku]) || isset($existingModelos[$sku])) {
                continue;
            }
            if (!empty($pnClean) && (isset($existingPartNumbers[$pnClean]) || isset($existingModelosLimpios[$pnClean]) || isset($existingModelos[$pn]))) {
                continue;
            }
            if (!empty($modClean) && (isset($existingModelosLimpios[$modClean]) || isset($existingPartNumbers[$modClean]) || isset($existingModelos[$modeloReal]))) {
                continue;
            }

            $price = (float)($item['price'] ?? 0);
            $pricePromo = isset($item['price_promo']) && $item['price_promo'] !== null ? (float)$item['price_promo'] : null;
            $hasPromo = ($pricePromo !== null && $pricePromo > 0 && $pricePromo < $price) || !empty($item['promociones']);

            if (!$hasPromo) continue;

            $totalStock = 0;
            $limaStock = 0;
            $limaHayMas = false;

            foreach ($item['stock']['almacenes'] ?? [] as $alm) {
                $cant = (int)($alm['cantidad'] ?? 0);
                $totalStock += $cant;
                $almName = strtoupper($alm['almacen_name'] ?? '');
                if (str_contains($almName, 'LIMA')) {
                    $limaStock += $cant;
                    if (!empty($alm['hay_mas'])) {
                        $limaHayMas = true;
                    }
                }
            }

            // Filtro: Oferta con stock masivo en Lima (hay_mas=true, stock Lima >= 20 o stock nacional >= 100)
            if ($totalStock >= 100 || $limaHayMas || $limaStock >= 20) {
                $clasif = $this->classifierService->clasificarGrupoSugerido($item, $grupos);
                $idMarca = $this->classifierService->emparejarMarca($item['brand_name'] ?? '', $marcas);
                $marcaObj = $marcas->firstWhere('idMarca', $idMarca);

                // Deteccion de variantes (color/modelo)
                $variante = DeltronClassifierService::detectarVarianteDeltron($item);
                $baseInfo = DeltronClassifierService::extraerModeloBaseYNombrePadre($item, $variante);
                $basePn = $baseInfo['part_number_base'];
                $familiaKey = $idMarca . '|' . $basePn;

                // Obtener Foto 1 (Principal) valida
                $foto1 = null;
                $imgListado = $item['images']['image_listado'] ?? null;
                if (is_string($imgListado) && !empty($imgListado) && !str_contains($imgListado, 'blanco.jpg')) {
                    $foto1 = $imgListado;
                }
                if (!$foto1 && !empty($item['images']['images_carrusel']) && is_array($item['images']['images_carrusel'])) {
                    foreach ($item['images']['images_carrusel'] as $c) {
                        $cUrl = is_string($c) ? $c : ($c['url'] ?? ($c['image'] ?? null));
                        if (is_string($cUrl) && !empty($cUrl) && !str_contains($cUrl, 'blanco.jpg')) {
                            $foto1 = $cUrl;
                            break;
                        }
                    }
                }
                if (!$foto1) {
                    $foto1 = DeltronTextHelper::obtenerUrlCdnDeltron($sku);
                }

                $candidatos[] = [
                    'sku' => $sku,
                    'part_number' => $item['part_number'] ?? $sku,
                    'modelo_real' => $modeloReal,
                    'name' => DeltronTextHelper::obtenerTituloComercialDeltron($item),
                    'deltron_name' => DeltronTextHelper::cleanDeltronText($item['name'] ?? ''),
                    'brand_name' => $item['brand_name'] ?? ($marcaObj ? $marcaObj->nombreMarca : 'Teros'),
                    'idMarca' => $idMarca,
                    'idGrupo' => $clasif['idGrupo'],
                    'nombreGrupo' => $clasif['nombreGrupo'],
                    'idCategoria' => $clasif['idCategoria'],
                    'nombreCategoria' => $clasif['nombreCategoria'],
                    'price' => $price,
                    'price_promo' => $pricePromo ?: ($item['promociones'][0]['precio'] ?? null),
                    'total_stock' => $totalStock,
                    'lima_stock' => $limaStock,
                    'lima_hay_mas' => $limaHayMas,
                    'foto1_url' => $foto1,
                    'mini_code' => $item['mini_code'] ?? null,
                    'warranty' => DeltronTextHelper::normalizarGarantiaDeltron($item['warranty'] ?? null),
                    'description' => DeltronTextHelper::construirDescripcionProducto($item),
                    'es_variante' => ($variante !== null),
                    'familia_key' => $familiaKey,
                    'part_number_base' => $basePn,
                    'modelo_base' => $baseInfo['modelo_base'],
                    'nombre_padre' => $baseInfo['nombre_padre'],
                    'tipo_variante' => $variante['tipo'] ?? null,
                    'valor_variante' => $variante['valor'] ?? null,
                    'codigo_color_hex' => $variante['hex'] ?? null,
                ];
            }
        }

        return $candidatos;
    }

    /**
     * Obtiene los productos candidatos del catalogo completo de Deltron que no existen en Unike,
     * paginados de 100 en 100 y optimizados para minimo uso de memoria RAM.
     */
    public function getCandidatosCatalogoPaginado(array $filtros = [], int $page = 1, int $perPage = 100): array
    {
        @ini_set('memory_limit', '512M');

        $cat = $this->getApiService()->getCatalogo();
        if (empty($cat)) {
            $snapshotPath = storage_path('app/deltron_catalogo_snapshot.json');
            if (file_exists($snapshotPath)) {
                $cat = @json_decode(file_get_contents($snapshotPath), true) ?: [];
            }
        }

        if (empty($cat)) {
            return [
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => 1,
                'items' => [],
            ];
        }

        // Cargar indices existentes de Unike de forma ligera
        $existingSkus = InventarioProveedorSku::whereNotNull('sku_proveedor')
            ->pluck('sku_proveedor')
            ->map(fn($s) => strtoupper(trim($s)))
            ->flip()
            ->toArray();

        $existingModelos = [];
        $existingModelosLimpios = [];
        foreach (Producto::whereNotNull('modelo')->where('modelo', '!=', '')->pluck('modelo') as $m) {
            $mUpper = strtoupper(trim($m));
            $existingModelos[$mUpper] = true;
            $mClean = preg_replace('/[^A-Z0-9]/', '', $mUpper);
            if (!empty($mClean)) {
                $existingModelosLimpios[$mClean] = true;
            }
        }

        $existingPartNumbers = [];
        foreach (Producto::whereNotNull('partNumber')->where('partNumber', '!=', '')->where('partNumber', '!=', '0')->pluck('partNumber') as $pn) {
            $pnUpper = strtoupper(trim($pn));
            $pnClean = preg_replace('/[^A-Z0-9]/', '', $pnUpper);
            if (!empty($pnClean)) {
                $existingPartNumbers[$pnClean] = true;
            }
        }

        $query = strtoupper(trim($filtros['query'] ?? ''));
        $soloConStock = !empty($filtros['solo_con_stock']);
        $filtroMarca = strtoupper(trim($filtros['marca'] ?? ''));

        // Filtrado preliminar liviano
        $candidatosCrudos = [];

        foreach ($cat as $item) {
            $sku = strtoupper(trim($item['sku'] ?? ''));
            if (empty($sku)) continue;

            $pn = strtoupper(trim($item['part_number'] ?? ''));
            $pnClean = preg_replace('/[^A-Z0-9]/', '', $pn);

            // Obtener el atributo modelo oficial de Deltron
            $modeloReal = DeltronTextHelper::extraerModeloDesdeItem($item);
            $modClean = $modeloReal ? preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($modeloReal))) : '';

            // Verificar si ya existe en Unike (por SKU, Part Number o Modelo oficial de Deltron)
            if (isset($existingSkus[$sku]) || isset($existingModelos[$sku])) {
                continue;
            }
            if (!empty($pnClean) && (isset($existingPartNumbers[$pnClean]) || isset($existingModelosLimpios[$pnClean]) || isset($existingModelos[$pn]))) {
                continue;
            }
            if (!empty($modClean) && (isset($existingModelosLimpios[$modClean]) || isset($existingPartNumbers[$modClean]) || isset($existingModelos[$modeloReal]))) {
                continue;
            }

            // Calcular stock total
            $totalStock = 0;
            $limaStock = 0;
            $limaHayMas = false;
            foreach ($item['stock']['almacenes'] ?? [] as $alm) {
                $cant = (int)($alm['cantidad'] ?? 0);
                $totalStock += $cant;
                $almName = strtoupper($alm['almacen_name'] ?? '');
                if (str_contains($almName, 'LIMA')) {
                    $limaStock += $cant;
                    if (!empty($alm['hay_mas'])) {
                        $limaHayMas = true;
                    }
                }
            }

            if ($soloConStock && $totalStock <= 0 && !$limaHayMas) {
                continue;
            }

            $itemMarca = strtoupper(trim($item['brand_name'] ?? ''));
            if ($filtroMarca !== '' && $itemMarca !== $filtroMarca) {
                continue;
            }

            // Filtro de busqueda si se proporciona
            if ($query !== '') {
                $nombre = strtoupper($item['name'] ?? '');
                $categoria = strtoupper($item['category_name'] ?? '');
                if (
                    !str_contains($sku, $query) &&
                    !str_contains($pn, $query) &&
                    !str_contains($nombre, $query) &&
                    !str_contains($itemMarca, $query) &&
                    !str_contains($categoria, $query)
                ) {
                    continue;
                }
            }

            $candidatosCrudos[] = [
                'raw' => $item,
                'sku' => $sku,
                'pn' => $pn,
                'totalStock' => $totalStock,
                'limaStock' => $limaStock,
                'limaHayMas' => $limaHayMas,
            ];
        }

        $total = count($candidatosCrudos);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $sliced = array_slice($candidatosCrudos, $offset, $perPage);

        // Procesar en detalle SOLO los items de la pagina actual
        $grupos = GrupoProducto::with('CategoriaProducto')->get()->keyBy('idGrupoProducto');
        $marcas = MarcaProducto::all();

        $itemsProcesados = [];

        foreach ($sliced as $c) {
            $item = $c['raw'];
            $sku = $c['sku'];
            $pn = $c['pn'];
            $totalStock = $c['totalStock'];
            $limaStock = $c['limaStock'];
            $limaHayMas = $c['limaHayMas'];

            $modeloReal = DeltronTextHelper::extraerModeloRealDesdeSpecs($item['specs_all_html'] ?? ($item['specs_html'] ?? ''));

            $clasif = $this->classifierService->clasificarGrupoSugerido($item, $grupos);
            $idMarca = $this->classifierService->emparejarMarca($item['brand_name'] ?? '', $marcas);
            $marcaObj = $marcas->firstWhere('idMarca', $idMarca);

            // Deteccion de variantes (color/modelo)
            $variante = DeltronClassifierService::detectarVarianteDeltron($item);
            $baseInfo = DeltronClassifierService::extraerModeloBaseYNombrePadre($item, $variante);
            $basePn = $baseInfo['part_number_base'];
            $familiaKey = $idMarca . '|' . $basePn;

            // Foto 1
            $foto1 = null;
            $imgListado = $item['images']['image_listado'] ?? null;
            if (is_string($imgListado) && !empty($imgListado) && !str_contains($imgListado, 'blanco.jpg')) {
                $foto1 = $imgListado;
            }
            if (!$foto1 && !empty($item['images']['images_carrusel']) && is_array($item['images']['images_carrusel'])) {
                foreach ($item['images']['images_carrusel'] as $cr) {
                    $cUrl = is_string($cr) ? $cr : ($cr['url'] ?? ($cr['image'] ?? null));
                    if (is_string($cUrl) && !empty($cUrl) && !str_contains($cUrl, 'blanco.jpg')) {
                        $foto1 = $cUrl;
                        break;
                    }
                }
            }
            if (!$foto1) {
                $foto1 = DeltronTextHelper::obtenerUrlCdnDeltron($sku);
            }

            $price = (float)($item['price'] ?? 0);
            $pricePromo = isset($item['price_promo']) && $item['price_promo'] !== null ? (float)$item['price_promo'] : null;

            $itemsProcesados[] = [
                'sku' => $sku,
                'part_number' => $item['part_number'] ?? $sku,
                'modelo_real' => $modeloReal,
                'name' => DeltronTextHelper::obtenerTituloComercialDeltron($item),
                'deltron_name' => DeltronTextHelper::cleanDeltronText($item['name'] ?? ''),
                'brand_name' => $item['brand_name'] ?? ($marcaObj ? $marcaObj->nombreMarca : 'Teros'),
                'idMarca' => $idMarca,
                'idGrupo' => $clasif['idGrupo'],
                'nombreGrupo' => $clasif['nombreGrupo'],
                'idCategoria' => $clasif['idCategoria'],
                'nombreCategoria' => $clasif['nombreCategoria'],
                'price' => $price,
                'price_promo' => $pricePromo ?: ($item['promociones'][0]['precio'] ?? null),
                'total_stock' => $totalStock,
                'lima_stock' => $limaStock,
                'lima_hay_mas' => $limaHayMas,
                'foto1_url' => $foto1,
                'mini_code' => $item['mini_code'] ?? null,
                'warranty' => DeltronTextHelper::normalizarGarantiaDeltron($item['warranty'] ?? null),
                'description' => DeltronTextHelper::construirDescripcionProducto($item),
                'es_variante' => ($variante !== null),
                'familia_key' => $familiaKey,
                'part_number_base' => $basePn,
                'modelo_base' => $baseInfo['modelo_base'],
                'nombre_padre' => $baseInfo['nombre_padre'],
                'tipo_variante' => $variante['tipo'] ?? null,
                'valor_variante' => $variante['valor'] ?? null,
                'codigo_color_hex' => $variante['hex'] ?? null,
            ];
        }

        return [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
            'items' => $itemsProcesados,
        ];
    }

    /**
     * Importa un micro-lote de productos seleccionados descargando la Foto 1 en WebP 800x800.
     * Soporta la creacion automatica de productos contenedores Padre y sus variantes Hijas (Colores).
     */
    public function importarLoteOfertas(array $itemsAImportar): array
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(180);

        $imgService = new ImageService();
        $destinationPath = DeltronTextHelper::getDestinationPathProductos();

        $grupos = GrupoProducto::all()->keyBy('idGrupoProducto');
        $almacenes = Almacen::all();

        $importados = [];
        $errores = [];

        foreach ($itemsAImportar as $itemData) {
            $sku = strtoupper(trim($itemData['sku'] ?? ''));
            if (empty($sku)) continue;

            // Verificar duplicados en caliente
            $duplicado = Producto::where('modelo', $sku)->exists() || 
                InventarioProveedorSku::where('sku_proveedor', $sku)->exists();

            if ($duplicado) {
                continue;
            }

            try {
                DB::transaction(function () use ($itemData, $sku, $imgService, $destinationPath, $grupos, $almacenes, $itemsAImportar, &$importados) {
                    $idGrupo = (int)($itemData['idGrupo'] ?? 25);
                    $grupoObj = $grupos->get($idGrupo);
                    $nombreGrupo = $grupoObj ? $grupoObj->nombreGrupo : 'Perifericos';

                    $idMarca = (int)($itemData['idMarca'] ?? 1);
                    $pn = substr(DeltronTextHelper::cleanDeltronText($itemData['part_number'] ?? $sku), 0, 50);

                    // Asegurar titulo comercial limpio
                    $nombre = !empty($itemData['name']) ? trim(DeltronTextHelper::cleanDeltronText($itemData['name'])) : '';
                    if (empty($nombre) || (mb_strlen($nombre) <= 30 && strtoupper($nombre) === $nombre)) {
                        $nombre = DeltronTextHelper::obtenerTituloComercialDeltron($itemData);
                    }
                    $nombre = substr($nombre, 0, 200);

                    // Regla de negocio: Usar siempre el precio mas alto (precio lista B2B base, NO el de promo)
                    $precioLista = (float)($itemData['price'] ?? 0);
                    $precioPromo = (float)($itemData['price_promo'] ?? 0);
                    $precioDolar = max($precioLista, $precioPromo);
                    if ($precioDolar <= 0) {
                        $precioDolar = $precioLista > 0 ? $precioLista : $precioPromo;
                    }

                    // Logica de variantes: comprobar si este item debe enlazarse a un Contenedor Padre
                    $esVariante = !empty($itemData['es_variante']) && !empty($itemData['valor_variante']);
                    $basePn = !empty($itemData['part_number_base']) ? trim($itemData['part_number_base']) : $pn;
                    $nombrePadre = !empty($itemData['nombre_padre']) ? trim($itemData['nombre_padre']) : $nombre;
                    $modeloBase = !empty($itemData['modelo_base']) ? trim($itemData['modelo_base']) : $basePn;

                    $padre = null;
                    if ($esVariante) {
                        $padre = Producto::where('es_padre', 1)
                            ->where('partNumber', $basePn)
                            ->where('idMarca', $idMarca)
                            ->first();

                        if (!$padre) {
                            $familiaKey = $itemData['familia_key'] ?? ($idMarca . '|' . $basePn);
                            $coincidentesEnLote = array_filter($itemsAImportar, function($it) use ($familiaKey, $idMarca, $basePn) {
                                $fk = $it['familia_key'] ?? ($idMarca . '|' . ($it['part_number_base'] ?? ($it['part_number'] ?? '')));
                                return $fk === $familiaKey;
                            });

                            if (count($coincidentesEnLote) >= 2 || !empty($itemData['forzar_padre'])) {
                                $lastIdPadre = Producto::max('idProducto') ?? 0;
                                $idPadre = $lastIdPadre + 1;
                                $codigoPadre = $this->classifierService->generarSiguienteCodigoProducto($idGrupo, $nombreGrupo);

                                $descPadre = !empty($itemData['description']) ? DeltronTextHelper::cleanDeltronText($itemData['description']) : $nombrePadre;
                                $descPadre = DeltronTextHelper::recortarTextoEnLinea($descPadre, 5000);

                                $padre = Producto::create([
                                    'idProducto' => $idPadre,
                                    'nombreProducto' => substr($nombrePadre, 0, 200),
                                    'codigoProducto' => $codigoPadre,
                                    'UPC' => '0',
                                    'partNumber' => substr($basePn, 0, 50),
                                    'idMarca' => $idMarca,
                                    'idGrupo' => $idGrupo,
                                    'modelo' => substr($modeloBase, 0, 70),
                                    'precioDolar' => $precioDolar,
                                    'gananciaExtra' => 0.00,
                                    'garantia' => DeltronTextHelper::normalizarGarantiaDeltron($itemData['warranty'] ?? null),
                                    'descripcionProducto' => $descPadre,
                                    'imagenProducto1' => 'noimagen.webp',
                                    'imagenProducto2' => null,
                                    'imagenProducto3' => null,
                                    'imagenProducto4' => null,
                                    'estadoProductoWeb' => 'DISPONIBLE',
                                    'stockMin' => 1,
                                    'usar_tc_fijo' => 1,
                                    'es_padre' => 1,
                                    'idProductoPadre' => null,
                                ]);
                                $idPadre = $padre->idProducto;

                                DetalleProducto::updateOrCreate(
                                    ['idProducto' => $idPadre],
                                    [
                                        'mostrarPrecioWeb' => true,
                                        'tipo_variante' => $itemData['tipo_variante'] ?? 'COLOR'
                                    ]
                                );

                                foreach ($almacenes as $alm) {
                                    Inventario::create([
                                        'idProducto' => $idPadre,
                                        'idAlmacen' => $alm->idAlmacen,
                                        'stock' => 0
                                    ]);
                                }
                            }
                        }
                    }

                    $lastId = Producto::max('idProducto') ?? 0;
                    $idProducto = $lastId + 1;
                    $codigo = $this->classifierService->generarSiguienteCodigoProducto($idGrupo, $nombreGrupo);

                    // Descargar Foto 1 (Principal) en WebP 800x800
                    $foto1Url = $itemData['foto1_url'] ?? null;
                    if (empty($foto1Url) || str_contains($foto1Url, 'blanco.jpg')) {
                        $foto1Url = DeltronTextHelper::obtenerUrlCdnDeltron($sku);
                    }
                    $foto1Final = 'noimagen.webp';

                    if (!empty($foto1Url) && is_string($foto1Url) && !str_contains($foto1Url, 'blanco.jpg')) {
                        try {
                            $saved = $imgService->createImage($foto1Url, $idProducto . '_1', $destinationPath);
                            if ($saved) {
                                $foto1Final = 'productos/IMGPRO' . $idProducto . '_1.webp';
                            }
                        } catch (Exception $imgEx) {
                            Log::warning("No se pudo procesar Foto 1 de Deltron para {$sku}: " . $imgEx->getMessage());
                            $foto1Final = 'noimagen.webp';
                        }
                    }

                    // Titulo y relaciones segun si es variante de un padre o producto simple
                    if ($padre) {
                        $nombreFinal = substr($padre->nombreProducto . ' - ' . ($itemData['valor_variante'] ?? 'Color'), 0, 200);
                        $esPadreVal = 0;
                        $idPadreVal = $padre->idProducto;

                        if ($padre->imagenProducto1 === 'noimagen.webp' && $foto1Final !== 'noimagen.webp') {
                            $padre->imagenProducto1 = $foto1Final;
                            $padre->save();
                        }
                    } else {
                        $nombreFinal = $nombre;
                        $esPadreVal = 0;
                        $idPadreVal = null;
                    }

                    $desc = !empty($itemData['description']) ? DeltronTextHelper::cleanDeltronText($itemData['description']) : $nombreFinal;
                    $desc = DeltronTextHelper::recortarTextoEnLinea($desc, 5000);

                    // 1. Crear Producto
                    $prod = Producto::create([
                        'idProducto' => $idProducto,
                        'nombreProducto' => $nombreFinal,
                        'codigoProducto' => $codigo,
                        'UPC' => '0',
                        'partNumber' => $pn,
                        'idMarca' => $idMarca,
                        'idGrupo' => $idGrupo,
                        'modelo' => substr($itemData['modelo_real'] ?? ($pn ?: $sku), 0, 70),
                        'precioDolar' => $precioDolar,
                        'gananciaExtra' => 0.00,
                        'garantia' => DeltronTextHelper::normalizarGarantiaDeltron($itemData['warranty'] ?? null),
                        'descripcionProducto' => $desc,
                        'imagenProducto1' => $foto1Final,
                        'imagenProducto2' => null,
                        'imagenProducto3' => null,
                        'imagenProducto4' => null,
                        'estadoProductoWeb' => 'DISPONIBLE',
                        'stockMin' => 1,
                        'usar_tc_fijo' => 1,
                        'es_padre' => $esPadreVal,
                        'idProductoPadre' => $idPadreVal,
                    ]);
                    $idProducto = $prod->idProducto;

                    // 2. Crear DetalleProducto
                    $detalleData = ['mostrarPrecioWeb' => true];
                    if ($padre && !empty($itemData['valor_variante'])) {
                        $detalleData['tipo_variante'] = $itemData['tipo_variante'] ?? 'COLOR';
                        $detalleData['valor_variante'] = $itemData['valor_variante'];
                        $detalleData['codigo_color_hex'] = $itemData['codigo_color_hex'] ?? null;
                    }

                    DetalleProducto::updateOrCreate(
                        ['idProducto' => $idProducto],
                        $detalleData
                    );

                    // 3. Crear registros de inventario fisico en cero para cada almacen local
                    foreach ($almacenes as $alm) {
                        Inventario::create([
                            'idProducto' => $idProducto,
                            'idAlmacen' => $alm->idAlmacen,
                            'stock' => 0
                        ]);
                    }

                    // 4. Crear Inventario_Proveedor (Deltron)
                    $lastInvProv = Inventario_Proveedor::max('idInventarioProveedor') ?? 0;
                    $idInvProv = $lastInvProv + 1;
                    $stockProv = (int)($itemData['lima_stock'] ?? ($itemData['total_stock'] ?? 1));

                    Inventario_Proveedor::create([
                        'idInventarioProveedor' => $idInvProv,
                        'idProducto' => $idProducto,
                        'idProveedor' => 1,
                        'stock' => max(1, $stockProv),
                        'estado' => 'VIGENTE'
                    ]);

                    // 5. Vincular SKU proveedor
                    InventarioProveedorSku::updateOrCreate(
                        [
                            'idInventarioProveedor' => $idInvProv,
                            'idProveedor' => 1,
                        ],
                        [
                            'sku_proveedor' => $sku,
                            'part_number' => $pn,
                            'mini_codigo' => $itemData['mini_code'] ?? null,
                            'precio_b2b' => (float)($itemData['price'] ?? 0),
                            'precio_promo' => !empty($itemData['price_promo']) ? (float)$itemData['price_promo'] : null,
                            'hay_mas' => !empty($itemData['lima_hay_mas']),
                        ]
                    );

                    // 6. Autollenar especificaciones técnicas desde la ficha de Deltron
                    //    (solo snapshot local: no consume el límite horario de la API en lotes masivos)
                    try {
                        $specMapper = new DeltronSpecMapperService($this->getApiService());
                        $specMapper->llenarEspecificacionesProducto($prod, $sku, true);
                    } catch (\Throwable $e) {
                        Log::warning("No se pudieron autollenar specs para {$sku}: " . $e->getMessage());
                    }

                    $importados[] = [
                        'idProducto' => $idProducto,
                        'sku' => $sku,
                        'codigoProducto' => $codigo,
                        'nombreProducto' => $nombreFinal,
                        'precioDolar' => $precioDolar,
                        'foto1' => $foto1Final,
                        'es_variante' => ($padre !== null),
                        'idProductoPadre' => $padre ? $padre->idProducto : null,
                        'codigoPadre' => $padre ? $padre->codigoProducto : null,
                    ];
                });
            } catch (Exception $e) {
                Log::error("Error importando oferta SKU {$sku}: " . $e->getMessage());
                $errores[] = [
                    'sku' => $sku,
                    'error' => $e->getMessage()
                ];
            }

            gc_collect_cycles();
        }

        return [
            'success' => true,
            'importados_count' => count($importados),
            'importados' => $importados,
            'errores' => $errores
        ];
    }

    /**
     * Completa las fotos secundarias (2, 3 y 4) de un producto (saltándose la Foto 1 que ya se descarga en la importación masiva,
     * salvo que la Foto 1 esté vacía). Ordena las imágenes secundarias (_1, _2, _3) y procesa en memoria con mínimo uso de RAM.
     *
     * @return array{success:bool,message:string,agregadas:int,slots:array,sku:?string}
     */
    public function completarFotosProducto(Producto $producto): array
    {
        // 1. Resolver SKU Deltron del producto
        $sku = null;
        $inv = Inventario_Proveedor::where('idProducto', $producto->idProducto)->first();
        if ($inv) {
            $sku = InventarioProveedorSku::where('idInventarioProveedor', $inv->idInventarioProveedor)
                ->whereNotNull('sku_proveedor')
                ->where('sku_proveedor', '!=', '')
                ->value('sku_proveedor');
        }
        if (!$sku) {
            return [
                'success' => false,
                'message' => 'Este producto no tiene un SKU de Deltron vinculado, no se pueden buscar fotos.',
                'agregadas' => 0,
                'slots' => [],
                'sku' => null,
            ];
        }
        $skuClean = strtoupper(trim($sku));

        // 2. Determinar qué slots llenar:
        // Se salta siempre la Foto 1 (a menos que esté vacía con noimagen.webp) y se completan los slots 2, 3 y 4.
        // Si los slots 2..4 ya tenían imágenes (p.ej. una duplicada previa), los reemplaza con la secuencia correcta _1, _2, _3.
        $default = asset('storage/noimagen.webp');
        $actuales = $producto->publicImages();
        $faltaFoto1 = ($actuales[0] ?? $default) === $default;

        $slotsSecundariosVacios = [];
        for ($s = 2; $s <= 4; $s++) {
            if (($actuales[$s - 1] ?? $default) === $default) {
                $slotsSecundariosVacios[] = $s;
            }
        }
        // Si todos los secundarios están ocupados y el usuario pulsa el botón, refresca los slots 2, 3 y 4
        $slotsSecundarios = !empty($slotsSecundariosVacios) ? $slotsSecundariosVacios : [2, 3, 4];

        // 3. Obtener metadatos de imágenes de forma ultraligera (primero desde el JSON local en disco sin parsear HTML)
        $imagesMeta = null;
        $snapshotPath = storage_path('app/deltron_fichas/' . $skuClean . '.json');
        if (is_file($snapshotPath)) {
            $saved = @json_decode(@file_get_contents($snapshotPath), true);
            if (!empty($saved['images'])) {
                $imagesMeta = $saved['images'];
            }
        }
        if (!$imagesMeta) {
            try {
                $raw = $this->getApiService()->getProductoPorSku($skuClean);
                if (is_array($raw) && !empty($raw['images'])) {
                    $imagesMeta = [
                        'listado' => $raw['images']['image_listado'] ?? null,
                        'carrusel' => $raw['images']['images_carrusel'] ?? [],
                    ];
                }
            } catch (Exception $e) {
                Log::warning("Completar fotos {$skuClean}: aviso al consultar API Deltron: " . $e->getMessage());
            }
        }

        // 4. Construir lista ordenada de URLs SECUNDARIAS (excluyendo siempre la portada / Foto 1)
        $secundariasOrdenadas = [];
        $secundariasSinIndice = [];
        $foto1Candidata = null;

        if (!empty($imagesMeta['listado']) && is_string($imagesMeta['listado']) && !str_contains($imagesMeta['listado'], 'blanco.jpg')) {
            $foto1Candidata = $imagesMeta['listado'];
        }

        foreach (($imagesMeta['carrusel'] ?? []) as $c) {
            $cUrl = is_string($c) ? $c : ($c['url'] ?? ($c['image'] ?? null));
            if (!is_string($cUrl) || !str_starts_with($cUrl, 'http') || str_contains($cUrl, 'blanco.jpg')) {
                continue;
            }
            $base = basename(parse_url($cUrl, PHP_URL_PATH) ?: $cUrl);

            // Detectar si es la portada / Foto 1 ({SKU}_{timestamp}.jpg sin subíndice o 00_portada o is_main)
            $esPortada = !empty($c['is_main'])
                || preg_match('/^00_portada/i', $base)
                || preg_match('/^' . preg_quote($skuClean, '/') . '_\d{6,}\./i', $base);

            if ($esPortada) {
                $foto1Candidata ??= $cUrl;
                continue; // Saltar la Foto 1 para no duplicarla en el slot 2
            }

            if (preg_match('/_(\d+)_\d{6,}\./', $base, $m) || preg_match('/^0*([1-9]\d*)_/', $base, $m)) {
                $idx = (int) $m[1];
                $secundariasOrdenadas[$idx] = $cUrl;
            } else {
                $secundariasSinIndice[] = $cUrl;
            }
        }

        ksort($secundariasOrdenadas, SORT_NUMERIC);
        $urlsSecundarias = array_values($secundariasOrdenadas);
        foreach ($secundariasSinIndice as $u) {
            if (!in_array($u, $urlsSecundarias, true)) {
                $urlsSecundarias[] = $u;
            }
        }

        // Agregar fallback del CDN de Deltron: solo _1.jpg, _2.jpg, _3.jpg, _4.jpg (nunca {sku}.jpg que es la Foto 1)
        $cdnBase = DeltronTextHelper::obtenerUrlCdnDeltron($skuClean);
        if ($cdnBase) {
            $foto1Candidata ??= $cdnBase;
            if (count($urlsSecundarias) < count($slotsSecundarios)) {
                for ($n = 1; $n <= 4; $n++) {
                    $uCdn = preg_replace('/\.jpg$/i', "_{$n}.jpg", $cdnBase);
                    if (!in_array($uCdn, $urlsSecundarias, true)) {
                        $urlsSecundarias[] = $uCdn;
                    }
                }
            }
        }

        // 5. Descargar y convertir en memoria (1 imagen a la vez para consumo mínimo de RAM)
        $destinationPath = DeltronTextHelper::getDestinationPathProductos();
        if (!is_dir($destinationPath)) {
            @mkdir($destinationPath, 0755, true);
        }
        $manager = new \Intervention\Image\ImageManager(\Intervention\Image\Drivers\Gd\Driver::class);
        $timestamp = time();
        $updates = [];
        $slotsLlenados = [];
        $hashesVistos = [];

        $procesarSlot = function (int $slot, array &$poolUrls) use (
            $producto, $manager, $destinationPath, $timestamp, &$updates, &$slotsLlenados, &$hashesVistos
        ): bool {
            while (!empty($poolUrls)) {
                $url = array_shift($poolUrls);
                try {
                    $resp = \Illuminate\Support\Facades\Http::withoutVerifying()
                        ->connectTimeout(4)
                        ->timeout(8)
                        ->get($url);

                    if (!$resp->successful()) {
                        continue;
                    }
                    $body = $resp->body();
                    unset($resp);

                    if (strlen($body) < 1500) {
                        unset($body);
                        continue;
                    }

                    $hash = md5($body);
                    if (isset($hashesVistos[$hash])) {
                        unset($body);
                        continue;
                    }

                    $fileName = 'IMGPRO' . $producto->idProducto . '_' . $slot . '.webp';
                    $fullDest = $destinationPath . '/' . $fileName;

                    $image = $manager->read($body);
                    unset($body);
                    $encoded = (string) $image->toWebp(80);
                    unset($image);

                    if (@file_put_contents($fullDest, $encoded) === false) {
                        unset($encoded);
                        continue;
                    }
                    unset($encoded);

                    foreach ([public_path('storage/productos'), storage_path('app/public/productos')] as $extra) {
                        if (is_dir($extra) && realpath($destinationPath) !== realpath($extra)) {
                            @copy($fullDest, $extra . '/' . $fileName);
                        }
                    }

                    $hashesVistos[$hash] = true;
                    $updates['imagenProducto' . $slot] = 'productos/' . $fileName . '?v=' . $timestamp;
                    $slotsLlenados[] = $slot;
                    return true;
                } catch (\Throwable $e) {
                    continue;
                }
            }
            return false;
        };

        // Si por alguna razón la Foto 1 estaba vacía, llenarla primero con la portada
        if ($faltaFoto1 && $foto1Candidata) {
            $pool1 = [$foto1Candidata];
            $procesarSlot(1, $pool1);
        }

        // Llenar los slots secundarios (2, 3 y 4) empezando desde la imagen _1 de Deltron
        foreach ($slotsSecundarios as $slot) {
            if (empty($urlsSecundarias)) {
                break;
            }
            $procesarSlot($slot, $urlsSecundarias);
        }

        unset($manager);
        gc_collect_cycles();

        if (empty($updates)) {
            return [
                'success' => false,
                'message' => "Deltron no tiene fotos adicionales (2, 3, 4) disponibles para el SKU {$skuClean}.",
                'agregadas' => 0,
                'slots' => [],
                'sku' => $skuClean,
            ];
        }

        foreach ($updates as $campo => $valor) {
            $producto->{$campo} = $valor;
        }
        $producto->save();
        if (method_exists($producto, 'invalidarCacheWeb')) {
            $producto->invalidarCacheWeb();
        }

        $n = count($slotsLlenados);
        return [
            'success' => true,
            'message' => "Se completaron {$n} foto(s) en los espacios: " . implode(', ', $slotsLlenados) . '.',
            'agregadas' => $n,
            'slots' => $slotsLlenados,
            'sku' => $skuClean,
        ];
    }
}
