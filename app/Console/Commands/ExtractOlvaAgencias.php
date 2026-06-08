<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ExtractOlvaAgencias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'olva:extract-agencias';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extrae la lista de Agencias (Tiendas) de Olva Courier para todos los departamentos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando extracción de Tiendas/Agencias desde Olva Courier...");

        $allData = [];
        $totalAgencias = 0;

        $bar = $this->output->createProgressBar(25);
        $bar->start();

        // Departamentos del 1 al 25
        for ($i = 1; $i <= 25; $i++) {
            try {
                // Probamos primero sin el cero (como se ve en tu captura)
                $response = Http::withoutVerifying()
                    ->withHeaders([
                        'X-Requested-With' => 'XMLHttpRequest',
                        'Referer' => 'https://www.olvacourier.com/ubicanos',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
                    ])
                    ->asForm()->post('https://www.olvacourier.com/tiendas', [
                        'ubigeo' => $i, // Enviamos el número tal cual (1, 2, 3...)
                        'tipo' => 'tiendas',
                        'lugar' => 'departamento'
                    ]);

                if ($response->successful() && !empty($response->json())) {
                    $data = $response->json();
                    $allData[$i] = $data;
                    $totalAgencias += count($data);
                } else {
                    // Si falla sin el cero, probamos CON el cero (por si acaso)
                    $ubigeoConCero = str_pad($i, 2, '0', STR_PAD_LEFT);
                    $responseFallback = Http::withoutVerifying()
                        ->withHeaders([
                            'X-Requested-With' => 'XMLHttpRequest',
                            'Referer' => 'https://www.olvacourier.com/ubicanos'
                        ])
                        ->asForm()->post('https://www.olvacourier.com/tiendas', [
                            'ubigeo' => $ubigeoConCero,
                            'tipo' => 'tiendas',
                            'lugar' => 'departamento'
                        ]);

                    if ($responseFallback->successful() && !empty($responseFallback->json())) {
                        $data = $responseFallback->json();
                        $allData[$ubigeoConCero] = $data;
                        $totalAgencias += count($data);
                    }
                }
            } catch (\Exception $e) {
                $this->error("\nError al extraer el departamento ID {$i}: " . $e->getMessage());
            }

            $bar->advance();
            // Pausa de 1 segundo para evitar bloqueos
            sleep(1);
        }

        $bar->finish();
        $this->newLine(2);

        if (count($allData) > 0) {
            Storage::put('olva_agencias.json', json_encode($allData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $this->info("✅ Extracción de Tiendas/Agencias completada exitosamente.");
            $this->info("🏢 Se extrajeron un total de {$totalAgencias} agencias a nivel nacional.");
            $this->line("📂 Archivo guardado en: " . storage_path('app/olva_agencias.json'));
        } else {
            $this->warn("⚠️ La respuesta fue vacía para todos los departamentos.");
        }
    }
}
