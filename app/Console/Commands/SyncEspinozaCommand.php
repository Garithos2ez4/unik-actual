<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agencia;
use App\Models\Destino;
use App\Models\SubAgencia;
use Illuminate\Support\Facades\Storage;

class SyncEspinozaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:espinoza';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza las sucursales de Espinoza desde un archivo JSON local.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización con Espinoza...');

        // 1. Obtener la agencia Espinoza local
        $agencia = Agencia::where('nombre', 'LIKE', '%ESPINOZA%')->first();
        if (!$agencia) {
            $this->error('No se encontró la agencia ESPINOZA en la base de datos.');
            return;
        }

        // 2. Leer el archivo JSON
        $filePath = 'espinoza_agencias.json';
        if (!Storage::exists($filePath)) {
            $this->error('No se encontró el archivo JSON de Espinoza: storage/app/' . $filePath);
            return;
        }

        try {
            $jsonContent = Storage::get($filePath);
            $sucursales = json_decode($jsonContent, true);

            if (!is_array($sucursales)) {
                $this->error('El formato del JSON de Espinoza no es válido.');
                return;
            }

            // --- BORRADO LÓGICO ---
            SubAgencia::where('idAgencia', $agencia->idAgencia)->update(['estado' => 0]);

            $nuevas = 0;
            $existentes_actualizadas = 0;
            $omitidas = 0;

            foreach ($sucursales as $item) {
                $sucursalName = trim($item['nombre'] ?? '');
                $direccion = trim($item['direccion'] ?? 'S/N');
                $referencia = trim($item['referencia'] ?? '');
                $telefono = trim($item['telefono'] ?? '');
                $distritoBusqueda = trim($item['distrito_busqueda'] ?? '');

                if (empty($sucursalName)) {
                    continue;
                }

                $direccionFinal = $direccion;
                if (!empty($referencia)) {
                    $direccionFinal .= ' (' . $referencia . ')';
                }

                // 3. Identificar el destino
                $destino = Destino::whereRaw('UPPER(nombre) = ?', [mb_strtoupper($distritoBusqueda, 'UTF-8')])->first();

                if (!$destino) {
                    $this->warn("Destino no encontrado para: {$sucursalName} (Buscado como: {$distritoBusqueda})");
                    $omitidas++;
                    continue;
                }

                // 4. Crear o actualizar SubAgencia
                $subAgenciaExistente = SubAgencia::where('idAgencia', $agencia->idAgencia)
                    ->where('idDestino', $destino->idDestino)
                    ->where('nombre_oficina', $sucursalName)
                    ->first();

                if (!$subAgenciaExistente) {
                    SubAgencia::create([
                        'idAgencia' => $agencia->idAgencia,
                        'idDestino' => $destino->idDestino,
                        'nombre_oficina' => $sucursalName,
                        'direccion' => substr($direccionFinal, 0, 255),
                        'telefono' => substr($telefono, 0, 50),
                        'estado' => 1
                    ]);
                    $nuevas++;
                    $this->line("Creado: {$sucursalName} en Destino: {$destino->nombre}");
                } else {
                    $subAgenciaExistente->update([
                        'direccion' => substr($direccionFinal, 0, 255),
                        'telefono'  => substr($telefono, 0, 50),
                        'estado'    => 1
                    ]);
                    $existentes_actualizadas++;
                }
            }

            $this->info("¡Sincronización ESPINOZA Completada!");
            $this->info("- Nuevas Sub-Agencias: {$nuevas}");
            $this->info("- Actualizadas con Dirección: {$existentes_actualizadas}");
            $this->info("- Omitidas (Sin destino): {$omitidas}");

        } catch (\Exception $e) {
            $this->error('Error durante la sincronización de Espinoza: ' . $e->getMessage());
        }
    }
}
