<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Agencia;
use App\Models\Destino;
use App\Models\SubAgencia;

class SyncSelvaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:selva';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza las sedes (destinos) de Expreso Selva hacia nuestra base de datos local.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando sincronización con Expreso Selva... ");

        // Buscamos la agencia Expreso Selva (ID 22, o por nombre si es más seguro)
        $agencia = Agencia::find(22);
        if (!$agencia) {
            $agencia = Agencia::where('nombre', 'LIKE', '%SELV%')->first();
            if (!$agencia) {
                $this->error('No se encontró la agencia Expreso Selva en la base de datos.');
                return;
            }
        }

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0'
                ])
                ->get('https://web.expresoselva.com/api/v1/travel/getDepartures');

            if ($response->successful()) {
                $json = $response->json();

                if (isset($json['code']) && $json['code'] == 10 && isset($json['data'])) {
                    $agencias = $json['data'];

                    // Desactivamos temporalmente todas para volver a activarlas si existen
                    SubAgencia::where('idAgencia', $agencia->idAgencia)->update(['estado' => 0]);

                    \Illuminate\Support\Facades\Storage::put('selva_agencias.json', json_encode($agencias, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    $this->line(" Archivo de respaldo: " . storage_path('app/selva_agencias.json'));

                    $nuevas = 0;
                    $actualizadas = 0;
                    $omitidas = 0;

                    foreach ($agencias as $item) {
                        $label = trim($item['label'] ?? '');
                        
                        if (empty($label)) {
                            continue;
                        }

                        // Mapeo de los nombres que arroja el endpoint a nuestros destinos en BD
                        $aliases = [
                            'Ate, Lima' => 'ATE',
                            'Huaycan, ate' => 'ATE', // O HUAYCAN si existe
                            'La Merced' => 'LA MERCED',
                            'Luna Pizarro, Lima' => 'LIMA',
                            'Pichanaki' => 'PICHANAQUI',
                            'San Ramón, Junin' => 'SAN RAMON',
                            'Santa Ana' => 'SANTA ANA',
                            'Satipo' => 'SATIPO',
                            'Yurinaki' => 'YURINAKI'
                        ];

                        $zonaBusqueda = isset($aliases[$label]) ? $aliases[$label] : explode(',', $label)[0];

                        // Buscar destino local
                        $destino = Destino::whereRaw('UPPER(nombre) = ?', [strtoupper(trim($zonaBusqueda))])->first();

                        if (!$destino) {
                            // Intento secundario si Yurinaki es YURINAQUI, o quitar acentos
                            $zonaLimpia = str_replace(['ó'], ['o'], $zonaBusqueda);
                            $destino = Destino::whereRaw('UPPER(nombre) = ?', [strtoupper(trim($zonaLimpia))])->first();
                        }

                        if (!$destino) {
                            $this->warn("  Destino local no encontrado para: {$label} (Busqueda: {$zonaBusqueda})");
                            $omitidas++;
                            continue;
                        }

                        // Verificar si ya existe esta SubAgencia para no duplicar
                        $subAgenciaExistente = SubAgencia::where('idAgencia', $agencia->idAgencia)
                            ->where('idDestino', $destino->idDestino)
                            ->where('nombre_oficina', $label)
                            ->first();

                        // Direcciones y teléfonos estáticos obtenidos de su sitio web
                        $dataExtras = [
                            'Luna Pizarro, Lima' => ['dir' => 'Av. Luna Pizarro 453, La Victoria.', 'tel' => '+51 990 992 887'],
                            'Ate, Lima' => ['dir' => 'Av. Marcos Puente Llanos Mz. A Lt. 1 Urb. Barbadillo, Ate', 'tel' => '+51 965 836 205'],
                            'Huaycan, ate' => ['dir' => 'Carretera Central km 17 Mz. F Lt 6 - Asoc. Vivienda Los Girasoles', 'tel' => '+51 965 836 205'],
                            'La Merced' => ['dir' => 'Av. Carlos A Peschiera Nro. 521', 'tel' => '+51 921 273 762'],
                            'Satipo' => ['dir' => 'Jr. Augusto B. Leguia - Terminal Municipal', 'tel' => '+51 922 520 324'],
                            'Santa Ana' => ['dir' => 'Av. Marginal Mz. N Lt. 6 Urb. Santa Ana - Junín', 'tel' => '+51 922 510 109'],
                            'Pichanaki' => ['dir' => 'Plaza Principal S/N (Frente a la Plaza de Armas)', 'tel' => '+51 978 066 108']
                        ];

                        $dirAnotada = isset($dataExtras[$label]) ? $dataExtras[$label]['dir'] : 'S/N';
                        $telAnotado = isset($dataExtras[$label]) ? $dataExtras[$label]['tel'] : '';

                        if (!$subAgenciaExistente) {
                            SubAgencia::create([
                                'idAgencia' => $agencia->idAgencia,
                                'idDestino' => $destino->idDestino,
                                'nombre_oficina' => $label,
                                'direccion' => $dirAnotada,
                                'telefono' => $telAnotado,
                                'estado' => 1
                            ]);
                            $nuevas++;
                        } else {
                            $subAgenciaExistente->update([
                                'direccion' => $dirAnotada,
                                'telefono' => $telAnotado,
                                'estado' => 1
                            ]);
                            $actualizadas++;
                        }
                    }

                    $this->info("¡Sincronización EXPRESO SELVA Completada!");
                    $this->info("- Nuevas Sub-Agencias: {$nuevas}");
                    $this->info("- Actualizadas (reactivadas): {$actualizadas}");
                    $this->info("- Omitidas (Sin destino local): {$omitidas}");
                } else {
                    $this->error("La respuesta no tiene el formato esperado (code 10).");
                }
            } else {
                $this->error("❌ Error HTTP " . $response->status() . " al intentar conectar con Expreso Selva.");
            }
        } catch (\Exception $e) {
            $this->error("❌ Error crítico: " . $e->getMessage());
        }
    }
}
