<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Envios\Agencia;
use App\Models\Envios\Destino;
use App\Models\Envios\SubAgencia;

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

            // Guardar respaldo JSON local por seguridad (anti-Cloudflare)
            file_put_contents(storage_path('app/marvisur_agencias.json'), json_encode($json, JSON_PRETTY_PRINT));

            $sucursales = $json['data']['Table'];

            $idsVistos = [];
            $nuevas = 0;
            $actualizadas = 0;
            $sinCambios = 0;
            $omitidas = 0;

            $excepciones = [
                'AGUAYTIA' => 'PADRE ABAD',
                'BAGUA CHICA' => 'BAGUA',
                'CANTA CALLAO' => 'CALLAO',
                'CAÑETE' => 'SAN VICENTE DE CAÑETE',
                'CERRO DE PASCO' => 'CHAUPIMARCA',
                'CERRO JULI' => 'JOSE LUIS BUSTAMANTE Y RIVERO',
                'CHINCHA' => 'CHINCHA ALTA',
                'CIUDAD DE DIOS' => 'PACASMAYO',
                'CONO NORTE' => 'CERRO COLORADO',
                'CORPORATIVOS' => 'LIMA',
                'FIORI' => 'SAN MARTIN DE PORRES',
                'GARCI CARBAJAL' => 'AREQUIPA',
                'HUAYCAN' => 'ATE',
                'LAS MALVINAS' => 'LIMA',
                'MANCHAY' => 'PACHACAMAC',
                'MARVI' => 'LA VICTORIA',
                'MARVI EXPRESS' => 'LA VICTORIA',
                'MAZUKO' => 'INAMBARI',
                'PEDREGAL' => 'MAJES',
                'PEDRO RUIZ GALLO' => 'JAZAN',
                'PUCALLPA' => 'CALLERIA',
                'QUILLABAMBA' => 'SANTA ANA',
                'TALARA' => 'PARIÑAS',
                'TINGO MARIA' => 'RUPA-RUPA',
                'VILLA MARIA' => 'VILLA MARIA DEL TRIUNFO',
                'SAN VICENTE DE CAÑETE' => 'SAN VICENTE DE CAÑETE',
                'RUPA RUPA' => 'RUPA-RUPA',
                'LA VICTORIA(LIMA)' => 'LA VICTORIA',
            ];

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

                // 3. Buscar el destino:
                // Prioridad A: Usar el distrito oficial provisto directamente por Marvisur
                $distritoMarvi = trim($item['distrito'] ?? '');
                $provinciaMarvi = trim($item['provincia'] ?? '');
                $distritoLimpio = preg_replace('/\s*\(.*?\)\s*/', '', $distritoMarvi);

                $distritoBusqueda = $excepciones[strtoupper($distritoMarvi)] 
                    ?? ($excepciones[strtoupper($distritoLimpio)] ?? $distritoLimpio);
                $provinciaBusqueda = $excepciones[strtoupper($provinciaMarvi)] ?? $provinciaMarvi;

                $destino = null;

                if (!empty($distritoBusqueda)) {
                    $q = Destino::select('destinos.*')
                        ->join('provincias', 'destinos.idProvincia', '=', 'provincias.idProvincia')
                        ->whereRaw('UPPER(destinos.nombre) = ?', [strtoupper($distritoBusqueda)]);
                    if (!empty($provinciaBusqueda)) {
                        $destino = (clone $q)->whereRaw('UPPER(provincias.nombre) = ?', [strtoupper($provinciaBusqueda)])->first();
                    }
                    if (!$destino) {
                        $destino = $q->first();
                    }
                }

                // Prioridad B (fallback): Inferir destino desde el título de la sucursal
                if (!$destino) {
                    $destinoName = preg_replace('/\s+([IVXLCDM]+|ALMACEN|EXPRESS|CORPORATIVOS)$/i', '', $sucursalName);
                    $destinoName = $excepciones[strtoupper($destinoName)] ?? $destinoName;

                    if (!empty($provinciaBusqueda)) {
                        $destino = Destino::whereHas('Provincia', function($q) use ($provinciaBusqueda) {
                            $q->where('nombre', $provinciaBusqueda);
                        })->whereRaw('UPPER(nombre) = ?', [strtoupper($destinoName)])->first();
                    }
                    if (!$destino) {
                        $destino = Destino::whereRaw('UPPER(nombre) = ?', [strtoupper($destinoName)])->first();
                    }
                }

                if (!$destino) {
                    $this->warn("Destino no encontrado para: {$sucursalName} (Distrito Marvisur: {$distritoMarvi}, Provincia: {$provinciaMarvi})");
                    $omitidas++;
                    continue;
                }

                // 4. Crear o actualizar SubAgencia
                $subAgencia = SubAgencia::where('idAgencia', $agencia->idAgencia)
                    ->where('idDestino', $destino->idDestino)
                    ->where('nombre_oficina', $sucursalName)
                    ->first();

                if (!$subAgencia) {
                    // Buscar si existía pero con idDestino previo para reubicarlo
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
                        'direccion' => substr($direccionFinal, 0, 255),
                        'telefono' => substr($telefonoFinal, 0, 50),
                        'estado' => 1
                    ]);
                    $idsVistos[] = $subAgencia->idSubAgencia;
                    $nuevas++;
                    $this->line("Creado: {$sucursalName} en Destino: {$destino->nombre}");
                } else {
                    $subAgencia->fill([
                        'idDestino' => $destino->idDestino,
                        'direccion' => substr($direccionFinal, 0, 255),
                        'telefono' => substr($telefonoFinal, 0, 50),
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

            // Desactivar solo las sub-agencias que ya no existen en Marvisur
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
            $this->error('Error durante la sincronización: ' . $e->getMessage());
        }
    }
}
