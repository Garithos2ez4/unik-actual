<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Envios\Agencia;
use App\Models\Envios\Destino;
use App\Models\Envios\SubAgencia;

class SyncShalomCommand extends Command
{
    protected $signature = 'sync:shalom';
    protected $description = 'Sincroniza las agencias de Shalom hacia nuestra base de datos local.';

    /**
     * Consulta el endpoint encriptado de shalom.com.pe (/api/local/session + /api/v1/web/agencias/listar)
     * y desencripta la respuesta AES-256-CBC con la llave de sesión generada.
     */
    protected function scrapeShalomAgencias(): array
    {
        $sessionKeyRaw = random_bytes(32);
        $sessionKeyB64 = base64_encode($sessionKeyRaw);
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';

        // 1. Obtener token de sesión proxy y cookies desde shalom.com.pe
        $ch = curl_init('https://shalom.com.pe/api/local/session');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: ' . $userAgent,
            'Referer: https://shalom.com.pe/',
            'X-Requested-With: XMLHttpRequest',
            'X-Session-Key: ' . $sessionKeyB64,
        ]);

        $resp1 = curl_exec($ch);
        if ($resp1 === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Error cURL en /api/local/session: ' . $err);
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $code1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code1 !== 200) {
            throw new \RuntimeException("HTTP {$code1} al obtener sesión de Shalom.");
        }

        $headers1 = substr($resp1, 0, $headerSize);
        $body1 = substr($resp1, $headerSize);

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers1, $matches);
        $cookies = [];
        foreach ($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        $cookieStr = '';
        foreach ($cookies as $k => $v) {
            if (!in_array(strtolower($k), ['expires', 'max-age', 'path', 'httponly', 'secure', 'samesite'])) {
                $cookieStr .= $k . '=' . $v . '; ';
            }
        }

        $sessData = json_decode($body1, true);
        $csrf = $sessData['csrf'] ?? '';
        if (empty($csrf)) {
            throw new \RuntimeException('No se recibió token csrf en /api/local/session.');
        }

        // 2. Consultar /api/v1/web/agencias/listar usando X-Proxy-Token y X-Session-Key
        $ch2 = curl_init('https://shalom.com.pe/api/v1/web/agencias/listar');
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch2, CURLOPT_POST, 1);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, '{}');
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
        if (!empty($cookieStr)) {
            curl_setopt($ch2, CURLOPT_COOKIE, $cookieStr);
        }
        curl_setopt($ch2, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: ' . $userAgent,
            'Origin: https://shalom.com.pe',
            'Referer: https://shalom.com.pe/',
            'X-Requested-With: XMLHttpRequest',
            'X-Proxy-Token: ' . $csrf,
            'X-Session-Key: ' . $sessionKeyB64,
        ]);

        $body2 = curl_exec($ch2);
        if ($body2 === false) {
            $err = curl_error($ch2);
            curl_close($ch2);
            throw new \RuntimeException('Error cURL en /api/v1/web/agencias/listar: ' . $err);
        }
        $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);

        if ($code2 !== 200) {
            throw new \RuntimeException("HTTP {$code2} al listar agencias de Shalom.");
        }

        $json2 = json_decode($body2, true);
        if (!is_array($json2)) {
            throw new \RuntimeException('Respuesta inválida (no JSON) desde Shalom.');
        }

        // 3. Desencriptar si viene cifrado con AES-256-CBC
        if (!empty($json2['encrypted']) && !empty($json2['data']) && is_string($json2['data'])) {
            $raw = base64_decode($json2['data'], true);
            if ($raw === false || strlen($raw) <= 16) {
                throw new \RuntimeException('Payload encriptado inválido.');
            }
            $iv = substr($raw, 0, 16);
            $cipher = substr($raw, 16);
            $decrypted = openssl_decrypt($cipher, 'AES-256-CBC', $sessionKeyRaw, OPENSSL_RAW_DATA, $iv);
            if ($decrypted === false) {
                throw new \RuntimeException('Fallo al desencriptar AES-256-CBC de Shalom.');
            }
            $json2 = json_decode($decrypted, true);
        }

        if (!empty($json2['success']) && !empty($json2['data']) && is_array($json2['data'])) {
            return $json2['data'];
        }

        throw new \RuntimeException('El listado desencriptado no contiene agencias válidas.');
    }

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

        $this->info("Scrapeando API de Shalom (shalom.com.pe)...");
        try {
            $agencias = $this->scrapeShalomAgencias();
            if (!empty($agencias)) {
                $apiSuccess = true;
            }
        } catch (\Throwable $e) {
            $this->warn("Aviso al consultar API en vivo: " . $e->getMessage());
        }

        if (!$apiSuccess || empty($agencias)) {
            $this->warn("No se pudo conectar en vivo con Shalom. Intentando cargar desde el respaldo local...");

            $backupPath = storage_path('app/agencias_shalom.json');
            if (file_exists($backupPath)) {
                $backupData = json_decode(file_get_contents($backupPath), true);
                if (!empty($backupData)) {
                    if (isset($backupData['data']) && is_array($backupData['data'])) {
                        $agencias = $backupData['data'];
                    } else {
                        $agencias = $backupData;
                    }
                    $this->info("¡Respaldo local cargado correctamente con " . count($agencias) . " agencias!");
                } else {
                    $this->error("El respaldo local (agencias_shalom.json) está vacío.");
                    return;
                }
            } else {
                $this->error("No hay respaldo local disponible en storage/app/agencias_shalom.json.");
                return;
            }
        } else {
            // Guardar respaldo JSON local actualizado
            Storage::put('agencias_shalom.json', json_encode($agencias, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("¡Datos obtenidos en vivo de Shalom (" . count($agencias) . " sedes) y respaldo actualizado!");
        }

        // IDs de sub-agencias presentes en el listado actual de Shalom.
        // Al final se desactivan solo las que ya no aparecen (sin tocar updated_at de las demás).
        $idsVistos = [];

        // Mapa de sedes que cambiaron de nombre en Shalom (nuevo_nombre => antiguo_nombre)
        // para actualizar el registro existente y mantener la referencia en envíos previos
        $renamedOffices = [
            'HUANUCO / HUANUCO / HUANUCO / JR MAYRO' => 'HUANUCO / HUANUCO / HUANUCO / JR AGUILAR',
            'ANCASH / HUARAZ / HUARAZ / AV. 27 DE NOVIEMBRE CO' => 'ANCASH / HUARAZ / HUARAZ / HUARAZ CO',
            'CUSCO / CANCHIS / SICUANI / SICUANI AV CONFEDERACION CO' => 'CUSCO / CANCHIS / SICUANI / SICUANI CO OVALO SAN ANDRES',
            'HUANCAVELICA / HUANCAVELICA / HUANCAVELICA / BARRIO STA ANA CO' => 'HUANCAVELICA / HUANCAVELICA / HUANCAVELICA / HUANCAVELICA',
            'LA LIBERTAD / TRUJILLO / EL PORVENIR / AV. 12 DE NOVIEMBRE' => 'LA LIBERTAD / TRUJILLO / EL PORVENIR / AV. 12 DE NOVIEMBRE (POR DEFINIR)',
            'LIMA / LIMA / PACHACAMAC / EL RETAMAL' => 'LIMA / LIMA / PACHACAMAC / AV. VICTOR MALASQUEZ - PACHACAMAC (POR DEFINIR)',
            'MADRE DE DIOS / TAMBOPATA / TAMBOPATA / TAMBOPATA AV UCAYALI  CO' => 'MADRE DE DIOS / TAMBOPATA / TAMBOPATA / TAMBOPATA AV LA JOYA CO',
        ];

        // Correcciones puntuales por ubicación completa (DEPARTAMENTO|PROVINCIA|ZONA_SHALOM => distrito local).
        // Evita homónimos: p.ej. "LA MERCED" de Shalom es la capital del distrito CHANCHAMAYO (Junín),
        // no el distrito LA MERCED de Aija (Áncash).
        $ubicacionOverrides = [
            'JUNIN|CHANCHAMAYO|LA MERCED' => 'CHANCHAMAYO',
        ];

        $nuevas = 0;
        $actualizadas = 0;
        $sinCambios = 0;
        $reubicadas = 0;
        $omitidas = 0;
        $detalleOmitidas = [];
        $detalleCambios = [];

        foreach ($agencias as $item) {
            $zona = trim($item['zona'] ?? '');
            $nombreSucursal = trim($item['nombre'] ?? '');
            $direccion = trim($item['direccion'] ?? 'S/N');

            if (empty($zona) || empty($nombreSucursal)) {
                continue;
            }

            // Omitir terminales de prueba interna de Shalom
            if (stripos($nombreSucursal, 'Prueba sistemas') !== false || stripos($nombreSucursal, 'Terminal de prueba') !== false) {
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
                'AGUAYTIA' => 'PADRE ABAD',
                'CONTRALMIRANTE VILLA' => 'CONTRALMIRANTE VILLAR'
            ];

            $zonaBusqueda = isset($aliases[$zona]) ? $aliases[$zona] : $zona;
            $provinciaBusqueda = isset($item['provincia']) ? (isset($aliases[trim($item['provincia'])]) ? $aliases[trim($item['provincia'])] : trim($item['provincia'])) : '';
            $departamentoBusqueda = isset($item['departamento']) ? (isset($aliases[trim($item['departamento'])]) ? $aliases[trim($item['departamento'])] : trim($item['departamento'])) : '';

            $claveUbicacion = strtoupper(trim($item['departamento'] ?? '') . '|' . trim($item['provincia'] ?? '') . '|' . $zona);
            if (isset($ubicacionOverrides[$claveUbicacion])) {
                $zonaBusqueda = $ubicacionOverrides[$claveUbicacion];
            }

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

            $candidatos = collect();
            if (!$destino) {
                // Fallback solo por nombre de distrito: únicamente si es inequívoco (sin homónimos)
                $candidatos = Destino::whereRaw('UPPER(nombre) = ?', [strtoupper($zonaBusqueda)])->limit(2)->get();
                if ($candidatos->count() === 1) {
                    $destino = $candidatos->first();
                } elseif ($candidatos->count() > 1) {
                    $detalleOmitidas[] = "{$nombreSucursal} (distrito homónimo: {$zonaBusqueda})";
                }
            }

            if (!$destino) {
                $omitidas++;
                if (!$candidatos->count()) {
                    $detalleOmitidas[] = "{$nombreSucursal} (sin destino local)";
                }
                continue;
            }

            // 1) Misma oficina en el mismo destino
            $subAgencia = SubAgencia::where('idAgencia', $agencia->idAgencia)
                ->where('idDestino', $destino->idDestino)
                ->where('nombre_oficina', $nombreSucursal)
                ->first();

            // 2) Oficina renombrada por Shalom (se actualiza en el mismo registro)
            if (!$subAgencia && isset($renamedOffices[$nombreSucursal])) {
                $subAgencia = SubAgencia::where('idAgencia', $agencia->idAgencia)
                    ->where('idDestino', $destino->idDestino)
                    ->where('nombre_oficina', $renamedOffices[$nombreSucursal])
                    ->first();
            }

            // 3) Misma oficina asignada a otro destino (p.ej. por un homónimo mal resuelto antes): se reubica
            if (!$subAgencia) {
                $subAgencia = SubAgencia::where('idAgencia', $agencia->idAgencia)
                    ->where('nombre_oficina', $nombreSucursal)
                    ->whereNotIn('idSubAgencia', $idsVistos ?: [0])
                    ->first();
            }

            if (!$subAgencia) {
                $subAgencia = SubAgencia::create([
                    'idAgencia' => $agencia->idAgencia,
                    'idDestino' => $destino->idDestino,
                    'nombre_oficina' => $nombreSucursal,
                    'direccion' => substr($direccion, 0, 255),
                    'telefono' => '',
                    'estado' => 1
                ]);
                $idsVistos[] = $subAgencia->idSubAgencia;
                $nuevas++;
                continue;
            }

            $subAgencia->fill([
                'idDestino' => $destino->idDestino,
                'nombre_oficina' => $nombreSucursal,
                'direccion' => substr($direccion, 0, 255),
                'estado' => 1
            ]);
            $idsVistos[] = $subAgencia->idSubAgencia;

            if (!$subAgencia->isDirty()) {
                $sinCambios++;
                continue;
            }

            $cambios = array_keys($subAgencia->getDirty());
            if ($subAgencia->isDirty('idDestino')) {
                $reubicadas++;
            }
            $detalleCambios[] = "#{$subAgencia->idSubAgencia} {$nombreSucursal}: " . implode(', ', $cambios);
            $subAgencia->save();
            $actualizadas++;
        }

        // Desactivar solo las sub-agencias que ya no figuran en Shalom
        $desactivadas = SubAgencia::where('idAgencia', $agencia->idAgencia)
            ->where('estado', 1)
            ->whereNotIn('idSubAgencia', $idsVistos ?: [0])
            ->update(['estado' => 0]);

        $this->info("\n¡Sincronización SHALOM Completada!");
        $this->info("- Nuevas Sub-Agencias: {$nuevas}");
        $this->info("- Con cambios reales: {$actualizadas} (reubicadas de destino: {$reubicadas})");
        $this->info("- Sin cambios: {$sinCambios}");
        $this->info("- Desactivadas (ya no existen en Shalom): {$desactivadas}");
        $this->info("- Omitidas: {$omitidas}");

        if ($this->getOutput()->isVerbose()) {
            foreach ($detalleCambios as $linea) {
                $this->line("  * {$linea}");
            }
        }
        foreach ($detalleOmitidas as $linea) {
            $this->warn("  ! Omitida: {$linea}");
        }
    }
}
