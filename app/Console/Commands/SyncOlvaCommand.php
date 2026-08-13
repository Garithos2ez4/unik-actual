<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Envios\Agencia;
use App\Models\Envios\Destino;
use App\Models\Envios\SubAgencia;

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

        $todasLasTiendas = []; // Para el respaldo JSON
        $apiSuccess = false;

        $this->info('Consultando API de Olva Courier (25 departamentos)...');
        $bar = $this->output->createProgressBar(25);
        $bar->start();

        // 2. Intentar descargar de la API
        for ($i = 1; $i <= 25; $i++) {
            try {
                $response = Http::withoutVerifying()
                    ->withHeaders([
                        'X-Requested-With' => 'XMLHttpRequest',
                        'Referer' => 'https://www.olvacourier.com/ubicanos',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    ])
                    ->asForm()
                    ->post('https://www.olvacourier.com/tiendas', [
                        'ubigeo' => $i,
                        'tipo' => 'tiendas',
                        'lugar' => 'departamento'
                    ]);

                if ($response->successful() && !empty($response->json())) {
                    $tiendas = $response->json();
                    $todasLasTiendas = array_merge($todasLasTiendas, $tiendas);
                    $apiSuccess = true;
                }
            } catch (\Exception $e) {
                // Silenciar errores de API aquí, si falla se usará el respaldo
            }

            $bar->advance();
            sleep(1);
        }

        $bar->finish();
        $this->newLine(2);

        if (!$apiSuccess || empty($todasLasTiendas)) {
            $this->warn("La API de Olva Courier fue bloqueada (Posible Cloudflare 403) o no retornó datos.");
            $this->info("Intentando cargar datos desde el respaldo local...");

            $backupPath = storage_path('app/olva_agencias.json');
            if (file_exists($backupPath)) {
                $backupContent = file_get_contents($backupPath);
                $backupData = json_decode($backupContent, true);

                if (!empty($backupData)) {
                    $todasLasTiendas = $backupData;
                    $this->info("¡Respaldo local cargado correctamente con " . count($todasLasTiendas) . " sucursales!");
                } else {
                    $this->error("El respaldo local (olva_agencias.json) está vacío. No se puede continuar.");
                    $this->info("Por favor, entra a la web de Olva y consigue el JSON manualmente para colocarlo en storage/app/olva_agencias.json.");
                    return;
                }
            } else {
                $this->error("No hay respaldo local disponible en storage/app/olva_agencias.json.");
                return;
            }
        } else {
            // Guardar respaldo JSON local por seguridad (anti-Cloudflare)
            file_put_contents(storage_path('app/olva_agencias.json'), json_encode($todasLasTiendas, JSON_PRETTY_PRINT));
            $this->info("Datos obtenidos correctamente de la API y respaldo actualizado.");
        }

        // --- BORRADO LÓGICO --- (Solo lo hacemos si ya tenemos tiendas válidas para procesar)
        SubAgencia::where('idAgencia', $agencia->idAgencia)->update(['estado' => 0]);

        $nuevas = 0;
        $actualizadas = 0;
        $omitidas = 0;

        foreach ($todasLasTiendas as $tienda) {
            $tipoSede = trim($tienda['office_type'] ?? '');
            if (!in_array($tipoSede, ['OFICINAS EXTERNAS', 'OFICINA OPERACIONES', 'AGENTE'])) {
                continue;
            }

            $nombreSucursalOriginal = trim($tienda['nombres'] ?? '');
            $direccion = trim($tienda['direccion'] ?? 'S/N');
            $ubigeo = trim($tienda['ubigeo'] ?? '');

            if (empty($nombreSucursalOriginal) || empty($ubigeo)) {
                continue;
            }

            $nombreLimpio = mb_strtoupper($nombreSucursalOriginal, 'UTF-8');
            $nombreLimpio = str_replace(['–', '—'], '-', $nombreLimpio);

            // Separar por guion con espacios para no romper nombres como "IV- CENTENARIO"
            if (strpos($nombreLimpio, ' - ') !== false) {
                $nombreLimpio = explode(' - ', $nombreLimpio)[0];
            } else {
                $nombreLimpio = explode('-', $nombreLimpio)[0]; // Fallback
            }

            // Eliminar texto entre paréntesis ej: "CUMBA (BGR)" -> "CUMBA "
            $nombreLimpio = preg_replace('/\(.*?\)/', '', $nombreLimpio);

            // Limpiar espacios múltiples que hayan quedado después de borrar los paréntesis
            $nombreLimpio = preg_replace('/\s+/', ' ', $nombreLimpio);
            $nombreLimpio = trim($nombreLimpio);

            // Limpiar prefijos comunes de la nueva API de Olva
            $prefijos = ['AGENTE OLVA ', 'TIENDA OLVA ', 'AGENTE ', 'TIENDA ', 'OLVA '];
            foreach ($prefijos as $prefijo) {
                if (strpos($nombreLimpio, $prefijo) === 0) {
                    $nombreLimpio = trim(substr($nombreLimpio, strlen($prefijo)));
                    break;
                }
            }

            $excepciones = [
                'LIMA CERCADO' => 'LIMA',
                'BAGUA CHICA' => 'BAGUA',
                'ZÁRATE' => 'SAN JUAN DE LURIGANCHO',
                'HIGUERETA' => 'SURCO',
                'MUSA' => 'LA MOLINA',
                'PLAZA TEC' => 'LIMA',
                'CAÑETE' => 'SAN VICENTE DE CAÑETE',
                'PUCALLPA' => 'CALLERIA',
                'AGUAYTIA' => 'PADRE ABAD',
                'ATALAYA' => 'RAYMONDI',
                'NARANJOS' => 'PARDO MIGUEL',
                'ROQUE' => 'ALONSO DE ALVARADO',
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
                'OLVA VMT' => 'VILLA MARIA DEL TRIUNFO',
                'VMT' => 'VILLA MARIA DEL TRIUNFO',
                'SJM' => 'SAN JUAN DE MIRAFLORES',
                'SMP' => 'SAN MARTIN DE PORRES',
                'VES' => 'VILLA EL SALVADOR',
                'REAL PLAZA VILLA MARIA' => 'VILLA MARIA DEL TRIUNFO',
                'CHILCA (MALA)' => 'CHILCA',
                'ALONSO DE ALVA' => 'ALONSO DE ALVARADO',
                'AG. OLVA'                                 => 'AREQUIPA', // O 'CERRO COLORADO' si prefieres centralizar Ciudad Municipal ahí
                'HUNTER'                                   => 'JACOBO HUNTER',
                'LA JOYA-AREQUIPA'                         => 'LA JOYA',
                'AV KENNEDY'                               => 'PAUCARPATA',
                'AV ARGENTINA'                             => 'PAUCARPATA',
                'HORACIO ZEBALLOS -SOCABAYA-BOTICA FLORES' => 'SOCABAYA',
                'ALMACEN AQP'                              => 'AREQUIPA', // O 'YANAHUARA' según la dirección
                'PIZARRO'                                  => 'JOSE LUIS BUSTAMANTE Y RIVERO',
                'ATICO-AREQUIPA'                           => 'ATICO',

                // Nuevas excepciones agregadas
                'PUCALLPA ALMACEN' => 'CALLERIA',
                'PIÑATERIA DULCE FANTASI' => 'CERRO COLORADO',
                'PIÑATERIA DULCE FANTASIA-CERRO COLORADO' => 'CERRO COLORADO',
                'MULTIPEL CHIVAY-AREQUIPA' => 'CHIVAY',
                'PRINCIPAL' => 'AYACUCHO',
                // --- LIMA CENTRO ---
                'CERCADO DE LIMA'                                  => 'LIMA',
                'REAL PLAZA CENTRO CIVICO'                         => 'LIMA',
                'ARGENTINA'                                        => 'LIMA',
                'BREÑA -MORONA'                                    => 'BREÑA',
                'REAL PLAZA'                                       => 'JESUS MARIA', // CC Real Plaza Salaverry
                'MAGDALENA AV BRASIL C36 LA OFICINA ON LINE EIRL'  => 'MAGDALENA DEL MAR',

                // --- LIMA ESTE ---
                'REAL PLAZA PURUCHUCO'                             => 'ATE',
                'REAL PLAZA SANTA CLARA'                           => 'ATE',
                'CHACLACAYO -LICORERIA EL TONEL DE CARUGA'         => 'CHACLACAYO',
                'CP CIENEGUILLA'                                   => 'CIENEGUILLA',
                'PARQUE LA MOLINA'                                 => 'LA MOLINA',
                'LA MOLINA CORREGIDOR CUADRA 22'                   => 'LA MOLINA',
                'SJL MANGOMARCA'                                   => 'SAN JUAN DE LURIGANCHO',
                'SJL'                                              => 'SAN JUAN DE LURIGANCHO',
                'SJL- PIRAMIDES DEL SOL'                           => 'SAN JUAN DE LURIGANCHO',
                'C.P SJL'                                          => 'SAN JUAN DE LURIGANCHO',
                'ZARATE'                                           => 'SAN JUAN DE LURIGANCHO',
                'AGENTESJL'                                        => 'SAN JUAN DE LURIGANCHO',

                // --- LIMA NORTE ---
                'CARABAYLLO SAN ANTONIO DISTRIBUCIONES SLAM S.A.C' => 'CARABAYLLO',
                'MEGAPLAZA INDEPENDENCIA'                          => 'INDEPENDENCIA',
                'LOS OLVIOS.CALLE 11'                              => 'LOS OLIVOS', // Escrito con error ortográfico por Olva
                'PUENTE PIEDRA -TRANSPORTES MEDINA'                => 'PUENTE PIEDRA',
                'SMP AV.PERU'                                      => 'SAN MARTIN DE PORRES',
                'SMP C.P'                                          => 'SAN MARTIN DE PORRES',

                // --- LIMA SUR / BALNEARIOS ---
                'REAL PLAZA GUARDIA CIVIL'                         => 'CHORRILLOS',
                'MEGAPLAZA CHORRILOS'                              => 'CHORRILLOS',
                'MINIMARKET PANADERIA NELDY'                       => 'PUNTA HERMOSA',
                'SAN JUAN MIRAFLORES'                              => 'SAN JUAN DE MIRAFLORES',
                'VMT- DISTRIBUIDORA CLC MOVILES S.A.C'             => 'VILLA MARIA DEL TRIUNFO',
                'VILLA MARIA DEL TRIUNFO PACHACUTEC'               => 'VILLA MARIA DEL TRIUNFO',

                // --- LIMA MODERNA ---
                'LARCOMAR'                                         => 'MIRAFLORES',
                'REAL PLAZA PRIMAVERA'                             => 'SAN BORJA',
                'AVIACION'                                         => 'SAN BORJA',
                'SAN BORJA SUR'                                    => 'SAN BORJA',
                'SURCO EL POLO'                                    => 'SANTIAGO DE SURCO',
                'SURCO HIGUERETA'                                  => 'SANTIAGO DE SURCO',

                // --- PROVINCIAS ---
                'MAZUCO'                                           => 'INAMBARI',
                // --- AREQUIPA ---
                'ENACE'                      => 'CAYMA',
                'PIÑATERIA DULCE FANTASIA'   => 'CERRO COLORADO',

                // --- AYACUCHO ---
                'TERMINAL'                   => 'AYACUCHO',
                'CENTRO'                     => 'AYACUCHO',
                'CORA CORA'                  => 'CORACORA', // En la base de datos nacional suele escribirse junto
                'PAUSA (PAUCAR DEL SA'       => 'PAUSA',

                // --- CAJAMARCA ---
                'CAJAMARCA-ALMACEN CENTRAL'  => 'CAJAMARCA',

                // --- CALLAO ---
                'ALMACEN'                    => 'CALLAO',

                // --- CUSCO ---
                'PARDO-CUSCO'                => 'CUSCO',
                'LOS NOGALES'                => 'SAN SEBASTIAN',
                'WANCHAQ CUSCO'              => 'WANCHAQ',
                // Nuevas excepciones sugeridas
                'CUMBA' => 'CUMBA',
                'CUMBA (BGR)' => 'CUMBA',
                'MORAL' => 'AREQUIPA',
                'MORAL (EX' => 'AREQUIPA',
                'IV' => 'AREQUIPA',
                'IV- CENTENARIO' => 'AREQUIPA',
                'IMACITA' => 'IMAZA',
                'IMAZA CHIRIACO' => 'IMAZA',
                'SANTA MARIA DE NIEVA' => 'NIEVA',
                'CAMPORREDONDO' => 'CAMPORREDONDO',
                // --- PIURA ---
                'PIURA-OFICINA ALMACEN FICUS' => 'PIURA',
                'PIURA-TIENDA SANTA ISABEL'   => 'PIURA',
                'PIURA-TIENDA CATACAOS'       => 'CATACAOS',
                'AYABACA-PIURA'               => 'AYABACA',
                'PAITA ALTA'                  => 'PAITA',

                // --- PUNO ---
                'MACUSANI-JULIACA'                  => 'MACUSANI',
                'JULIACA CENTRAL'                   => 'JULIACA',
                'JULIACA TIENDA'                    => 'JULIACA',
                'JULIACA-TIENDA TERMINAL TERRESTRE' => 'JULIACA',

                // --- SAN MARTIN ---
                'ALONSO DE ALVA-ROQUE' => 'ALONSO DE ALVARADO',
                'PARDO MIGUEL-NARANJO' => 'PARDO MIGUEL',

                // --- TACNA ---
                'TACNA-ALMACEN'  => 'TACNA',
                'BOLIVAR-TACNA'  => 'TACNA',
                'CONO SUR-TACNA' => 'CORONEL GREGORIO ALBARRACIN LANCHIPA',
                // Nota Tacna: Si 'CORONEL GREGORIO ALBARRACIN LANCHIPA' te rebota, 
                // intenta cambiarlo a 'GREGORIO ALBARRACIN' (depende de cómo esté en tu tabla destinos).
                // --- CUSCO ---
                'YAURI' => 'ESPINAR', // Yauri es la capital de la provincia de Espinar

                // --- HUANUCO ---
                'OPERACIONES HUANUCO'  => 'HUANUCO',
                'PORTALES'             => 'HUANUCO',
                'OPERACIONES TINGO MARIA' => 'RUPA-RUPA', // Distrito oficial de Tingo María
                'AUCAYACU-TINGO MARIA' => 'JOSE CRESPO Y CASTILLO',

                // --- JUNIN (HUANCAYO / SELVA CENTRAL) ---
                'OMAR YALI'      => 'HUANCAYO', // Omar Yali es una calle céntrica
                'MARIATEGUI'     => 'EL TAMBO',
                'PICHANAKI'      => 'PICHANAQUI', // Ortografía de la base de datos nacional
                'TIENDASATIPO'   => 'SATIPO',

                // --- LA LIBERTAD (TRUJILLO / SIERRA) ---
                'TRUJILLO- TIENDA TUPAC AMARU' => 'TRUJILLO',
                'OLVATRU'                      => 'TRUJILLO',
                'BOLIVAR ( PROV. BOLIVAR'      => 'BOLIVAR', // Quedó sin cerrar el paréntesis por el corte del guion
                'PACANGUILLA'                  => 'PACANGA', // Pacanguilla pertenece al distrito de Pacanga
                'PACASMAYO TIENDA -AV GONZALO UGAS SALCEDO 09' => 'PACASMAYO',
                'CRUCE SAN JOSE'               => 'SAN JOSE',

                // --- LAMBAYEQUE (CHICLAYO) ---
                'TACNA-CHICLAYO'           => 'CHICLAYO',
                'SANTA VICTORIA-CHICLAYO'  => 'CHICLAYO',
                'OFICINA ALMACEN-CHICLAYO' => 'CHICLAYO',
                'MOSHOQUEQUE'              => 'JOSE LEONARDO ORTIZ', // Zona comercial de JLO
                'PIMENTEL-CHICLAYO'        => 'PIMENTEL',
                'FERREÑAFE-CHICLAYO'       => 'FERREÑAFE',

                // --- LIMA ---
                'AGENTE' => 'JESUS MARIA', // (Lee la nota de advertencia abajo sobre esto)

                // --- PASCO / SELVA ---
                'PUERTO BERMUNDEZ' => 'PUERTO BERMUDEZ', // Error ortográfico de Olva (pusieron una N extra)
                // --- TUMBES ---
                'MINIMARKET R&R' => 'TUMBES',
                // --- PIURA ---
                'PIURA-TIENDASANCHEZ CERRO' => 'PIURA',
                // --- TUMBES ---
                'TUMBES-TIENDA ZORRITOS'  => 'ZORRITOS',
                'TUMBES-TIENDA ZARUMILLA' => 'ZARUMILLA',
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

            // Intentar matchear con el ubigeo primero usando idProvincia
            // (El ubigeo de Olva es el codigo del distrito? A veces no machea directo, la logica anterior usaba el id del departamento del for-loop, 
            // pero como unificamos el array, buscaremos el destino directo sin restringir por departamento).
            $destino = Destino::whereRaw('UPPER(nombre) = ?', [$nombreLimpio])->first();

            if (!$destino) {
                $this->warn("\nDestino no encontrado para: {$nombreSucursalOriginal} (Buscado como: {$nombreLimpio})");
                $omitidas++;
                continue;
            }

            $subAgenciaExistente = SubAgencia::where('idAgencia', $agencia->idAgencia)
                ->where('idDestino', $destino->idDestino)
                ->where('nombre_oficina', $nombreSucursalOriginal)
                ->first();

            if (!$subAgenciaExistente) {
                SubAgencia::create([
                    'idAgencia' => $agencia->idAgencia,
                    'idDestino' => $destino->idDestino,
                    'nombre_oficina' => $nombreSucursalOriginal,
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

        $this->info("\n¡Sincronización OLVA Completada con éxito!");
        $this->info("- Nuevas Sub-Agencias: {$nuevas}");
        $this->info("- Actualizadas con Dirección: {$actualizadas}");
        $this->info("- Omitidas (Sin destino local): {$omitidas}");
    }
}
