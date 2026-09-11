<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class RipleyScraperService
{
    /**
     * Busca un modelo en Ripley y extrae los precios vía SSR (Next.js).
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

            // 1. Apuntamos a la vista HTML pública, no a la API privada
            $url = 'https://simple.ripley.com.pe/search/' . $terminoBusqueda;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_ENCODING, '');

            // 2. Camuflaje táctico de Googlebot (Evasión de WAF)
            curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: es-PE,es;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: none',
                'Sec-Fetch-User: ?1'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

            $htmlResponse = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpcode !== 200 || empty($htmlResponse)) {
                return [
                    'success' => false,
                    'message' => "Ripley ha bloqueado la solicitud a nivel de red (HTTP {$httpcode}). Error: {$curlError}.",
                    'productos' => []
                ];
            }

            // 3. Extracción del estado de Next.js inyectado en el HTML
            $productosExtraidos = [];
            if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $htmlResponse, $matches)) {
                $data = json_decode($matches[1], true);

                // En la arquitectura Next.js de Ripley, los productos suelen estar aquí:
                $productosExtraidos = $data['props']['pageProps']['products'] ?? [];
            }

            if (empty($productosExtraidos)) {
                return [
                    'success' => false,
                    'message' => 'Se accedió a Ripley, pero no se encontró la data de productos (posible cambio de estructura Next.js o sin resultados).',
                    'productos' => []
                ];
            }

            // 4. Tu lógica original de filtrado y mapeo de datos (Intacta)
            $productos = [];
            foreach ($productosExtraidos as $item) {
                $title = $item['name'] ?? 'Producto sin título';

                if (!empty($categoriaLocal)) {
                    $catLower = strtolower($categoriaLocal);
                    if ($catLower === 'laptops' && (stripos($title, 'mochila') !== false || stripos($title, 'funda') !== false || stripos($title, 'cargador') !== false)) {
                        continue;
                    }
                    if ($catLower === 'monitores' && (stripos($title, 'cable') !== false || stripos($title, 'brazo') !== false || stripos($title, 'soporte') !== false)) {
                        continue;
                    }
                }

                $link = 'https://simple.ripley.com.pe' . ($item['url'] ?? '');
                $seller = $item['seller']['name'] ?? 'Ripley Retail';
                if (empty($seller)) {
                    $seller = 'Ripley Retail';
                }

                $prices = $item['prices'] ?? [];
                $priceList = $prices['listPrice'] ?? 0;
                $priceOffer = $prices['offerPrice'] ?? 0;
                $priceCard = $prices['cardPrice'] ?? 0;

                $precioActual = 0;
                if ($priceCard > 0) $precioActual = $priceCard;
                elseif ($priceOffer > 0) $precioActual = $priceOffer;
                else $precioActual = $priceList;

                if ($precioActual <= 0) continue;

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

            usort($productos, function ($a, $b) {
                return $a['price'] <=> $b['price'];
            });

            return [
                'success' => true,
                'total' => count($productos),
                'mostrando' => min(count($productos), 10),
                'productos' => $productos,
                'debug_url' => $url
            ];
        } catch (\Exception $e) {
            Log::error("Error en Ripley Scraper SSR: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al analizar los resultados de Ripley: ' . $e->getMessage(),
                'productos' => []
            ];
        }
    }
}
