<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShalomTarifaController extends Controller
{
    /**
     * Obtiene una sesión válida de pro.shalom.pe junto con cookies, XSRF-TOKEN, api-secret y response-key.
     */
    protected function getProShalomSession(): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://pro.shalom.pe/envia_ya/service_order/create');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36');
        $sessionResponse = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headersRaw = substr((string)$sessionResponse, 0, $headerSize);
        $htmlBody = substr((string)$sessionResponse, $headerSize);

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headersRaw, $matches);
        $cookies = [];
        $xsrfToken = '';
        foreach ($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
            if (strpos($item, 'XSRF-TOKEN=') === 0) {
                $xsrfToken = substr($item, 11);
            }
        }

        $cookieString = '';
        foreach ($cookies as $k => $v) {
            if (!in_array(strtolower($k), ['expires', 'max-age', 'path', 'httponly', 'secure', 'samesite'])) {
                $cookieString .= $k . '=' . $v . '; ';
            }
        }

        $apiSecret = '90d8df9a6868ab19d4784cd55a1687f160f9e5cf0d93e6dff23e3d0ab3bfdc6b';
        if (preg_match('/<meta[^>]+name=["\']api-secret["\'][^>]+content=["\']([^"\']+)["\']/i', $htmlBody, $mSecret)) {
            $apiSecret = $mSecret[1];
        }

        $responseKey = 'F9XKLGh8dsvoXASZnYwU9qX2OCVM5nkVFJDkMxyjNEY=';
        if (preg_match('/<meta[^>]+name=["\']response-key["\'][^>]+content=["\']([^"\']+)["\']/i', $htmlBody, $mKey)) {
            $responseKey = $mKey[1];
        }

        return [
            'cookieString' => $cookieString,
            'xsrfToken' => urldecode($xsrfToken),
            'apiSecret' => $apiSecret,
            'responseKey' => $responseKey,
        ];
    }

    /**
     * Genera las cabeceras firmadas (HMAC-SHA256) requeridas por pro.shalom.pe
     */
    protected function buildSignedHeaders(string $method, string $path, array $session, array $extraHeaders = []): array
    {
        $timestamp = (string) time();
        $nonce = substr(base_convert((string) mt_rand(), 10, 36) . base_convert((string) mt_rand(), 10, 36), 0, 8);
        $cleanPath = ltrim($path, '/');
        $payload = strtoupper($method) . $cleanPath . $timestamp . $nonce;
        $signature = hash_hmac('sha256', $payload, $session['apiSecret']);

        $headers = array_merge([
            'Accept: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
            'Origin: https://pro.shalom.pe',
            'Referer: https://pro.shalom.pe/envia_ya/service_order/create',
            'X-Requested-With: XMLHttpRequest',
            'X-API-KEY: pk_over_5pg91gO6CSgmT627cf2sC8B7dqjxcLFQhW7HhnitKq3',
            'X-TIMESTAMP: ' . $timestamp,
            'X-NONCE: ' . $nonce,
            'X-SIGNATURE: ' . $signature,
        ], $extraHeaders);

        if (!empty($session['xsrfToken'])) {
            $headers[] = 'X-XSRF-TOKEN: ' . $session['xsrfToken'];
        }

        return $headers;
    }

    /**
     * Desencripta la respuesta de pro.shalom.pe si viene con encrypted: true
     */
    protected function decodeProShalomResponse(string $rawBody, string $responseKeyB64): ?array
    {
        $json = json_decode($rawBody, true);
        if (!is_array($json)) {
            return null;
        }

        if (!empty($json['encrypted']) && !empty($json['data']) && is_string($json['data'])) {
            $raw = base64_decode($json['data'], true);
            $key = base64_decode($responseKeyB64, true);
            if ($raw !== false && $key !== false && strlen($raw) > 16) {
                $iv = substr($raw, 0, 16);
                $cipher = substr($raw, 16);
                $decrypted = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
                if ($decrypted !== false) {
                    $decJson = json_decode($decrypted, true);
                    if (is_array($decJson)) {
                        return $decJson;
                    }
                }
            }
        }

        return $json;
    }

    /**
     * Obtiene la lista de terminales desde el JSON de respaldo
     */
    public function getTerminals()
    {
        $path = storage_path('app/agencias_shalom.json');
        if (!file_exists($path)) {
            return response()->json(['success' => false, 'message' => 'Archivo agencias_shalom.json no encontrado. Ejecuta sync:shalom primero.']);
        }

        $json = json_decode(file_get_contents($path), true);
        if (isset($json['data']) && is_array($json['data'])) {
            $json = $json['data'];
        }

        $terminals = [];
        foreach ($json as $item) {
            if (isset($item['ter_id']) && isset($item['nombre'])) {
                $terminals[] = [
                    'id' => $item['ter_id'],
                    'nombre' => $item['nombre']
                ];
            } elseif (isset($item['ter_id']) && isset($item['lugar'])) {
                $terminals[] = [
                    'id' => $item['ter_id'],
                    'nombre' => $item['lugar']
                ];
            }
        }

        usort($terminals, function ($a, $b) {
            return strcmp($a['nombre'], $b['nombre']);
        });

        return response()->json(['success' => true, 'data' => $terminals]);
    }

    /**
     * Calcula la tarifa consultando la API de Shalom
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'origin' => 'required|numeric',
            'destiny' => 'required|numeric',
            'weight' => 'required|numeric'
        ]);

        $origin = $request->input('origin');
        $destiny = $request->input('destiny');
        $weight = $request->input('weight');
        $length = $request->input('length', 0);
        $width = $request->input('width', 0);
        $height = $request->input('height', 0);

        try {
            $session = $this->getProShalomSession();
            $path = '/envia_ya/tariff/calculate';
            $headers = $this->buildSignedHeaders('POST', $path, $session, [
                'Content-Type: application/json',
            ]);

            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_URL, 'https://pro.shalom.pe' . $path);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch2, CURLOPT_POST, 1);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode([
                'origin' => (int)$origin,
                'destiny' => (int)$destiny,
                'width' => (float)$width,
                'height' => (float)$height,
                'length' => (float)$length,
                'weight' => (float)$weight
            ]));
            curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch2, CURLOPT_COOKIE, $session['cookieString']);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);

            $res2 = curl_exec($ch2);
            $httpcode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);

            $responseJson = $this->decodeProShalomResponse((string)$res2, $session['responseKey']);

            if ($httpcode == 200 && isset($responseJson['success']) && $responseJson['success'] == true) {
                return response()->json([
                    'success' => true,
                    'price' => $responseJson['data']['price'],
                    'tariff' => $responseJson['data']['tariff'] ?? null
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al cotizar. Shalom devolvió: ' . ($responseJson['message'] ?? 'Error desconocido')
                ]);
            }

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Excepción: ' . $e->getMessage()]);
        }
    }

    /**
     * Obtiene las restricciones de categorías por terminal desde Shalom
     * Cachea el resultado en un archivo JSON local por 24 horas
     */
    public function getRestricciones()
    {
        $cachePath = storage_path('app/shalom_restricciones.json');
        $cacheMaxAge = 86400; // 24 horas

        if (file_exists($cachePath) && (time() - filemtime($cachePath)) < $cacheMaxAge) {
            $cached = json_decode(file_get_contents($cachePath), true);
            if ($cached && isset($cached['data'])) {
                return response()->json(['success' => true, 'data' => $cached['data']]);
            }
        }

        try {
            $session = $this->getProShalomSession();
            $path = '/envia_ya/service_order/restricciones-categorias';
            $headers = $this->buildSignedHeaders('GET', $path, $session);

            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_URL, 'https://pro.shalom.pe' . $path);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch2, CURLOPT_COOKIE, $session['cookieString']);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);

            $res2 = curl_exec($ch2);
            $httpcode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);

            if ($httpcode == 200) {
                $json = $this->decodeProShalomResponse((string)$res2, $session['responseKey']);

                if (isset($json['valor']) && $json['valor'] === true && isset($json['data'])) {
                    file_put_contents($cachePath, json_encode([
                        'data' => $json['data'],
                        'cached_at' => now()->toDateTimeString()
                    ]));

                    return response()->json(['success' => true, 'data' => $json['data']]);
                }
            }

            if (file_exists($cachePath)) {
                $cached = json_decode(file_get_contents($cachePath), true);
                if ($cached && isset($cached['data'])) {
                    return response()->json(['success' => true, 'data' => $cached['data'], 'from_cache' => true]);
                }
            }

            return response()->json(['success' => false, 'message' => 'Error HTTP ' . $httpcode . ' al consultar restricciones, y no hay caché disponible.']);

        } catch (\Exception $e) {
            if (file_exists($cachePath)) {
                $cached = json_decode(file_get_contents($cachePath), true);
                if ($cached && isset($cached['data'])) {
                    return response()->json(['success' => true, 'data' => $cached['data'], 'from_cache' => true]);
                }
            }

            return response()->json(['success' => false, 'message' => 'Error al obtener restricciones: ' . $e->getMessage()]);
        }
    }
}
