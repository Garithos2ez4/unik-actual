<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\MercadoLibreApiService;

class MercadoLibreScraperV2Service
{
    protected string $baseUrl = 'https://api.mercadolibre.com';
    protected MercadoLibreApiService $apiService;
    /**
     * V2: Busca productos en todo Mercado Libre usando la API pública de búsqueda.
     * Separa "tus publicaciones" de "competencia" y calcula diferencias reales.
     */
    public function __construct(MercadoLibreApiService $apiService)
    {
        $this->apiService = $apiService;
    }
    public function searchAndCompare(string $sellerId, string $search): array
    {
        if (empty(trim($search))) {
            return [
                'success' => false,
                'message' => 'Debes ingresar un término de búsqueda para la V2.'
            ];
        }

        set_time_limit(60);

        try {
            // 1. Apuntar a la URL pública de ML en lugar de la API
            $terminoBusqueda = urlencode(trim($search));

            // Si la búsqueda contiene "impresora", "laptop" o "monitor", buscamos por Mayor Precio
            // para evitar que los 50 primeros resultados sean repuestos/accesorios baratos.
            // Luego el código local se encarga de ordenarlos del más barato al más caro.
            if (preg_match('/impresora|laptop|monitor/i', $search)) {
                $orden = "_OrderId_PRICE_DESC_NoIndex_True";
            } else {
                $orden = "_OrderId_PRICE_NoIndex_True";
            }

            $url = "https://listado.mercadolibre.com.pe/{$terminoBusqueda}{$orden}";

            $url = "https://listado.mercadolibre.com.pe/{$terminoBusqueda}{$orden}";

            // Integración de ScraperAPI
            $apiKey = config('services.scraperapi.key', env('SCRAPERAPI_KEY', '835ebca6b72fb95cbed8275b16ac4433'));
            $urlScraperApi = 'https://api.scraperapi.com/?' . http_build_query([
                'api_key' => $apiKey,
                'url'     => $url,
                'country_code' => 'pe', // Fuerza salida por IP de Perú
                'render'  => 'true', // Vital para resolver el JS Muralla de Mercado Libre
                'device_type' => 'mobile' // Último recurso: A veces la versión móvil no lanza reCAPTCHA
            ]);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlScraperApi);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Los proxies residenciales/headless tardan un poco más
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            // Ya no usamos el user agent Stealth de Googlebot porque ScraperAPI enmascara todo
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: text/html,application/xhtml+xml']);

            $htmlResponse = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            \Illuminate\Support\Facades\Storage::disk('local')->put('ml_debug_response.html', $htmlResponse);
            // Validación de bloqueo nivel de IP
            if ($httpcode !== 200 || empty($htmlResponse)) {
                return [
                    'success' => false,
                    'message' => "Bloqueo por IP detectado (HTTP {$httpcode}). ML cortó la conexión.",
                    'mis_productos' => [],
                    'top_competidores' => []
                ];
            }

            // Validación de DataDome (Captcha)
            if (stripos($htmlResponse, 'data-captcha') !== false || stripos($htmlResponse, 'Verifica que eres humano') !== false) {
                return [
                    'success' => false,
                    'message' => "DataDome nos interceptó y lanzó un Captcha. La IP del servidor está bajo vigilancia.",
                    'mis_productos' => [],
                    'top_competidores' => []
                ];
            }

            // 2. Extraer el JSON oculto mediante Regex (Técnica SSR)
            // Buscamos la variable window.__PRELOADED_STATE__ = { ... };
            // 2. Extraer el JSON oculto mediante Regex (Técnica SSR)
            $results = [];
            if (preg_match('/window\.__PRELOADED_STATE__\s*=\s*(\{.*?\});/s', $htmlResponse, $matches)) {
                $data = json_decode($matches[1], true);
                $results = $data['initialState']['results'] ?? [];
            }

            // ¡NUEVO!: FALLBACK A PARSEO DOM (Scraping Clásico)
            if (empty($results)) {
                $dom = new \DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML($htmlResponse);
                libxml_clear_errors();

                $xpath = new \DOMXPath($dom);

                // Buscar los contenedores de la lista de productos
                $nodosProductos = $xpath->query('//li[contains(@class, "ui-search-layout__item")]');

                foreach ($nodosProductos as $nodo) {
                    // El título ahora suele estar en un <a> o <h2> con clase poly-component__title
                    $tituloNodo = $xpath->query('.//*[contains(@class, "poly-component__title")]', $nodo);
                    $titulo = $tituloNodo->length > 0 ? trim($tituloNodo->item(0)->textContent) : '';

                    // El precio ahora está anidado en poly-price__current
                    $precioNodo = $xpath->query('.//div[contains(@class, "poly-price__current")]//span[contains(@class, "andes-money-amount__fraction")]', $nodo);
                    if ($precioNodo->length === 0) {
                        // Fallback a versión anterior
                        $precioNodo = $xpath->query('.//span[contains(@class, "andes-money-amount__fraction")]', $nodo);
                    }
                    $precio = $precioNodo->length > 0 ? floatval(str_replace(['.', ','], '', $precioNodo->item(0)->textContent)) : 0;

                    // El link
                    $linkNodo = $xpath->query('.//a[contains(@class, "poly-component__title") or contains(@class, "ui-search-link")]', $nodo);
                    $link = $linkNodo->length > 0 ? $linkNodo->item(0)->getAttribute('href') : '';

                    // Extraer el ID de ML desde el link (Ej: MPE123456789)
                    $itemId = '';
                    if (preg_match('/MPE-?(\d+)/', $link, $idMatches)) {
                        $itemId = 'MPE' . $idMatches[1];
                    }

                    // Intentar sacar el vendedor (Nueva clase poly-component__seller)
                    $vendedor = 'Competencia';
                    $vendedorNodo = $xpath->query('.//*[contains(@class, "poly-component__seller") or contains(@class, "ui-search-official-store-label")]', $nodo);
                    if ($vendedorNodo->length > 0) {
                        $vendedor = trim(str_ireplace('por ', '', $vendedorNodo->item(0)->textContent));
                    }

                    // Extraer Envío Gratis buscando la palabra en el texto del nodo
                    $freeShipping = stripos($nodo->textContent, 'gratis') !== false;

                    if ($titulo && $precio > 0) {
                        // Armamos el array con la misma estructura que esperaba el JSON original
                        $results[] = [
                            'id' => $itemId,
                            'title' => $titulo,
                            'price' => $precio,
                            'original_price' => $precio, // Simplificado
                            'permalink' => $link,
                            'thumbnail' => '', // Las imágenes suelen cargar por lazy-load, la omitimos
                            'condition' => 'new',
                            'shipping' => ['free_shipping' => $freeShipping],
                            'seller' => [
                                'id' => '', // No lo sabemos desde el HTML público
                                'nickname' => $vendedor
                            ]
                        ];
                    }
                }
            }

            if (empty($results)) {
                // Extraemos un snippet del HTML devuelto para saber qué está respondiendo ML a la IP de producción
                $snippet = substr(trim(preg_replace('/\s+/', ' ', strip_tags($htmlResponse))), 0, 150);
                return [
                    'success' => false,
                    'message' => "Pasamos el bloqueo HTTP 200, pero no hay productos. Respuesta de ML: '{$snippet}...'. Revisa storage/app/ml_debug_response.html en tu servidor de producción para ver el HTML completo.",
                    'mis_productos' => [],
                    'top_competidores' => []
                ];
            }
            // 3. Procesar los resultados (Reciclamos la misma lógica que ya tenías)
            $misProductos = [];
            $competencia = [];

            foreach ($results as $item) {
                $itemSellerId = $item['seller']['id'] ?? '';
                $price = $item['price'] ?? 0;
                $originalPrice = $item['original_price'] ?? $price;
                $title = $item['title'] ?? 'Sin título';
                $itemId = $item['id'] ?? '';
                $permalink = $item['permalink'] ?? '';
                $thumbnail = $item['thumbnail'] ?? '';
                $condition = $item['condition'] ?? 'new';

                // Formateamos para tu frontend
                $producto = [
                    'item_id' => $itemId,
                    'titulo' => $title,
                    'precio' => $price,
                    'precio_original' => $originalPrice,
                    'permalink' => $permalink,
                    'thumbnail' => $thumbnail,
                    'condition' => $condition,
                    'free_shipping' => $item['shipping']['free_shipping'] ?? false,
                    'seller_nickname' => $item['seller']['nickname'] ?? 'Desconocido',
                    'seller_id' => (string) $itemSellerId,
                ];

                if ((string) $itemSellerId === (string) $sellerId) {
                    $misProductos[] = $producto;
                } else {
                    $competencia[] = $producto;
                }
            }

            // Calcular el más barato de la competencia
            $precioMasBajoCompetencia = 0;
            $competidorMasBarato = null;
            if (!empty($competencia)) {
                usort($competencia, function ($a, $b) use ($search) {
                    $hasKeywordA = 0;
                    $hasKeywordB = 0;
                    $penaltyA = 0;
                    $penaltyB = 0;

                    // Palabras negativas comunes que indican que es un accesorio/repuesto
                    $negativeWords = ['chip', 'caja', 'mantenimiento', 'reset', 'rodillo', 'cabezal', 'cable', 'funda', 'cargador', 'soporte', 'brazo', 'cartucho', 'botella', 'bolsa'];

                    foreach ($negativeWords as $word) {
                        if (stripos($a['titulo'], $word) !== false) $penaltyA = 1;
                        if (stripos($b['titulo'], $word) !== false) $penaltyB = 1;
                    }

                    // Extraer la primera palabra de la búsqueda (ej: "impresora")
                    $keywords = explode(' ', trim($search));
                    $mainKeyword = !empty($keywords) ? strtolower($keywords[0]) : '';

                    if ($mainKeyword) {
                        $hasKeywordA = stripos($a['titulo'], $mainKeyword) !== false ? 1 : 0;
                        $hasKeywordB = stripos($b['titulo'], $mainKeyword) !== false ? 1 : 0;
                    }

                    // Penalizar "tinta" SOLO si el producto NO contiene la palabra principal (ej. impresora)
                    if (stripos($a['titulo'], 'tinta') !== false && !$hasKeywordA) $penaltyA = 1;
                    if (stripos($b['titulo'], 'tinta') !== false && !$hasKeywordB) $penaltyB = 1;

                    // 1. Prioridad extrema: Penalizar accesorios mandándolos al final
                    if ($penaltyA !== $penaltyB) {
                        return $penaltyA <=> $penaltyB; // El que tiene penalización (1) va después del que no (0)
                    }

                    // 2. Priorizar el que tiene la palabra clave en el título
                    if ($hasKeywordA !== $hasKeywordB) {
                        return $hasKeywordB <=> $hasKeywordA;
                    }

                    // 3. Luego, ordenar por precio ascendente
                    return $a['precio'] <=> $b['precio'];
                });
                $precioMasBajoCompetencia = $competencia[0]['precio'];
                $competidorMasBarato = $competencia[0];
            }

            // Lógica de diferencias y estados
            $productosFinales = [];
            foreach ($misProductos as $prod) {
                $diferencia = 0;
                $status = 'sin_competencia';
                $statusLabel = 'Sin competencia encontrada';
                $statusColor = 'secondary';

                if ($precioMasBajoCompetencia > 0) {
                    $diferencia = round((($prod['precio'] - $precioMasBajoCompetencia) / $precioMasBajoCompetencia) * 100, 1);

                    if ($prod['precio'] <= $precioMasBajoCompetencia) {
                        $status = 'eres_el_mas_barato';
                        $statusLabel = 'Eres el más BARATO';
                        $statusColor = 'success';
                    } elseif ($diferencia <= 5) {
                        $status = 'precio_competitivo';
                        $statusLabel = 'Precio COMPETITIVO';
                        $statusColor = 'info';
                    } elseif ($diferencia <= 15) {
                        $status = 'precio_alto';
                        $statusLabel = 'Precio ALTO';
                        $statusColor = 'warning';
                    } else {
                        $status = 'precio_muy_alto';
                        $statusLabel = 'Precio MUY ALTO';
                        $statusColor = 'danger';
                    }
                }

                $prod['precio_mas_bajo'] = $precioMasBajoCompetencia;
                $prod['competidor_mas_barato'] = $competidorMasBarato ? $competidorMasBarato['seller_nickname'] : '-';
                $prod['diferencia_porcentaje'] = $diferencia;
                $prod['status'] = $status;
                $prod['status_label'] = $statusLabel;
                $prod['status_color'] = $statusColor;
                $prod['competidores'] = count($competencia);

                $productosFinales[] = $prod;
            }

            usort($productosFinales, fn($a, $b) => $b['diferencia_porcentaje'] <=> $a['diferencia_porcentaje']);

            // Extraer el total real de resultados de la cabecera
            $totalResultados = count($results); // Fallback
            $qtyNode = $xpath->query('//span[contains(@class, "ui-search-search-result__quantity-results")]');
            if ($qtyNode->length > 0) {
                if (preg_match('/([\d\.,]+)/', $qtyNode->item(0)->textContent, $m)) {
                    $totalResultados = (int)str_replace(['.', ','], '', $m[1]);
                }
            }

            return [
                'success' => true,
                'total' => $totalResultados,
                'mostrando' => count($productosFinales),
                'mis_productos' => $productosFinales,
                'top_competidores' => $competencia,
                'precio_mas_bajo_mercado' => $precioMasBajoCompetencia,
                'busqueda' => $search,
            ];
        } catch (\Exception $e) {
            Log::error("Error en scraper V2 ML (Stealth SSR): " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error crítico al extraer datos web: ' . $e->getMessage()
            ];
        }
    }
}
