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

        foreach ($warehouses as $warehouse) {
            $response = Http::withoutVerifying()
                ->withBasicAuth($this->username, $this->password)
                ->get($url, [
                    'category' => 'Todos',
                    'q' => $keyword,
                    'warehouse' => $warehouse,
                    'stock' => 'available', // Solo en stock
                    'sort' => $sort,
                    'page' => $page
                ]);

            if (!$response->successful()) {
                \Log::error("Deltron Scraper Error: HTTP " . $response->status() . " for keyword " . $keyword . " in warehouse " . $warehouse);
                continue;
            }

            $html = $response->body();
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
                                'mini_codigo' => $miniCodigo
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning("Deltron Scraper: Error parseando un nodo - " . $e->getMessage());
                }
            });
        }

        return array_values($scrapedProducts);
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
