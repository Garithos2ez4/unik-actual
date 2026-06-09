<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Agencia;
use App\Models\Destino;
use App\Models\SubAgencia;

class SyncShalomCommand extends Command
{
    protected $signature = 'sync:shalom';
    protected $description = 'Sincroniza las agencias de Shalom hacia nuestra base de datos local.';

    public function handle()
    {
        $this->info("Iniciando sincronización con Shalom... 🥷");

        $agencia = Agencia::where('nombre', 'SHALOM')->first();
        if (!$agencia) {
            $this->error('No se encontró la agencia SHALOM en la base de datos.');
            return;
        }

        $tokenMagico = 'Bearer web-23027e18-742e-441f-bced-96027c306163@1780955419@28d2d7dfa93b373922582fcd43664c945e8e9563b06804f56a42e8b55dce15a4';

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => $tokenMagico,
                    'Accept' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0'
                ])
                ->post('https://serviceswebapi.shalomcontrol.com/api/v1/web/agencias/listar');

            if ($response->successful()) {
                $json = $response->json();

                if (isset($json['success']) && $json['success'] == true) {
                    $agencias = $json['data'];
                    
                    // Guardar respaldo JSON
                    \Illuminate\Support\Facades\Storage::put('shalom_agencias.json', json_encode($agencias, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    $this->line("📂 Archivo de respaldo: " . storage_path('app/shalom_agencias.json'));

                    $nuevas = 0;
                    $actualizadas = 0;
                    $omitidas = 0;

                    foreach ($agencias as $item) {
                        // "zona" es el distrito en la API de Shalom
                        $zona = trim($item['zona'] ?? '');
                        // "nombre" es la ruta completa o el nombre comercial de la sucursal
                        $nombreSucursal = trim($item['nombre'] ?? '');
                        $direccion = trim($item['direccion'] ?? 'S/N');
                        $ubigeo = trim($item['ubigeo'] ?? '');
                        
                        if (empty($zona)) {
                            continue;
                        }

                        // Diccionario de equivalencias (Shalom -> Local)
                        $aliases = [
                            'CERCADO LIMA' => 'LIMA',
                            'ATE-VITARTE' => 'ATE',
                            'SAN VICENTE DE CANET' => 'SAN VICENTE DE CAÑETE',
                            '26 DE OCTUBRE' => 'VEINTISEIS DE OCTUBRE',
                            'PUCALLPA CALLERIA' => 'CALLERIA',
                            'PUCALLPA YARINACOCHA' => 'YARINACOCHA',
                            'PUCALLPA MANANTAY' => 'MANANTAY',
                            'RUPA RUPA' => 'RUPA-RUPA',
                            'YAURI ( ESPINAR )' => 'ESPINAR',
                            'JOSE CRESPO Y CASTIL' => 'JOSE CRESPO Y CASTILLO',
                            'BAJO PICHANAQUI' => 'PICHANAQUI',
                            'CHURIN' => 'PACHANGARA',
                            'IQUITOS SAN JUAN BAUTISTA' => 'SAN JUAN BAUTISTA',
                            'AGUAYTIA' => 'PADRE ABAD'
                        ];

                        $zonaBusqueda = isset($aliases[$zona]) ? $aliases[$zona] : $zona;

                        // Buscar el Destino por zona (distrito) o su alias
                        $destino = Destino::whereRaw('UPPER(nombre) = ?', [strtoupper($zonaBusqueda)])->first();

                        if (!$destino) {
                            $this->warn("  Destino no encontrado para la zona: {$zona} (sucursal: {$nombreSucursal})");
                            $omitidas++;
                            continue;
                        }

                        // Crear o actualizar SubAgencia
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

                    $this->info("¡Sincronización SHALOM Completada!");
                    $this->info("- Nuevas Sub-Agencias: {$nuevas}");
                    $this->info("- Actualizadas con Dirección: {$actualizadas}");
                    $this->info("- Omitidas (Sin destino local): {$omitidas}");

                } else {
                    $this->error("El servidor respondió, pero rechazó la petición. ¿El token expiró?");
                }
            } else {
                $this->error("❌ Error HTTP " . $response->status() . ". ¡El token Bearer ha expirado o es inválido!");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error crítico: " . $e->getMessage());
        }
    }
}
