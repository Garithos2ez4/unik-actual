<?php

namespace App\Services;

class RipleyScraperService
{
    /**
     * Busca un modelo en Ripley y extrae los precios.
     * 
     * @param string $modelo
     * @param string $miTienda Nombre de tu tienda para identificar si eres el más barato
     * @param string $nombreProductoLocal Opcional.
     * @param string $categoriaLocal Opcional.
     * @param string $marcaLocal Opcional.
     * @return array
     */
    public function scrapePrices(string $modelo, string $miTienda = 'Unik Store', string $nombreProductoLocal = '', string $categoriaLocal = '', string $marcaLocal = '')
    {
        if (!$modelo) {
            return ['success' => false, 'message' => 'El parámetro modelo es requerido'];
        }

        // Recuperar el producto local si no se pasó el nombre
        if (empty($nombreProductoLocal)) {
            $prodLocal = \App\Models\Catalogo\Producto::with(['GrupoProducto'])
                ->where('modelo', $modelo)
                ->orWhere('modelo', 'LIKE', '%' . trim($modelo) . '%')
                ->first();
            if ($prodLocal) {
                $nombreProductoLocal = $prodLocal->nombreProducto;
                if (empty($categoriaLocal) && $prodLocal->GrupoProducto) {
                    $categoriaLocal = $prodLocal->GrupoProducto->nombreGrupo;
                }
            }
        }

        try {
            $terminoBusqueda = urlencode(strtoupper(trim($modelo)));
            $url = 'https://simple.ripley.com.pe/api/v2/products?byTerm=' . $terminoBusqueda . '&page=1';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json, text/plain, */*',
                'Accept-Language: es-PE,es;q=0.9,en;q=0.8',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Sec-Fetch-Dest: empty',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Site: same-origin'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            // Timeouts para no colgar el servidor
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

            $jsonResponse = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpcode !== 200 || !$jsonResponse) {
                \Illuminate\Support\Facades\Log::warning("Ripley Scraper - HTTP $httpcode. Error: $curlError. Intentando usar file_get_contents.");
                
                // Backup option if curl fails but server allows file_get_contents
                $context = stream_context_create([
                    "http" => [
                        "method" => "GET",
                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
                    ]
                ]);
                $jsonResponse = @file_get_contents($url, false, $context);
                
                if (!$jsonResponse) {
                    return [
                        'success' => false,
                        'message' => 'Ripley ha bloqueado la solicitud (Status: ' . $httpcode . '). Esto es común por su Firewall. Usa la sincronización oficial si es posible.',
                        'debug' => "HTTP Code: $httpcode. Curl Error: $curlError",
                        'productos' => []
                    ];
                }
            }

            $data = json_decode($jsonResponse, true);
            
            if (!$data || !isset($data['products'])) {
                return [
                    'success' => false,
                    'message' => 'No se pudo decodificar el JSON de Ripley o estructura inválida.',
                    'productos' => []
                ];
            }

            $productos = [];
            foreach ($data['products'] as $item) {
                // Validación básica de título
                $title = $item['name'] ?? 'Producto sin título';
                
                // Evitar accesorios si es necesario
                if (!empty($categoriaLocal)) {
                    $catLower = strtolower($categoriaLocal);
                    if ($catLower === 'laptops' && (stripos($title, 'mochila') !== false || stripos($title, 'funda') !== false || stripos($title, 'cargador') !== false)) {
                        continue; // Saltar porque es un accesorio
                    }
                    if ($catLower === 'monitores' && (stripos($title, 'cable') !== false || stripos($title, 'brazo') !== false || stripos($title, 'soporte') !== false)) {
                        continue; // Saltar accesorio
                    }
                }

                $link = 'https://simple.ripley.com.pe' . ($item['url'] ?? '');
                $seller = $item['seller']['name'] ?? 'Ripley';
                if ($seller === 'Ripley' || empty($seller)) {
                    $seller = 'Ripley Retail'; // Para distinguir de seller de marketplace
                }

                // Precios
                $prices = $item['prices'] ?? [];
                $priceList = $prices['listPrice'] ?? 0;
                $priceOffer = $prices['offerPrice'] ?? 0;
                $priceCard = $prices['cardPrice'] ?? 0;

                // Determinar el precio actual (el más bajo disponible)
                $precioActual = 0;
                if ($priceCard > 0) $precioActual = $priceCard;
                elseif ($priceOffer > 0) $precioActual = $priceOffer;
                else $precioActual = $priceList;

                if ($precioActual <= 0) continue; // Saltar si no tiene precio

                $image = '';
                if (!empty($item['images']) && isset($item['images'][0])) {
                    $image = strpos($item['images'][0], 'http') === 0 ? $item['images'][0] : 'https:' . $item['images'][0];
                }

                $productos[] = [
                    'title' => $title,
                    'price' => (float)$precioActual,
                    'price_original' => (float)$priceList,
                    'link' => $link,
                    'image' => $image,
                    'seller' => $seller,
                    'is_my_store' => (stripos($seller, $miTienda) !== false)
                ];
            }

            // Ordenar por precio ascendente
            usort($productos, function($a, $b) {
                return $a['price'] <=> $b['price'];
            });

            return [
                'success' => true,
                'total' => count($productos),
                'mostrando' => min(count($productos), 10), // Limitamos en frontend
                'productos' => $productos,
                'debug_url' => $url
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al analizar los resultados de Ripley: ' . $e->getMessage(),
                'productos' => []
            ];
        }
    }
}
