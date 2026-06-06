<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Agencia;
use App\Models\Destino;
use App\Models\SubAgencia;

class SyncMarvisurCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:marvisur';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza las sucursales de Marvisur desde su API pública hacia nuestra base de datos local.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización con Marvisur...');

        // 1. Obtener la agencia Marvisur local
        $agencia = Agencia::where('nombre', 'LIKE', '%MARVISUR%')->first();
        if (!$agencia) {
            $this->error('No se encontró la agencia MARVISUR en la base de datos.');
            return;
        }

        // 2. Consumir la API
        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'accept' => 'application/json, text/plain, */*',
                    'referrer' => 'https://www.expresomarvisur.com/',
                ])
                ->timeout(30)
                ->post('https://marvicom.expresomarvisur.com/backend/api/Sucursales', [
                    'modo' => 20
                ]);
            
            if (!$response->successful()) {
                $this->error('No se pudo conectar con la API de Marvisur.');
                return;
            }

            $json = $response->json();
            if (!isset($json['data']['Table']) || !is_array($json['data']['Table'])) {
                $this->error('El formato de la respuesta de la API no es el esperado.');
                return;
            }

            $sucursales = $json['data']['Table'];
            $nuevas = 0;
            $existentes_actualizadas = 0;
            $omitidas = 0;

            foreach ($sucursales as $item) {
                $sucursalName = trim($item['titulo'] ?? '');
                $direccion = trim($item['direccion'] ?? 'S/N');
                $referencia = trim($item['referencia'] ?? '');
                $telefono1 = trim($item['telefono_primario'] ?? '');
                $telefono2 = trim($item['telefono_secundario'] ?? '');
                
                $telefonoFinal = $telefono1;
                if (!empty($telefono2)) {
                    $telefonoFinal .= ' / ' . $telefono2;
                }
                
                $direccionFinal = $direccion;
                if (!empty($referencia)) {
                    $direccionFinal .= ' (' . $referencia . ')';
                }

                // Omitir el registro "TODOS" u otros no válidos
                if (empty($sucursalName) || $sucursalName === 'TODOS' || $sucursalName === 'PRUEBA') {
                    continue;
                }

                // 3. Identificar el nombre del destino a buscar
                // Si contiene "LIMA", lo mapeamos automáticamente al destino "LIMA"
                $destinoName = str_contains(strtoupper($sucursalName), 'LIMA') ? 'LIMA' : $sucursalName;

                // Buscar el Destino localmente
                $destino = Destino::where('nombre', $destinoName)->first();

                // Intentar buscar por Provincia si el destino no se encontró (Marvisur da la provincia ahora)
                if (!$destino && !empty($item['provincia'])) {
                    $provinciaStr = trim($item['provincia']);
                    $destino = Destino::whereHas('Provincia', function($q) use ($provinciaStr) {
                        $q->where('nombre', $provinciaStr);
                    })->where('nombre', $sucursalName)->first();
                    
                    // Si aún no hay destino exacto pero tenemos provincia, podríamos intentar crear el destino?
                    // Por ahora mantendremos la regla de omitirlo si no existe.
                }

                if (!$destino) {
                    $this->warn("Destino no encontrado para: {$sucursalName} (Provincia Marvisur: " . ($item['provincia'] ?? 'N/A') . ")");
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
                        'direccion' => substr($direccionFinal, 0, 255), // Limitar a 255 chars
                        'telefono' => substr($telefonoFinal, 0, 50),
                        'estado' => 1
                    ]);
                    $nuevas++;
                    $this->line("Creado: {$sucursalName} en Destino: {$destino->nombre}");
                } else {
                    // Actualizamos la data rica
                    $subAgenciaExistente->update([
                        'direccion' => substr($direccionFinal, 0, 255),
                        'telefono' => substr($telefonoFinal, 0, 50),
                    ]);
                    $existentes_actualizadas++;
                }
            }

            $this->info("¡Sincronización Completada!");
            $this->info("- Nuevas Sub-Agencias: {$nuevas}");
            $this->info("- Actualizadas con Dirección: {$existentes_actualizadas}");
            $this->info("- Omitidas (Sin destino): {$omitidas}");

        } catch (\Exception $e) {
            $this->error('Error durante la sincronización: ' . $e->getMessage());
        }
    }
}
