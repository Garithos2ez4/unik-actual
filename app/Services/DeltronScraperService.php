<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Catalogo\Producto;
use App\Models\Inventario\Inventario_Proveedor;

class DeltronScraperService
{
    protected $username = '20606545470';
    protected $password = '60654547';
    protected $idProveedor = 1; // ID de Deltron en Preveedor

    /**
     * Busca productos en Deltron y devuelve los datos extraídos
     */
    public function scrapeByKeyword($keyword, $sort = 'stock_desc', $page = 1)
    {
        $url = "https://www.deltron.com.pe/modulos/productos/items/buscadorWeb.php";
        $warehouses = ['000', '005', '007']; // Lima Principal, Surquillo, Compuplaza
        $scrapedProducts = [];

        $cookieStr = $this->getCookieString();

        foreach ($warehouses as $warehouse) {
            try {
                $query = http_build_query([
                    'category' => 'Todos',
                    'q' => $keyword,
                    'warehouse' => $warehouse,
                    'stock' => 'available', // Solo en stock
                    'sort' => $sort,
                    'page' => $page
                ]);
                $fullUrl = $url . '?' . $query;

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $fullUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
                if ($cookieStr) {
                    curl_setopt($ch, CURLOPT_COOKIE, $cookieStr);
                }
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);

                $html = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpcode !== 200) {
                    \Log::error("Deltron Scraper Error: HTTP " . $httpcode . " for keyword " . $keyword . " in warehouse " . $warehouse);
                    continue;
                }
            } catch (\Exception $e) {
                \Log::error("Deltron Scraper Exception in warehouse $warehouse: " . $e->getMessage());
                continue;
            }
            $crawler = new Crawler($html);

            // Cada producto está dentro de un elemento con la clase .product-item
            $crawler->filter('.product-item')->each(function (Crawler $node) use (&$scrapedProducts) {
                try {
                    // Título
                    $titulo = null;
                    if ($node->filter('.product-title')->count() > 0) {
                        $titulo = trim($node->filter('.product-title')->first()->text());
                    }

                    // Código Deltron (Modelo)
                    $codigoDeltron = null;
                    if ($node->filter('.chip-primary')->count() > 0) {
                        $codigoDeltron = trim($node->filter('.chip-primary')->text());
                    }

                    // Stock
                    $stock = 0;
                    if ($node->filter('.chip-stock')->count() > 0) {
                        $stockText = trim($node->filter('.chip-stock')->text());
                        $stock = (int) filter_var($stockText, FILTER_SANITIZE_NUMBER_INT);
                    }

                    // Part Number
                    $partNumber = null;
                    if ($node->filter('.chip-secondary')->count() > 0) {
                        $textBlock = trim($node->filter('.chip-secondary')->text());
                        if (preg_match('/Part Number:\s*([^\n\r]+)/i', $textBlock, $matches)) {
                            $partNumber = trim($matches[1]);
                        } else {
                            // Si no tiene la etiqueta "Part Number:", asume que todo el texto lo es
                            $partNumber = str_replace('Part Number:', '', $textBlock);
                            $partNumber = trim($partNumber);
                        }
                    }

                    // Mini Código
                    $miniCodigo = null;
                    if ($node->filter('.chip-muted')->count() > 0) {
                        $textBlock = trim($node->filter('.chip-muted')->text());
                        if (preg_match('/Mini Código:\s*([^\n\r]+)/i', $textBlock, $matches)) {
                            $miniCodigo = trim($matches[1]);
                        } else {
                            $miniCodigo = str_replace('Mini Código:', '', $textBlock);
                            $miniCodigo = trim($miniCodigo);
                        }
                    }

                    // Precio
                    $precio = null;
                    if ($node->filter('.product-price')->count() > 0) {
                        $precioText = trim($node->filter('.product-price')->text());
                        $precioNumerico = preg_replace('/[^0-9.]/', '', $precioText);
                        if (is_numeric($precioNumerico)) {
                            $precio = (float) $precioNumerico;
                        }
                    }

                    if ($codigoDeltron && $partNumber) {
                        if (isset($scrapedProducts[$partNumber])) {
                            // Si ya existe, sumamos el stock
                            $scrapedProducts[$partNumber]['stock'] += $stock;
                        } else {
                            // Si no existe, lo agregamos
                            $scrapedProducts[$partNumber] = [
                                'titulo' => $titulo,
                                'modelo' => $codigoDeltron,
                                'stock' => $stock,
                                'part_number' => $partNumber,
                                'mini_codigo' => $miniCodigo,
                                'precio' => $precio
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning("Deltron Scraper: Error parseando un nodo - " . $e->getMessage());
                }
            });
        }
        $scrapedProductsList = array_values($scrapedProducts);

        // Ordenar por stock ascendente
        usort($scrapedProductsList, function ($a, $b) {
            return $b['stock'] <=> $a['stock'];
        });

        return $scrapedProductsList;
    }

    /**
     * Inicia sesión en Deltron como humano, extrae las cookies y las guarda temporalmente.
     */
    private function getCookieString()
    {
        $cachePath = storage_path('app/deltron_cookie.txt');

        // Cachear cookie por 30 minutos (1800 segundos) para no hacer login en cada recarga
        if (file_exists($cachePath) && (time() - filemtime($cachePath)) < 1800) {
            return file_get_contents($cachePath);
        }

        $userpwd = $this->username . ':' . $this->password;

        // 1. Obtener sesión inicial
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.deltron.com.pe/login.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        $sessionResponse = curl_exec($ch);
        curl_close($ch);

        // Extraer Cookies
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $sessionResponse, $matches);
        $cookies = [];
        foreach ($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }

        $cookieString = '';
        foreach ($cookies as $k => $v) {
            if (!in_array(strtolower($k), ['expires', 'max-age', 'path', 'httponly', 'secure', 'samesite'])) {
                $cookieString .= $k . '=' . $v . '; ';
            }
        }

        // 2. Hacer POST al login
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, 'https://www.deltron.com.pe/login.php');
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch2, CURLOPT_HEADER, 1);
        curl_setopt($ch2, CURLOPT_POST, 1);
        curl_setopt($ch2, CURLOPT_USERPWD, $userpwd);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query([
            'action' => 'login',
            'customer' => $this->username,
            'login_username' => $this->username,
            'login_password' => $this->password
        ]));

        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'Origin: https://www.deltron.com.pe',
            'Referer: https://www.deltron.com.pe/login.php',
        ];
        curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch2, CURLOPT_COOKIE, $cookieString);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);

        $res2 = curl_exec($ch2);
        curl_close($ch2);

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $res2, $matches2);
        foreach ($matches2[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }

        $cookieStringFinal = '';
        foreach ($cookies as $k => $v) {
            if (!in_array(strtolower($k), ['expires', 'max-age', 'path', 'httponly', 'secure', 'samesite'])) {
                $cookieStringFinal .= $k . '=' . $v . '; ';
            }
        }

        file_put_contents($cachePath, $cookieStringFinal);
        return $cookieStringFinal;
    }

    /**
     * Sincroniza el array de productos parseados con la base de datos
     */
    public function syncInventario($scrapedProducts)
    {
        $updatedCount = 0;
        $detallesActualizados = [];
        $recomendados = [];

        foreach ($scrapedProducts as $data) {
            // Buscamos el producto en nuestro catálogo SOLO por partNumber
            if (empty($data['part_number'])) {
                continue;
            }

            $producto = Producto::where('partNumber', $data['part_number'])->first();

            if ($producto) {
                // Actualizar o crear registro en Inventario_Proveedor
                Inventario_Proveedor::updateOrCreate(
                    [
                        'idProducto' => $producto->idProducto
                    ],
                    [
                        'idProveedor' => $this->idProveedor,
                        'stock' => $data['stock'],
                        'estado' => 1
                    ]
                );

                $detallesActualizados[] = [
                    'part_number' => $data['part_number']
                ];
                $updatedCount++;
            } else {
                // Si el producto NO existe en el catálogo, pero tiene buen stock, lo recomendamos
                if ($data['stock'] > 100) {
                    $recomendados[] = [
                        'titulo' => $data['titulo'],
                        'part_number' => $data['part_number'],
                        'modelo' => $data['modelo'],
                        'stock' => $data['stock']
                    ];
                }
            }
        }

        return [
            'count' => $updatedCount,
            'detalles' => $detallesActualizados,
            'recomendados' => $recomendados
        ];
    }
}
