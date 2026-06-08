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
                        $nombreSucursal = trim($tienda['nombres'] ?? '');
                        $direccion = trim($tienda['direccion'] ?? 'S/N');
                        $ubigeo = trim($tienda['ubigeo'] ?? '');

                        if (empty($nombreSucursal) || empty($ubigeo)) {
                            continue;
                        }

                        // 3. Buscar el Destino por nombre (el nombre de la sucursal suele ser el nombre del distrito)
                        $destino = Destino::whereRaw('UPPER(nombre) = ?', [strtoupper($nombreSucursal)])->first();

                        if (!$destino) {
                            $this->warn("  Destino no encontrado para: {$nombreSucursal} (ubigeo: {$ubigeo})");
                            $omitidas++;
                            continue;
                        }

                        // 4. Crear o actualizar SubAgencia
                        $subAgenciaExistente = SubAgencia::where('idAgencia', $agencia->idAgencia)
                            ->where('idDestino', $destino->idDestino)
                            ->where('nombre_oficina', $nombreSucursal)
                            ->first();

                        if (!$subAgenciaExistente) {
                            SubAgencia::create([
                                'idAgencia' => $agencia->idAgencia,
                                'idDestino' => $destino->idDestino,
                                'nombre_oficina' => $nombreSucursal,
                                'direccion' => substr($direccion, 0, 255),
                                'telefono' => '',
                                'estado' => 1
                            ]);
                            $nuevas++;
                        } else {
                            $subAgenciaExistente->update([
                                'direccion' => substr($direccion, 0, 255),
                            ]);
                            $actualizadas++;
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error("\nError al extraer departamento {$i}: " . $e->getMessage());
            }

            $bar->advance();
            // Pausa de 1 segundo para evitar baneo
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
