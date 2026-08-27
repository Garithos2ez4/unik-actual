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
        $specificKeyword = $this->option('keyword');

        // Palabras clave por defecto (las requeridas por el usuario)
        $keywords = $specificKeyword ? [$specificKeyword] : ['LAPTOP', 'MONITOR', 'IMPRESORA', 'TECLADO'];

        $this->info("Iniciando scraping de Deltron...");

        foreach ($keywords as $keyword) {
            $this->info("Buscando: $keyword ...");
            
            $scrapedData = $scraperService->scrapeByKeyword($keyword);
            
            $this->info("Encontrados " . count($scrapedData) . " productos para '$keyword'. Sincronizando...");

            $syncResult = $scraperService->syncInventario($scrapedData);
            $updatedCount = $syncResult['count'];
            $detalles = $syncResult['detalles'];
            $recomendados = $syncResult['recomendados'] ?? [];

            \App\Models\Inventario\InventarioProveedorDetalle::create([
                'keyword' => $keyword,
                'productos_encontrados' => count($scrapedData),
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
