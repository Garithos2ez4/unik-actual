<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Agencia;
use App\Models\Destino;
use App\Models\SubAgencia;

class SyncEmtrafesaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:emtrafesa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza las sucursales desde la API de Emtrafesa hacia la tabla sub_agencias';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización con Emtrafesa...');

        // 1. Obtener la Agencia "EMTRAFESA"
        $agencia = Agencia::where('nombre', 'EMTRAFESA')->first();
        if (!$agencia) {
            $this->error('No se encontró la agencia "EMTRAFESA" en la base de datos.');
            return;
        }

        // 2. Consumir la API
        try {
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->get('https://emtrafesa.pe/Home/GetSucursales');
            
            if (!$response->successful()) {
                $this->error('No se pudo conectar con la API de Emtrafesa.');
                return;
            }

            $json = $response->json();
            if (!is_array($json)) {
                $this->error('El formato de la respuesta de la API no es el esperado.');
                return;
            }

            $sucursales = $json;
            $nuevas = 0;
            $existentes_actualizadas = 0;
            $omitidas = 0;

            foreach ($sucursales as $item) {
                $sucursalName = trim($item['Nombre'] ?? '');
                $direccionCompleta = trim($item['Direccion'] ?? '');

                if (empty($sucursalName)) {
                    continue;
                }

                // Extraer dirección y ubicación (Dep-Prov-Dist)
                // Formato esperado: "DIRECCION - DEPARTAMENTO-PROVINCIA-DISTRITO"
                $parts = explode(' - ', $direccionCompleta);
                $direccion = 'S/N';
                $ubicacionStr = '';

                if (count($parts) >= 2) {
                    // El último elemento suele ser el Dep-Prov-Dist
                    $ubicacionStr = array_pop($parts);
                    // Lo que queda es la dirección (volvemos a unir si había " - " en la calle)
                    $direccion = implode(' - ', $parts);
                } else {
                    $direccion = $direccionCompleta;
                }

                $provinciaStr = '';
                $distritoStr = '';
                
                // Parsear ubicacionStr (ej: "LA LIBERTAD-TRUJILLO-TRUJILLO")
                if (!empty($ubicacionStr)) {
                    $ubiParts = explode('-', $ubicacionStr);
                    if (count($ubiParts) >= 3) {
                        $provinciaStr = trim($ubiParts[1]);
                        $distritoStr = trim($ubiParts[2]);
                    } elseif (count($ubiParts) == 2) {
                        // Por si acaso viene solo Prov-Dist
                        $provinciaStr = trim($ubiParts[0]);
                        $distritoStr = trim($ubiParts[1]);
                    }
                }

                // 3. Buscar el Destino localmente
                $destino = null;

                // Primero intentamos buscar por distrito y provincia si tenemos ambos
                if (!empty($distritoStr) && !empty($provinciaStr)) {
                    $destino = Destino::whereHas('Provincia', function($q) use ($provinciaStr) {
                        $q->where('nombre', $provinciaStr);
                    })->where('nombre', $distritoStr)->first();
                }

                // Si no se encontró (o no había provincia), intentamos solo por el distrito
                if (!$destino && !empty($distritoStr)) {
                    $destino = Destino::where('nombre', $distritoStr)->first();
                }

                // Si aún no se encuentra, intentamos por el nombre de la sucursal (ej: "CHICLAYO")
                if (!$destino) {
                    $destino = Destino::where('nombre', $sucursalName)->first();
                }

                // Si aún no se encuentra, intentamos cruzar Nombre Sucursal con la Provincia extraída
                if (!$destino && !empty($provinciaStr)) {
                    $destino = Destino::whereHas('Provincia', function($q) use ($provinciaStr) {
                        $q->where('nombre', $provinciaStr);
                    })->where('nombre', $sucursalName)->first();
                }

                if (!$destino) {
                    $this->warn("Destino no encontrado para: {$sucursalName} (Ubicación Emtrafesa: {$ubicacionStr})");
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
                        'direccion' => substr($direccion, 0, 255),
                        'telefono' => '', // Emtrafesa no expone teléfonos aquí
                        'estado' => 1
                    ]);
                    $nuevas++;
                    $this->line("Creado: {$sucursalName} en Destino: {$destino->nombre}");
                } else {
                    $subAgenciaExistente->update([
                        'direccion' => substr($direccion, 0, 255)
                    ]);
                    $existentes_actualizadas++;
                }
            }

            $this->info("¡Sincronización Completada!");
            $this->info("- Nuevas Sub-Agencias: {$nuevas}");
            $this->info("- Actualizadas con Dirección: {$existentes_actualizadas}");
            $this->info("- Omitidas (Sin destino): {$omitidas}");

        } catch (\Exception $e) {
            $this->error('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }
}
