<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Envios\Agencia;
use App\Models\Envios\Destino;
use App\Models\Envios\SubAgencia;

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

            // Guardar respaldo JSON local por seguridad (anti-Cloudflare)
            file_put_contents(storage_path('app/emtrafesa_agencias.json'), json_encode($json, JSON_PRETTY_PRINT));

            $sucursales = $json;

            $idsVistos = [];
            $nuevas = 0;
            $actualizadas = 0;
            $sinCambios = 0;
            $omitidas = 0;

            // Aliases explícitos para sucursales que no traen formato estándar
            $sucursalAliases = [
                'LIMA PUENTE PIEDRA' => ['distrito' => 'PUENTE PIEDRA', 'provincia' => 'LIMA'],
                'JAEN TERMINAL' => ['distrito' => 'JAEN', 'provincia' => 'JAEN'],
            ];

            foreach ($sucursales as $item) {
                $sucursalName = trim($item['Nombre'] ?? '');
                $direccionCompleta = trim($item['Direccion'] ?? '');

                if (empty($sucursalName)) {
                    continue;
                }

                $provinciaStr = '';
                $distritoStr = '';
                $direccion = $direccionCompleta;

                // 1. Verificar si tiene alias explícito
                if (isset($sucursalAliases[strtoupper($sucursalName)])) {
                    $distritoStr = $sucursalAliases[strtoupper($sucursalName)]['distrito'];
                    $provinciaStr = $sucursalAliases[strtoupper($sucursalName)]['provincia'];
                } else {
                    // Extraer dirección y ubicación (Dep-Prov-Dist)
                    // Formato esperado: "DIRECCION - DEPARTAMENTO-PROVINCIA-DISTRITO"
                    $parts = explode(' - ', $direccionCompleta);

                    if (count($parts) >= 2) {
                        $ubicacionStr = array_pop($parts);
                        $direccion = implode(' - ', $parts);

                        $ubiParts = explode('-', $ubicacionStr);
                        if (count($ubiParts) >= 3) {
                            $provinciaStr = trim($ubiParts[1]);
                            $distritoStr = trim($ubiParts[2]);
                        } elseif (count($ubiParts) == 2) {
                            $provinciaStr = trim($ubiParts[0]);
                            $distritoStr = trim($ubiParts[1]);
                        }
                    }

                    // Si no se extrajo distrito y el nombre empieza con "LIMA "
                    if (empty($distritoStr) && str_starts_with(strtoupper($sucursalName), 'LIMA ')) {
                        $distritoStr = trim(substr($sucursalName, 5));
                        $provinciaStr = 'LIMA';
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

                // Si aún no se encuentra, intentamos por el nombre de la sucursal (ej: "HUARMEY", "CHICLAYO")
                if (!$destino) {
                    $nombreLimpio = preg_replace('/\s*\(.*?\)\s*/', '', $sucursalName);
                    $destino = Destino::where('nombre', $nombreLimpio)->first();
                }

                // Si aún no se encuentra, intentamos cruzar Nombre Sucursal con la Provincia extraída
                if (!$destino && !empty($provinciaStr)) {
                    $destino = Destino::whereHas('Provincia', function($q) use ($provinciaStr) {
                        $q->where('nombre', $provinciaStr);
                    })->where('nombre', $sucursalName)->first();
                }

                if (!$destino) {
                    $this->warn("Destino no encontrado para: {$sucursalName} (Ubicación Emtrafesa: {$direccionCompleta})");
                    $omitidas++;
                    continue;
                }

                // 4. Crear o actualizar SubAgencia
                $subAgencia = SubAgencia::where('idAgencia', $agencia->idAgencia)
                    ->where('idDestino', $destino->idDestino)
                    ->where('nombre_oficina', $sucursalName)
                    ->first();

                if (!$subAgencia) {
                    $subAgencia = SubAgencia::where('idAgencia', $agencia->idAgencia)
                        ->where('nombre_oficina', $sucursalName)
                        ->whereNotIn('idSubAgencia', $idsVistos ?: [0])
                        ->first();
                }

                if (!$subAgencia) {
                    $subAgencia = SubAgencia::create([
                        'idAgencia' => $agencia->idAgencia,
                        'idDestino' => $destino->idDestino,
                        'nombre_oficina' => $sucursalName,
                        'direccion' => substr($direccion, 0, 255),
                        'telefono' => '', // Emtrafesa no expone teléfonos aquí
                        'estado' => 1
                    ]);
                    $idsVistos[] = $subAgencia->idSubAgencia;
                    $nuevas++;
                    $this->line("Creado: {$sucursalName} en Destino: {$destino->nombre}");
                } else {
                    $subAgencia->fill([
                        'idDestino' => $destino->idDestino,
                        'direccion' => substr($direccion, 0, 255),
                        'estado' => 1
                    ]);
                    $idsVistos[] = $subAgencia->idSubAgencia;

                    if ($subAgencia->isDirty()) {
                        $subAgencia->save();
                        $actualizadas++;
                    } else {
                        $sinCambios++;
                    }
                }
            }

            // Desactivar solo las sub-agencias que ya no existen en Emtrafesa
            $desactivadas = SubAgencia::where('idAgencia', $agencia->idAgencia)
                ->where('estado', 1)
                ->whereNotIn('idSubAgencia', $idsVistos ?: [0])
                ->update(['estado' => 0]);

            $this->info("¡Sincronización Completada!");
            $this->info("- Nuevas Sub-Agencias: {$nuevas}");
            $this->info("- Actualizadas con cambios: {$actualizadas}");
            $this->info("- Sin cambios: {$sinCambios}");
            $this->info("- Desactivadas: {$desactivadas}");
            $this->info("- Omitidas (Sin destino): {$omitidas}");

        } catch (\Exception $e) {
            $this->error('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }
}
