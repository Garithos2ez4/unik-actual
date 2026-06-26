<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ShalomTarifaController extends Controller
{
    /**
     * Obtiene la lista de terminales desde el JSON de respaldo
     */
    public function getTerminals()
    {
        $path = storage_path('app/shalom_agencias.json');
        if (!file_exists($path)) {
            return response()->json(['success' => false, 'message' => 'Archivo shalom_agencias.json no encontrado. Ejecuta sync:shalom primero.']);
        }

        $json = json_decode(file_get_contents($path), true);
        
        $terminals = [];
        foreach ($json as $item) {
            if (isset($item['ter_id']) && isset($item['nombre'])) {
                $terminals[] = [
                    'id' => $item['ter_id'],
                    'nombre' => $item['nombre']
                ];
            } elseif (isset($item['ter_id']) && isset($item['lugar'])) {
                // Fallback por si la propiedad se llama 'lugar'
                $terminals[] = [
                    'id' => $item['ter_id'],
                    'nombre' => $item['lugar']
                ];
            }
        }

        // Ordenar alfabéticamente
        usort($terminals, function($a, $b) {
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
            // 1. Obtener una nueva sesión de Shalom
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://pro.shalom.pe/envia_ya/service_order/create');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $sessionResponse = curl_exec($ch);
            curl_close($ch);

            // Extraer Cookies
            preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $sessionResponse, $matches);
            $cookies = array();
            $xsrfToken = '';
            foreach($matches[1] as $item) {
                parse_str($item, $cookie);
                $cookies = array_merge($cookies, $cookie);
                if (strpos($item, 'XSRF-TOKEN=') === 0) {
                    $xsrfToken = substr($item, 11);
                }
            }

            // Construir la cabecera de Cookie
            $cookieString = '';
            foreach ($cookies as $k => $v) {
                if($k != 'expires' && $k != 'Max-Age' && $k != 'path' && $k != 'httponly') {
                    $cookieString .= $k . '=' . $v . '; ';
                }
            }
            
            // Decodificar XSRF-TOKEN
            $decodedXsrf = urldecode($xsrfToken);

            // 2. Realizar el POST a la API de Calculate
            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_URL, 'https://pro.shalom.pe/envia_ya/tariff/calculate');
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
            
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Origin: https://pro.shalom.pe',
                'Referer: https://pro.shalom.pe/envia_ya/service_order/create',
                'X-Requested-With: XMLHttpRequest',
            ];
            
            if ($decodedXsrf) {
                $headers[] = 'X-XSRF-TOKEN: ' . $decodedXsrf;
            }

            curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch2, CURLOPT_COOKIE, $cookieString);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            
            $res2 = curl_exec($ch2);
            $httpcode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);

            $responseJson = json_decode($res2, true);

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

        // Intentar leer del caché
        if (file_exists($cachePath) && (time() - filemtime($cachePath)) < $cacheMaxAge) {
            $cached = json_decode(file_get_contents($cachePath), true);
            if ($cached && isset($cached['data'])) {
                return response()->json(['success' => true, 'data' => $cached['data']]);
            }
        }

        try {
            // 1. Obtener una nueva sesión de Shalom
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://pro.shalom.pe/envia_ya/service_order/create');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $sessionResponse = curl_exec($ch);
            curl_close($ch);

            // Extraer Cookies
            preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $sessionResponse, $matches);
            $cookies = array();
            $xsrfToken = '';
            foreach($matches[1] as $item) {
                parse_str($item, $cookie);
                $cookies = array_merge($cookies, $cookie);
                if (strpos($item, 'XSRF-TOKEN=') === 0) {
                    $xsrfToken = substr($item, 11);
                }
            }

            // Construir la cabecera de Cookie
            $cookieString = '';
            foreach ($cookies as $k => $v) {
                if($k != 'expires' && $k != 'Max-Age' && $k != 'path' && $k != 'httponly') {
                    $cookieString .= $k . '=' . $v . '; ';
                }
            }
            
            // Decodificar XSRF-TOKEN
            $decodedXsrf = urldecode($xsrfToken);

            // 2. Realizar el GET a la API de Restricciones
            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_URL, 'https://pro.shalom.pe/envia_ya/service_order/restricciones-categorias');
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
            
            $headers = [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer: https://pro.shalom.pe/envia_ya/service_order/create',
                'X-Requested-With: XMLHttpRequest',
            ];
            
            if ($decodedXsrf) {
                $headers[] = 'X-XSRF-TOKEN: ' . $decodedXsrf;
            }

            curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch2, CURLOPT_COOKIE, $cookieString);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            
            $res2 = curl_exec($ch2);
            $httpcode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);

            if ($httpcode == 200) {
                $json = json_decode($res2, true);

                if (isset($json['valor']) && $json['valor'] === true && isset($json['data'])) {
                    // Guardar en caché
                    file_put_contents($cachePath, json_encode([
                        'data' => $json['data'],
                        'cached_at' => now()->toDateTimeString()
                    ]));

                    return response()->json(['success' => true, 'data' => $json['data']]);
                }
            }

            // Fallback si la API falla (ej. error 401, timeout, cambio de seguridad en Shalom)
            if (file_exists($cachePath)) {
                $cached = json_decode(file_get_contents($cachePath), true);
                if ($cached && isset($cached['data'])) {
                    return response()->json(['success' => true, 'data' => $cached['data'], 'from_cache' => true]);
                }
            }

            return response()->json(['success' => false, 'message' => 'Error HTTP ' . $httpcode . ' al consultar restricciones, y no hay caché disponible.']);

        } catch (\Exception $e) {
            // Si falla la ejecución
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
