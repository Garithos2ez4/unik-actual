<?php

namespace App\Services\Deltron\Import;

use App\Models\Catalogo\GrupoProducto;
use App\Models\Catalogo\MarcaProducto;
use App\Models\Catalogo\Producto;
use App\Models\Inventario\InventarioProveedorSku;
use App\Services\Deltron\DeltronApiService;
use App\Services\Deltron\DeltronClassifierService;
use App\Services\Deltron\DeltronSpecMapperService;
use App\Services\Deltron\DeltronTextHelper;
use App\Services\ProductoServiceInterface;
use Illuminate\Support\Facades\Log;

class DeltronProductReplacerService
{
    protected ?DeltronApiService $apiService;
    protected DeltronClassifierService $classifierService;
    protected DeltronBatchImportService $batchImportService;
    protected DeltronImageImportService $imageService;

    public function __construct(
        ?DeltronApiService $apiService = null,
        ?DeltronClassifierService $classifierService = null,
        ?DeltronBatchImportService $batchImportService = null,
        ?DeltronImageImportService $imageService = null
    ) {
        $this->apiService = $apiService;
        $this->classifierService = $classifierService ?: new DeltronClassifierService();
        $this->imageService = $imageService ?: new DeltronImageImportService($apiService);
        $this->batchImportService = $batchImportService ?: new DeltronBatchImportService(
            $apiService,
            $this->classifierService,
            $this->imageService
        );
    }

    public function setApiService(DeltronApiService $apiService): self
    {
        $this->apiService = $apiService;
        $this->batchImportService->setApiService($apiService);
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
     * Reemplaza un producto fantasma (sin ingresos en IngresoProducto) con un SKU de Deltron,
     * conservando el idProducto original y completando fotos y especificaciones.
     */
    public function reemplazarProductoConSkuDeltron(Producto $producto, string $sku, ?int $idGrupoOverride = null): array
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(180);

        $errorReemplazable = $this->validarProductoReemplazable($producto);
        if ($errorReemplazable !== null) {
            return [
                'success' => false,
                'message' => $errorReemplazable,
            ];
        }

        $skuClean = strtoupper(trim($sku));
        if (empty($skuClean)) {
            return [
                'success' => false,
                'message' => 'Debes ingresar un SKU o Part Number válido de Deltron.',
            ];
        }

        // Si el usuario ingresó un Part Number (ej. DJ2Z8AT#ABM) o Mini Código, resolver el SKU oficial de Deltron
        $itemCatalog = $this->getApiService()->buscarItemEnCatalogoSnapshot($skuClean);
        $skuReal = $itemCatalog ? strtoupper(trim($itemCatalog['sku'] ?? $skuClean)) : $skuClean;

        $errorSkuEnUso = $this->validarSkuDisponible($skuReal, $skuClean, $producto);
        if ($errorSkuEnUso !== null) {
            return [
                'success' => false,
                'message' => $errorSkuEnUso,
            ];
        }

        $ficha = $this->getApiService()->getOrFetchFichaSku($skuReal);
        if (!$ficha && $itemCatalog) {
            $ficha = $this->getApiService()->construirFichaDesdeItemCatalogo($itemCatalog);
        }

        if (!$ficha) {
            return [
                'success' => false,
                'message' => "No se encontró información en Deltron para el SKU/PN: {$skuClean}.",
            ];
        }

        $skuReal = strtoupper(trim($ficha['sku'] ?? $skuClean));
        $itemData = $this->construirItemDataReemplazo($producto, $ficha, $skuReal, $idGrupoOverride);

        $loteRes = $this->batchImportService->importarLoteOfertas([$itemData], false);
        if (empty($loteRes['importados'])) {
            $errMsg = $loteRes['errores'][0]['error'] ?? 'No se pudo completar el reemplazo del producto.';
            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }

        $producto->refresh();

        // Completar fotos secundarias (2, 3 y 4) y especificaciones desde la ficha
        try {
            $this->imageService->completarFotosProducto($producto);
        } catch (\Throwable $e) {
            Log::warning("Aviso completando fotos tras reemplazo ID {$producto->idProducto}: " . $e->getMessage());
        }

        try {
            $specMapper = new DeltronSpecMapperService($this->getApiService(), $this->classifierService);
            $specMapper->llenarEspecificacionesProducto($producto, $skuReal, false);
        } catch (\Throwable $e) {
            Log::warning("Aviso llenando specs tras reemplazo ID {$producto->idProducto}: " . $e->getMessage());
        }

        $impInfo = $loteRes['importados'][0];

        return [
            'success' => true,
            'message' => "Producto ID #{$producto->idProducto} reemplazado exitosamente por {$impInfo['nombreProducto']} ({$impInfo['codigoProducto']}).",
            'producto' => $impInfo,
        ];
    }

    protected function validarProductoReemplazable(Producto $producto): ?string
    {
        $prodService = app(ProductoServiceInterface::class);
        if ($prodService->esReemplazable($producto)) {
            return null;
        }

        $ultimaVenta = method_exists($prodService, 'getFechaUltimaVenta') ? $prodService->getFechaUltimaVenta((int) $producto->idProducto) : null;
        $mesesGarantia = method_exists($prodService, 'obtenerMesesGarantiaProducto') ? $prodService->obtenerMesesGarantiaProducto($producto, 12) : 12;

        if ($ultimaVenta && $ultimaVenta->copy()->addMonths($mesesGarantia)->isFuture()) {
            $expira = $ultimaVenta->copy()->addMonths($mesesGarantia)->format('d/m/Y');
            return "El producto {$producto->codigoProducto} (ID #{$producto->idProducto}) registra una venta del {$ultimaVenta->format('d/m/Y')} con garantía vigente hasta el {$expira} y no puede ser reemplazado.";
        }

        return "El producto {$producto->codigoProducto} (ID #{$producto->idProducto}) no cumple los requisitos para ser reemplazado (cuenta con stock en almacén o garantía vigente).";
    }

    protected function validarSkuDisponible(string $skuReal, string $skuClean, Producto $producto): ?string
    {
        $skuEnUso = InventarioProveedorSku::where(function ($q) use ($skuReal, $skuClean) {
            $q->where('sku_proveedor', $skuReal)
                ->orWhere('sku_proveedor', $skuClean)
                ->orWhere('part_number', $skuClean);
        })
            ->whereHas('inventarioProveedor', function ($q) use ($producto) {
                $q->where('idProducto', '!=', $producto->idProducto);
            })
            ->first();

        if (!$skuEnUso) {
            return null;
        }

        $prodDuplicado = optional($skuEnUso->inventarioProveedor)->Producto;
        $codDup = $prodDuplicado ? $prodDuplicado->codigoProducto : ('ID #' . optional($skuEnUso->inventarioProveedor)->idProducto);

        return "El SKU/PN {$skuClean} ya se encuentra vinculado al producto {$codDup}.";
    }

    protected function construirItemDataReemplazo(Producto $producto, array $ficha, string $skuReal, ?int $idGrupoOverride): array
    {
        $pn = trim($ficha['part_number'] ?? $skuReal);
        $grupos = GrupoProducto::with('CategoriaProducto')->get()->keyBy('idGrupoProducto');
        $marcas = MarcaProducto::all();

        if ($idGrupoOverride && $grupos->has($idGrupoOverride)) {
            $grupoObj = $grupos->get($idGrupoOverride);
            $idGrupo = (int) $idGrupoOverride;
            $nombreGrupo = $grupoObj->nombreGrupo;
        } else {
            $clasif = $this->classifierService->clasificarGrupoSugerido($ficha, $grupos);
            $idGrupo = !empty($clasif['idGrupo']) ? (int) $clasif['idGrupo'] : null;
            $nombreGrupo = $clasif['nombreGrupo'];
        }

        $idMarca = $this->classifierService->emparejarMarca($ficha['brand_name'] ?? '', $marcas);

        // Preferir portada 800x800 del carrusel antes que 320x320 de listado
        $foto1 = $this->imageService->resolverUrlFoto1DesdeItem($ficha['images'] ?? null, $skuReal, false);

        return [
            'replace_id' => $producto->idProducto,
            'sku' => $skuReal,
            'part_number' => $pn,
            'modelo_real' => DeltronTextHelper::formatearModeloSiLaptop(
                $ficha['modelo_real'] ?? DeltronTextHelper::extraerModeloDesdeItem($ficha, $idGrupo),
                $ficha,
                $idGrupo
            ),
            'name' => $ficha['name'] ?? DeltronTextHelper::obtenerTituloComercialDeltron($ficha),
            'brand_name' => $ficha['brand_name'] ?? '',
            'idMarca' => $idMarca,
            'idGrupo' => $idGrupo,
            'nombreGrupo' => $nombreGrupo,
            'price' => (float) ($ficha['price'] ?? 0),
            'price_promo' => !empty($ficha['price_promo']) ? (float) $ficha['price_promo'] : null,
            'total_stock' => (int) ($ficha['stock_total'] ?? 0),
            'lima_stock' => (int) ($ficha['stock_total'] ?? 0),
            'lima_hay_mas' => !empty($ficha['hay_mas']),
            'foto1_url' => $foto1,
            'mini_code' => $ficha['mini_code'] ?? ($ficha['mini_codigo'] ?? null),
            'warranty' => DeltronTextHelper::normalizarGarantiaDeltron($ficha['warranty'] ?? null),
            'description' => $ficha['descripcion_completa'] ?? DeltronTextHelper::construirDescripcionProducto($ficha),
            'es_variante' => false,
        ];
    }
}
