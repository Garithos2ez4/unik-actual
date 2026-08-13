<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Envios\Agencia;
use App\Models\Envios\Destino;
use App\Models\Envios\SubAgencia;

class SyncShalomCommand extends Command
{
    protected $signature = 'sync:shalom';
    protected $description = 'Sincroniza las agencias de Shalom hacia nuestra base de datos local.';

    public function handle()
    {
        $this->info("Iniciando sincronización con Shalom...");

        $agencia = Agencia::where('nombre', 'SHALOM')->first();
        if (!$agencia) {
            $this->error('No se encontró la agencia SHALOM en la base de datos.');
            return;
        }

        $agencias = [];
        $apiSuccess = false;

        $this->info("Consultando API de Shalom...");
        try {
            // Intentar con el token antiguo por si acaso
            $tokenMagico = 'Bearer web-ae5e1e0f-5118-4b18-93ab-e5d51026dc57@1782579587@e2adae89f7a194ae111c8627e5e5fd8c300a80298b5c570683e5930432d452e3';
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => $tokenMagico,
                    'Accept' => 'application/json, text/plain, */*',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
                    'Origin' => 'https://shalom.com.pe',
                    'Referer' => 'https://shalom.com.pe/',
                ])
                ->asJson()
                ->post('https://serviceswebapi.shalomcontrol.com/api/v1/web/agencias/listar', [
                    'limit' => 1000,
                ]);

            if ($response->successful()) {
                $json = $response->json();
                if (isset($json['success']) && $json['success'] == true) {
                    $agencias = $json['data'] ?? [];
                    $apiSuccess = true;
                }
            }
        } catch (\Exception $e) {
            // Falla silenciosa para saltar al respaldo local
        }

        if (!$apiSuccess || empty($agencias)) {
            $this->warn("La API de Shalom bloqueó la solicitud (headers/tokens dinámicos) o falló.");
            $this->info("Intentando cargar datos desde el respaldo local...");
            
            $backupPath = storage_path('app/agencias_shalom.json');
            if (file_exists($backupPath)) {
                $backupData = json_decode(file_get_contents($backupPath), true);
                if (!empty($backupData)) {
                    // Si el JSON copiado por el usuario tiene la estructura { "data": [...] } o es un array directo [...]
                    if (isset($backupData['data']) && is_array($backupData['data'])) {
                        $agencias = $backupData['data'];
                    } else {
                        $agencias = $backupData;
                    }
                    $this->info("¡Respaldo local cargado correctamente con " . count($agencias) . " agencias!");
                } else {
                    $this->error("El respaldo local (agencias_shalom.json) está vacío.");
                    $this->info("Copia el JSON de la pestaña Network de Shalom y guárdalo en storage/app/agencias_shalom.json");
                    return;
                }
            } else {
                $this->error("No hay respaldo local disponible en storage/app/agencias_shalom.json.");
                $this->info("Por favor, entra a la web de Shalom, copia el JSON devuelto por /terminals y guárdalo en storage/app/agencias_shalom.json");
                return;
            }
        } else {
            // Guardar respaldo JSON local
            \Illuminate\Support\Facades\Storage::put('agencias_shalom.json', json_encode($agencias, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("Datos obtenidos correctamente de la API y respaldo actualizado.");
        }

        SubAgencia::where('idAgencia', $agencia->idAgencia)->update(['estado' => 0]);

        $nuevas = 0;
        $actualizadas = 0;
        $omitidas = 0;

        foreach ($agencias as $item) {
            $zona = trim($item['zona'] ?? '');
            $nombreSucursal = trim($item['nombre'] ?? '');
            $direccion = trim($item['direccion'] ?? 'S/N');
            $ubigeo = trim($item['ubigeo'] ?? '');

            if (empty($zona)) {
                continue;
            }

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
            $provinciaBusqueda = isset($item['provincia']) ? (isset($aliases[trim($item['provincia'])]) ? $aliases[trim($item['provincia'])] : trim($item['provincia'])) : '';
            $departamentoBusqueda = isset($item['departamento']) ? (isset($aliases[trim($item['departamento'])]) ? $aliases[trim($item['departamento'])] : trim($item['departamento'])) : '';

            $destino = Destino::select('destinos.*')
                ->join('provincias', 'destinos.idProvincia', '=', 'provincias.idProvincia')
                ->join('departamentos', 'provincias.idDepartamento', '=', 'departamentos.idDepartamento')
                ->whereRaw('UPPER(destinos.nombre) = ?', [strtoupper($zonaBusqueda)])
                ->when(!empty($provinciaBusqueda), function ($q) use ($provinciaBusqueda) {
                    return $q->whereRaw('UPPER(provincias.nombre) = ?', [strtoupper($provinciaBusqueda)]);
                })
                ->when(!empty($departamentoBusqueda), function ($q) use ($departamentoBusqueda) {
                    return $q->whereRaw('UPPER(departamentos.nombre) = ?', [strtoupper($departamentoBusqueda)]);
                })
                ->first();

            if (!$destino) {
                $destino = Destino::whereRaw('UPPER(nombre) = ?', [strtoupper($zonaBusqueda)])->first();
            }

            if (!$destino) {
                // $this->warn("  Destino no encontrado para la zona: {$zona} (sucursal: {$nombreSucursal})");
                $omitidas++;
                continue;
            }

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
                    'estado' => 1
                ]);
                $actualizadas++;
            }
        }

        $this->info("\n¡Sincronización SHALOM Completada!");
        $this->info("- Nuevas Sub-Agencias: {$nuevas}");
        $this->info("- Actualizadas con Dirección: {$actualizadas}");
        $this->info("- Omitidas (Sin destino local): {$omitidas}");
    }
}
