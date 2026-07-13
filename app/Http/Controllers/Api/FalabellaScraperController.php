<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FalabellaScraperController extends Controller
{
    /**
     * Busca un modelo en Falabella y extrae el precio.
     * 
     * Nota Arquitectónica: A diferencia de Shalom que usa un token XSRF y una API REST, 
     * Falabella utiliza SSR con Next.js y está protegido por Cloudflare/Datadome. 
     * No hay un "inicio de sesión" simple para búsqueda, sino que extraemos los datos 
     * directamente del HTML inicial generado por el servidor (SSR).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verificarPrecio(Request $request)
    {
        $modelo = $request->input('modelo');

        if (!$modelo) {
            return response()->json(['success' => false, 'message' => 'El parámetro modelo es requerido']);
        }

        try {
            // URL de búsqueda de Falabella Perú
            $url = 'https://www.falabella.com.pe/falabella-pe/search?Ntt=' . urlencode($modelo);
            
            // Simular un navegador para mitigar bloqueos de Cloudflare/Datadome
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_ENCODING, ''); // Permite gzip, deflate
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: es-PE,es;q=0.9,en;q=0.8',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $html = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpcode !== 200 || !$html) {
                return response()->json([
                    'success' => false,
                    'message' => "Error de acceso a Falabella (HTTP $httpcode). Posible bloqueo de anti-bots."
                ]);
            }

            // Falabella guarda los resultados en una etiqueta script con id __NEXT_DATA__
            if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
                $data = json_decode($matches[1], true);
                
                $productosEncontrados = [];
                
                // Función recursiva para encontrar todos los nodos que parecen productos
                $buscarProductos = function($array) use (&$buscarProductos, &$productosEncontrados) {
                    if (!is_array($array)) return;
                    
                    if (isset($array['displayName']) && isset($array['prices'])) {
                        $productosEncontrados[] = $array;
                    }
                    
                    foreach ($array as $key => $value) {
                        if (is_array($value)) {
                            $buscarProductos($value);
                        }
                    }
                };
                
                if ($data) {
                    $buscarProductos($data);
                }

                $productos = [];
                
                if (count($productosEncontrados) > 0) {
                    foreach ($productosEncontrados as $item) {
                        $titulo = $item['displayName'];
                        $vendedor = $item['sellerName'] ?? 'FALABELLA';
                        $pricesArray = $item['prices'];
                        
                        $precioMinimo = null;
                        
                        if (is_array($pricesArray)) {
                            foreach ($pricesArray as $p) {
                                // Consideramos cualquier precio (normalPrice, internetPrice, cmrPrice, eventPrice)
                                if (isset($p['price'][0])) {
                                    $precio = (float) str_replace(',', '', $p['price'][0]);
                                    if ($precioMinimo === null || $precio < $precioMinimo) {
                                        $precioMinimo = $precio;
                                    }
                                }
                            }
                        }

                        if ($precioMinimo !== null) {
                            $productos[] = [
                                'titulo' => $titulo,
                                'vendedor' => $vendedor,
                                'precio_mas_bajo' => $precioMinimo
                            ];
                        }
                    }
                    
                    // Ordenar de menor a mayor precio
                    usort($productos, function($a, $b) {
                        return $a['precio_mas_bajo'] <=> $b['precio_mas_bajo'];
                    });
                    
                    $miTienda = $request->input('mi_tienda', ''); // Ej: GAMING POWER PERU
                    $soyElMasBarato = false;
                    $masBarato = null;
                    
                    if (count($productos) > 0) {
                        $masBarato = $productos[0];
                        if ($miTienda !== '' && stripos($masBarato['vendedor'], $miTienda) !== false) {
                            $soyElMasBarato = true;
                        }
                    }

                    return response()->json([
                        'success' => true,
                        'modelo' => $modelo,
                        'total_encontrados' => count($productos),
                        'mejor_precio' => $masBarato ? $masBarato['precio_mas_bajo'] : null,
                        'vendedor_mas_barato' => $masBarato ? $masBarato['vendedor'] : null,
                        'mi_tienda_es_mas_barata' => $miTienda !== '' ? $soyElMasBarato : null,
                        'productos' => $productos
                    ]);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'No se encontraron productos para el modelo: ' . $modelo,
                    'data' => []
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se encontró la estructura de datos (bloqueo por Captcha o cambio de diseño).'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al raspar Falabella: ' . $e->getMessage()
            ]);
        }
    }
}
