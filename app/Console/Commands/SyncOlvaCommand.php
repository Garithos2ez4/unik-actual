<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Agencia;
use App\Models\Destino;
use App\Models\SubAgencia;

class SyncOlvaCommand extends Command
{
    protected $signature = 'sync:olva';
    protected $description = 'Sincroniza las sucursales/tiendas de Olva Courier desde su API pública hacia nuestra base de datos local.';

    public function handle()
    {
        $this->info('Iniciando sincronización con OLVA COURIER...');

        // 1. Obtener la agencia OLVA local
        $agencia = Agencia::where('nombre', 'LIKE', '%OLVA%')->first();
        if (!$agencia) {
            $this->error('No se encontró la agencia OLVA en la base de datos.');
            return;
        }

        $nuevas = 0;
        $actualizadas = 0;
        $omitidas = 0;

        $bar = $this->output->createProgressBar(25);
        $bar->start();

        // --- BORRADO LÓGICO ---
        SubAgencia::where('idAgencia', $agencia->idAgencia)->update(['estado' => 0]);

        // 2. Iterar por los 25 departamentos
        for ($i = 1; $i <= 25; $i++) {
            try {
                $response = Http::withoutVerifying()
                    ->withHeaders([
                        'X-Requested-With' => 'XMLHttpRequest',
                        'Referer' => 'https://www.olvacourier.com/ubicanos',
                    ])
                    ->asForm()
                    ->post('https://www.olvacourier.com/tiendas', [
                        'ubigeo' => $i,
                        'tipo' => 'tiendas',
                        'lugar' => 'departamento'
                    ]);

                if ($response->successful() && !empty($response->json())) {
                    $tiendas = $response->json();

                    foreach ($tiendas as $tienda) {
                        $nombreSucursalOriginal = trim($tienda['nombres'] ?? '');
                        $direccion = trim($tienda['direccion'] ?? 'S/N');
                        $ubigeo = trim($tienda['ubigeo'] ?? '');

                        if (empty($nombreSucursalOriginal) || empty($ubigeo)) {
                            continue;
                        }


                        $nombreLimpio = mb_strtoupper($nombreSucursalOriginal, 'UTF-8');

                        $nombreLimpio = str_replace(['–', '—'], '-', $nombreLimpio);

                        $nombreLimpio = explode('-', $nombreLimpio)[0];
                        $nombreLimpio = trim($nombreLimpio);

                        $excepciones = [
                            'LIMA CERCADO' => 'LIMA',
                            'BAGUA CHICA' => 'BAGUA',
                            'ZÁRATE' => 'SAN JUAN DE LURIGANCHO',
                            'HIGUERETA' => 'SURCO',
                            'MUSA' => 'LA MOLINA',
                            'PLAZA TEC' => 'LIMA',
                            'CAÑETE' => 'SAN VICENTE DE CAÑETE',
                            // Mapeo de ciudades de la selva a sus distritos reales
                            'PUCALLPA' => 'CALLERIA',
                            'AGUAYTIA' => 'PADRE ABAD',
                            'ATALAYA' => 'RAYMONDI',
                            'NARANJOS' => 'PARDO MIGUEL',
                            'ROQUE' => 'ALONSO DE ALVARADO',
                            // Nuevos mapeos
                            'CHIRIACO' => 'IMAZA',
                            'RODRIGUEZ DE MENDOZA' => 'SAN NICOLAS',
                            'URIPA' => 'ANCO-HUALLO',
                            'CALLE MORAL' => 'AREQUIPA',
                            'EL PEDREGAL' => 'MAJES',
                            'GUARDIA CIVIL' => 'JOSE LUIS BUSTAMANTE Y RIVERO',
                            'IV CENTENARIO' => 'AREQUIPA',
                            'PARRA' => 'AREQUIPA',
                            'VILCASHUAMAN' => 'VILCAS HUAMAN',
                            'CAJAMARCA 2' => 'CAJAMARCA',
                            'PUCAR?' => 'JAEN',
                            'PUCARÁ' => 'JAEN',
                            'PUCARA' => 'JAEN',
                            'SAN MIGUEL (CAJ)' => 'TONGOD',
                            'TEMBLADERA' => 'YONAN',
                            'ALMACEN CENTRAL CALLAO' => 'CALLAO',
                            'CANTA CALLAO' => 'CALLAO',
                            'QUILLABAMBA' => 'SANTA ANA',
                            'AUCAYACU' => 'JOSE CRESPO Y CASTILLO',
                            'TINGO MARIA' => 'RUPA-RUPA',
                            'CHINCHA' => 'CHINCHA ALTA',
                            'PERENE (SANTA ANA)' => 'PERENE',
                            'PERENE (URB. ESPERANZA)' => 'PERENE',
                            'CA?ETE' => 'SAN VICENTE DE CAÑETE',
                            'CHOSICA' => 'LURIGANCHO',
                            'CABALLOCOCHA' => 'RAMON CASTILLA',
                            'SANCHEZ CERRO' => 'PIURA',
                            'TALARA' => 'PARIÑAS',
                            'TAMBOGRANDE' => 'TAMBO GRANDE',
                            'OLVA JULIACA' => 'JULIACA',
                            'PONGO DE CAYNARACHI' => 'CAYNARACHI',
                            'PIURA - AYABACA' => 'AYABACA',
                            'PIURA - LOS FICUS' => 'PIURA',
                            'PIURA - LOS GERANIOS' => 'PIURA',
                            'PIURA - SANTA ISABEL' => 'PIURA',
                            'INAMBARI - MAZUCO' => 'INAMBARI',
                        ];

                        if (array_key_exists($nombreLimpio, $excepciones)) {
                            $nombreLimpio = $excepciones[$nombreLimpio];
                        }

                        // Diferenciación por ubigeo para sucursales con el mismo nombre en distintos distritos
                        if ($nombreLimpio === 'CERRO DE PASCO') {
                            if ($ubigeo === '190113') {
                                $nombreLimpio = 'YANACANCHA';
                            } else {
                                $nombreLimpio = 'CHAUPIMARCA';
                            }
                        }


                        $destino = Destino::whereRaw('UPPER(nombre) = ?', [$nombreLimpio])->first();

                        if (!$destino) {
                            $this->warn("\nDestino no encontrado para: {$nombreSucursalOriginal} (Buscado como: {$nombreLimpio})");
                            $omitidas++;
                            continue;
                        }

                        // 4. Crear o actualizar SubAgencia
                        $subAgenciaExistente = SubAgencia::where('idAgencia', $agencia->idAgencia)
                            ->where('idDestino', $destino->idDestino)
                            ->where('nombre_oficina', $nombreSucursalOriginal) // Guardamos el nombre real de Olva
                            ->first();

                        if (!$subAgenciaExistente) {
                            SubAgencia::create([
                                'idAgencia' => $agencia->idAgencia,
                                'idDestino' => $destino->idDestino,
                                'nombre_oficina' => $nombreSucursalOriginal, // Guardamos el nombre real de Olva
                                'direccion' => substr($direccion, 0, 255),
                                'telefono' => '',
                                'estado' => 1
                            ]);
                            $nuevas++;
                        } else {
                            $subAgenciaExistente->update([
                                'direccion' => substr($direccion, 0, 255),
                                'estado' => 1
                            ]);
                            $actualizadas++;
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error("\nError al extraer departamento {$i}: " . $e->getMessage());
            }

            $bar->advance();
            sleep(1);
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("¡Sincronización OLVA Completada!");
        $this->info("- Nuevas Sub-Agencias: {$nuevas}");
        $this->info("- Actualizadas con Dirección: {$actualizadas}");
        $this->info("- Omitidas (Sin destino local): {$omitidas}");
    }
}
