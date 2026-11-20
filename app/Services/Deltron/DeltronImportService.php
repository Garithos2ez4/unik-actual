<?php

namespace App\Services\Deltron;

use App\Models\Catalogo\Producto;
use App\Services\Deltron\Import\DeltronBatchImportService;
use App\Services\Deltron\Import\DeltronCandidateService;
use App\Services\Deltron\Import\DeltronImageImportService;
use App\Services\Deltron\Import\DeltronProductReplacerService;

/**
 * Fachada orquestadora de importación, candidatos, reemplazo e imágenes de Deltron.
 * manteniendo 100% compatibilidad retroactiva con `DeltronApiService` y `DeltronController`.
 */
class DeltronImportService
{
    protected ?DeltronApiService $apiService;
    protected DeltronClassifierService $classifierService;
    protected DeltronImageImportService $imageService;
    protected DeltronCandidateService $candidateService;
    protected DeltronBatchImportService $batchImportService;
    protected DeltronProductReplacerService $replacerService;

    public function __construct(
        ?DeltronApiService $apiService = null,
        ?DeltronClassifierService $classifierService = null,
        ?DeltronImageImportService $imageService = null,
        ?DeltronCandidateService $candidateService = null,
        ?DeltronBatchImportService $batchImportService = null,
        ?DeltronProductReplacerService $replacerService = null
    ) {
        $this->apiService = $apiService;
        $this->classifierService = $classifierService ?: new DeltronClassifierService();

        $this->imageService = $imageService ?: new DeltronImageImportService($apiService);
        $this->candidateService = $candidateService ?: new DeltronCandidateService(
            $apiService,
            $this->classifierService,
            $this->imageService
        );
        $this->batchImportService = $batchImportService ?: new DeltronBatchImportService(
            $apiService,
            $this->classifierService,
            $this->imageService
        );
        $this->replacerService = $replacerService ?: new DeltronProductReplacerService(
            $apiService,
            $this->classifierService,
            $this->batchImportService,
            $this->imageService
        );
    }

    public function setApiService(DeltronApiService $apiService): self
    {
        $this->apiService = $apiService;
        $this->imageService->setApiService($apiService);
        $this->candidateService->setApiService($apiService);
        $this->batchImportService->setApiService($apiService);
        $this->replacerService->setApiService($apiService);
        return $this;
    }

    public function images(): DeltronImageImportService
    {
        return $this->imageService;
    }

    public function candidates(): DeltronCandidateService
    {
        return $this->candidateService;
    }

    public function batchImporter(): DeltronBatchImportService
    {
        return $this->batchImportService;
    }

    public function replacer(): DeltronProductReplacerService
    {
        return $this->replacerService;
    }

    public function getCandidatosOfertasAltoStock(): array
    {
        return $this->candidateService->getCandidatosOfertasAltoStock();
    }

    public function getCandidatosCatalogoPaginado(array $filtros = [], int $page = 1, int $perPage = 100): array
    {
        return $this->candidateService->getCandidatosCatalogoPaginado($filtros, $page, $perPage);
    }

    public function importarLoteOfertas(array $itemsAImportar, bool $reciclarFantasmas = false): array
    {
        return $this->batchImportService->importarLoteOfertas($itemsAImportar, $reciclarFantasmas);
    }

    public function completarFotosProducto(Producto $producto): array
    {
        return $this->imageService->completarFotosProducto($producto);
    }

    public function reemplazarProductoConSkuDeltron(Producto $producto, string $sku, ?int $idGrupoOverride = null): array
    {
        return $this->replacerService->reemplazarProductoConSkuDeltron($producto, $sku, $idGrupoOverride);
    }
}
