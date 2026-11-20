<?php

namespace App\Services\Deltron\Import;

use App\Models\Catalogo\GrupoProducto;
use App\Models\Catalogo\MarcaProducto;
use App\Models\Catalogo\Producto;
use App\Models\Inventario\InventarioProveedorSku;
use App\Services\Deltron\DeltronApiService;
use App\Services\Deltron\DeltronClassifierService;
use App\Services\Deltron\DeltronTextHelper;

class DeltronCandidateService
{
    protected ?DeltronApiService $apiService;
    protected DeltronClassifierService $classifierService;
    protected DeltronImageImportService $imageService;

    public function __construct(
        ?DeltronApiService $apiService = null,
        ?DeltronClassifierService $classifierService = null,
        ?DeltronImageImportService $imageService = null
    ) {
        $this->apiService = $apiService;
        $this->classifierService = $classifierService ?: new DeltronClassifierService();
        $this->imageService = $imageService ?: new DeltronImageImportService($apiService);
    }

    public function setApiService(DeltronApiService $apiService): self
    {
        $this->apiService = $apiService;
        $this->imageService->setApiService($apiService);
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
        $cat = $this->obtenerCatalogoConFallback();
        if (empty($cat)) {
            return [];
        }

        $indices = $this->cargarIndicesExistentes();
        $grupos = GrupoProducto::with('CategoriaProducto')->get()->keyBy('idGrupoProducto');
        $marcas = MarcaProducto::all();

        $candidatos = [];

        foreach ($cat as $item) {
            $sku = strtoupper(trim($item['sku'] ?? ''));
            if (empty($sku)) {
                continue;
            }

            $modeloReal = null;
            if ($this->yaExisteEnUnike($sku, $item, $indices, $modeloReal)) {
                continue;
            }

            $price = (float) ($item['price'] ?? 0);
            $pricePromo = isset($item['price_promo']) && $item['price_promo'] !== null ? (float) $item['price_promo'] : null;
            $hasPromo = ($pricePromo !== null && $pricePromo > 0 && $pricePromo < $price) || !empty($item['promociones']);

            if (!$hasPromo) {
                continue;
            }

            $stockInfo = $this->calcularStockItem($item);

            // Filtro: Oferta con stock masivo en Lima (hay_mas=true, stock Lima >= 20 o stock nacional >= 100)
            if ($stockInfo['total_stock'] >= 100 || $stockInfo['lima_hay_mas'] || $stockInfo['lima_stock'] >= 20) {
                $candidatos[] = $this->formatearCandidato($item, $sku, $modeloReal, $stockInfo, $grupos, $marcas);
            }
        }

        return $candidatos;
    }

    /**
     * Obtiene los productos candidatos del catálogo completo de Deltron que no existen en Unike,
     * paginados de 100 en 100 y optimizados para mínimo uso de memoria RAM.
     */
    public function getCandidatosCatalogoPaginado(array $filtros = [], int $page = 1, int $perPage = 100): array
    {
        @ini_set('memory_limit', '512M');

        $cat = $this->obtenerCatalogoConFallback();
        if (empty($cat)) {
            return [
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => 1,
                'items' => [],
            ];
        }

        $indices = $this->cargarIndicesExistentes();
        $query = strtoupper(trim($filtros['query'] ?? ''));
        $soloConStock = !empty($filtros['solo_con_stock']);
        $filtroMarca = strtoupper(trim($filtros['marca'] ?? ''));

        // Filtrado preliminar liviano
        $candidatosCrudos = [];

        foreach ($cat as $item) {
            $sku = strtoupper(trim($item['sku'] ?? ''));
            if (empty($sku)) {
                continue;
            }

            $modeloReal = null;
            if ($this->yaExisteEnUnike($sku, $item, $indices, $modeloReal)) {
                continue;
            }

            $stockInfo = $this->calcularStockItem($item);
            if ($soloConStock && $stockInfo['total_stock'] <= 0 && !$stockInfo['lima_hay_mas']) {
                continue;
            }

            $itemMarca = strtoupper(trim($item['brand_name'] ?? ''));
            if ($filtroMarca !== '' && $itemMarca !== $filtroMarca) {
                continue;
            }

            // Filtro de búsqueda si se proporciona
            if ($query !== '') {
                $pn = strtoupper(trim($item['part_number'] ?? ''));
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
                'modelo_real' => $modeloReal,
                'stockInfo' => $stockInfo,
            ];
        }

        $total = count($candidatosCrudos);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $sliced = array_slice($candidatosCrudos, $offset, $perPage);

        // Procesar en detalle SOLO los ítems de la página actual
        $grupos = GrupoProducto::with('CategoriaProducto')->get()->keyBy('idGrupoProducto');
        $marcas = MarcaProducto::all();

        $itemsProcesados = [];
        foreach ($sliced as $c) {
            $itemsProcesados[] = $this->formatearCandidato(
                $c['raw'],
                $c['sku'],
                $c['modelo_real'],
                $c['stockInfo'],
                $grupos,
                $marcas
            );
        }

        return [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
            'items' => $itemsProcesados,
        ];
    }

    protected function obtenerCatalogoConFallback(): array
    {
        $cat = $this->getApiService()->getCatalogo();
        if (empty($cat)) {
            $snapshotPath = storage_path('app/deltron_catalogo_snapshot.json');
            if (file_exists($snapshotPath)) {
                $cat = @json_decode(file_get_contents($snapshotPath), true) ?: [];
            }
        }
        return is_array($cat) ? $cat : [];
    }

    /**
     * Carga índices ligeros en memoria de productos/SKUs ya registrados en Unike.
     *
     * @return array{skus:array<string,int>, modelos:array<string,bool>, modelosLimpios:array<string,bool>, partNumbers:array<string,bool>}
     */
    protected function cargarIndicesExistentes(): array
    {
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
            $pnBase = strtoupper(trim(DeltronTextHelper::limpiarSufijoPartNumber($pnUpper)));
            $pnBaseClean = preg_replace('/[^A-Z0-9]/', '', $pnBase);
            if (!empty($pnBaseClean)) {
                $existingPartNumbers[$pnBaseClean] = true;
            }
        }

        return [
            'skus' => $existingSkus,
            'modelos' => $existingModelos,
            'modelosLimpios' => $existingModelosLimpios,
            'partNumbers' => $existingPartNumbers,
        ];
    }

    protected function yaExisteEnUnike(string $sku, array $item, array $indices, ?string &$modeloReal = null): bool
    {
        $pn = strtoupper(trim($item['part_number'] ?? ''));
        $pnClean = preg_replace('/[^A-Z0-9]/', '', $pn);
        $pnBase = strtoupper(trim(DeltronTextHelper::limpiarSufijoPartNumber($pn)));
        $pnBaseClean = preg_replace('/[^A-Z0-9]/', '', $pnBase);

        // Obtener el atributo modelo oficial de Deltron
        $modeloReal = DeltronTextHelper::extraerModeloDesdeItem($item);
        $modClean = $modeloReal ? preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($modeloReal))) : '';

        if (isset($indices['skus'][$sku]) || isset($indices['modelos'][$sku])) {
            return true;
        }
        if (!empty($pnClean) && (isset($indices['partNumbers'][$pnClean]) || isset($indices['modelosLimpios'][$pnClean]) || isset($indices['modelos'][$pn]))) {
            return true;
        }
        if (!empty($pnBaseClean) && (isset($indices['partNumbers'][$pnBaseClean]) || isset($indices['modelosLimpios'][$pnBaseClean]) || isset($indices['modelos'][$pnBase]))) {
            return true;
        }
        if (!empty($modClean) && (isset($indices['modelosLimpios'][$modClean]) || isset($indices['partNumbers'][$modClean]) || isset($indices['modelos'][strtoupper(trim($modeloReal))]))) {
            return true;
        }

        return false;
    }

    /**
     * @return array{total_stock:int, lima_stock:int, lima_hay_mas:bool}
     */
    protected function calcularStockItem(array $item): array
    {
        $totalStock = 0;
        $limaStock = 0;
        $limaHayMas = false;

        foreach ($item['stock']['almacenes'] ?? [] as $alm) {
            $cant = (int) ($alm['cantidad'] ?? 0);
            $totalStock += $cant;
            $almName = strtoupper($alm['almacen_name'] ?? '');
            if (str_contains($almName, 'LIMA')) {
                $limaStock += $cant;
                if (!empty($alm['hay_mas'])) {
                    $limaHayMas = true;
                }
            }
        }

        return [
            'total_stock' => $totalStock,
            'lima_stock' => $limaStock,
            'lima_hay_mas' => $limaHayMas,
        ];
    }

    protected function formatearCandidato(
        array $item,
        string $sku,
        ?string $modeloReal,
        array $stockInfo,
        $grupos,
        $marcas
    ): array {
        $clasif = $this->classifierService->clasificarGrupoSugerido($item, $grupos);
        $idMarca = $this->classifierService->emparejarMarca($item['brand_name'] ?? '', $marcas);
        $marcaObj = $marcas->firstWhere('idMarca', $idMarca);

        // Detección de variantes (color/modelo)
        $variante = DeltronClassifierService::detectarVarianteDeltron($item);
        $baseInfo = DeltronClassifierService::extraerModeloBaseYNombrePadre($item, $variante);
        $basePn = $baseInfo['part_number_base'];
        $familiaKey = $idMarca . '|' . $basePn;

        // Obtener Foto 1 prefiriendo 800x800 del carrusel antes que 320x320 de listados
        $foto1 = $this->imageService->resolverUrlFoto1DesdeItem($item['images'] ?? null, $sku, true);

        $price = (float) ($item['price'] ?? 0);
        $pricePromo = isset($item['price_promo']) && $item['price_promo'] !== null ? (float) $item['price_promo'] : null;

        return [
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
            'total_stock' => $stockInfo['total_stock'],
            'lima_stock' => $stockInfo['lima_stock'],
            'lima_hay_mas' => $stockInfo['lima_hay_mas'],
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
