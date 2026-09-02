<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DeltronScraperService;

class ScrapeDeltron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:deltron {--keyword= : Palabra clave específica a scrapear}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extrae el stock de productos desde Deltron y actualiza el inventario proveedor';

    /**
     * Execute the console command.
     */
    public function handle(DeltronScraperService $scraperService)
    {
        set_time_limit(0); // Evitar timeout al descargar múltiples categorías

        $specificKeyword = $this->option('keyword');

        if ($specificKeyword) {
            $keywords = [$specificKeyword];
        } else {
            // Tomar las categorías solicitadas
            $categoriasIds = [1, 2, 3, 4, 6, 7, 8, 9, 10, 14, 16];
            $keywords = \App\Models\Catalogo\CategoriaProducto::whereIn('idCategoria', $categoriasIds)
                ->orderBy('idCategoria', 'desc')
                ->pluck('nombreCategoria')
                ->toArray();
        }

        $this->info("Iniciando scraping de Deltron...");
        $processedPartNumbers = []; // Registro de números de parte ya procesados en esta ejecución

        foreach ($keywords as $keyword) {
            $this->info("Buscando: $keyword ...");
            
            $scrapedData = $scraperService->scrapeByKeyword($keyword);
            
            // Filtrar productos repetidos que ya aparecieron en categorías anteriores
            $filteredData = [];
            foreach ($scrapedData as $data) {
                if (!empty($data['part_number']) && !in_array($data['part_number'], $processedPartNumbers)) {
                    $filteredData[] = $data;
                    $processedPartNumbers[] = $data['part_number'];
                }
            }
            
            $this->info("Encontrados " . count($filteredData) . " productos nuevos (no repetidos) para '$keyword'. Sincronizando...");

            $syncResult = $scraperService->syncInventario($filteredData);
            $updatedCount = $syncResult['count'];
            $detalles = $syncResult['detalles'];
            $recomendados = $syncResult['recomendados'] ?? [];

            \App\Models\Inventario\InventarioProveedorDetalle::create([
                'keyword' => $keyword,
                'productos_encontrados' => count($filteredData),
                'productos_actualizados' => $updatedCount,
                'detalles_json' => $detalles,
                'recomendados_json' => $recomendados
            ]);

            $this->info("Sincronización completada para '$keyword': $updatedCount productos actualizados, " . count($recomendados) . " recomendados.");
            $this->line("--------------------------------------------------");
            
            // Pausa breve para no saturar el servidor de Deltron
            sleep(2);
        }

        $this->info("Scraping finalizado exitosamente.");
    }
}
