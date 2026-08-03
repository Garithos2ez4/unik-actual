<?php

namespace App\Services;

class FalabellaScraperService
{
    /**
     * Busca un modelo en Falabella y extrae los precios.
     * 
     * @param string $modelo
     * @param string $miTienda Nombre de tu tienda para identificar si eres el más barato
     * @param string $nombreProductoLocal Opcional. Se usa para filtrar resultados basura (accesorios)
     * @param string $categoriaLocal Opcional. El nombre del GrupoProducto (ej. Estabilizadores)
     * @param string $marcaLocal Opcional. Nombre de la marca para mejorar la búsqueda en Falabella
     * @return array
     */
    public function scrapePrices(string $modelo, string $miTienda = 'GAMING POWER PERU', string $nombreProductoLocal = '', string $categoriaLocal = '', string $marcaLocal = '')
    {
        if (!$modelo) {
            return ['success' => false, 'message' => 'El parámetro modelo es requerido'];
        }

        try {
            $terminoBusqueda = $marcaLocal !== '' ? $marcaLocal . ' ' . $modelo : $modelo;
            $url = 'https://www.falabella.com.pe/falabella-pe/search?Ntt=' . urlencode($terminoBusqueda);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_ENCODING, '');
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
                return [
                    'success' => false,
                    'message' => "Error de acceso a Falabella (HTTP $httpcode)."
                ];
            }

            if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
                $data = json_decode($matches[1], true);
                
                $productosEncontrados = [];
                
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
                    // Obtener palabras clave válidas (1era palabra del título, y singular de la categoría)
                    $palabrasValidas = [];
                    $palabrasClave = explode(' ', strtoupper(trim($nombreProductoLocal)));
                    $primeraPalabra = count($palabrasClave) > 0 ? preg_replace('/[^A-Z]/', '', $palabrasClave[0]) : '';
                    if (!in_array($primeraPalabra, ['', 'EL', 'LA', 'LOS', 'LAS', 'KIT', 'PACK'])) {
                        $palabrasValidas[] = $primeraPalabra;
                    } elseif (count($palabrasClave) > 1) {
                        $segundaPalabra = preg_replace('/[^A-Z]/', '', $palabrasClave[1]);
                        if (!in_array($segundaPalabra, ['', 'EL', 'LA', 'LOS', 'LAS', 'KIT', 'PACK'])) {
                            $palabrasValidas[] = $segundaPalabra;
                        }
                    }

                    if ($categoriaLocal !== '') {
                        $cat = strtoupper(trim($categoriaLocal));
                        // Singularizar (ej. IMPRESORAS -> IMPRESORA)
                        if (substr($cat, -2) === 'ES') {
                            $cat = substr($cat, 0, -2);
                        } elseif (substr($cat, -1) === 'S') {
                            $cat = substr($cat, 0, -1);
                        }
                        $palabrasValidas[] = preg_replace('/[^A-Z]/', '', $cat);
                    }

                    // Eliminar vacíos
                    $palabrasValidas = array_filter($palabrasValidas);

                    foreach ($productosEncontrados as $item) {
                        $titulo = $item['displayName'];
                        $vendedor = $item['sellerName'] ?? 'FALABELLA';
                        $pricesArray = $item['prices'];
                        
                        // Filtro 1: Debe coincidir AL MENOS UNA de las palabras clave principales
                        $tituloUpper = strtoupper($titulo);
                        $pasaFiltro1 = (count($palabrasValidas) === 0); // Si no hay palabras válidas, lo pasamos por defecto
                        foreach ($palabrasValidas as $palabraValida) {
                            if (strpos($tituloUpper, $palabraValida) !== false) {
                                $pasaFiltro1 = true;
                                break;
                            }
                        }

                        if (!$pasaFiltro1 && stripos($vendedor, $miTienda) === false) {
                            continue;
                        }

                        // Filtro 2: El título DEBE contener el modelo exacto (ignorando guiones y espacios)
                        $modeloClean = preg_replace('/[^A-Z0-9]/', '', strtoupper($modelo));
                        $tituloClean = preg_replace('/[^A-Z0-9]/', '', $tituloUpper);
                        
                        if (stripos($vendedor, $miTienda) === false && strpos($tituloClean, $modeloClean) === false) {
                            continue;
                        }

                        // Filtro 3: Evitar packs/combos de la competencia si nuestro producto es por unidad
                        $esPackLocal = preg_match('/\b(PACK|KIT|UNIDADES|UND|COMBO|CAJA X|X\d+)\b/i', $nombreProductoLocal);
                        $esPackCompetidor = preg_match('/\b(PACK|KIT|UNIDADES|UND|COMBO|CAJA X|X\d+)\b/i', $titulo);
                        
                        if (!$esPackLocal && $esPackCompetidor && stripos($vendedor, $miTienda) === false) {
                            continue; // Ignorar porque el competidor vende un pack/combo y nosotros no
                        }

                        $precioMinimo = null;
                        
                        if (is_array($pricesArray)) {
                            foreach ($pricesArray as $p) {
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
                    
                    usort($productos, function($a, $b) {
                        return $a['precio_mas_bajo'] <=> $b['precio_mas_bajo'];
                    });
                    
                    $soyElMasBarato = false;
                    $masBarato = null;
                    
                    if (count($productos) > 0) {
                        $masBarato = $productos[0];
                        if ($miTienda !== '' && stripos($masBarato['vendedor'], $miTienda) !== false) {
                            $soyElMasBarato = true;
                        }
                    }

                    return [
                        'success' => true,
                        'modelo' => $modelo,
                        'total_encontrados' => count($productos),
                        'mejor_precio' => $masBarato ? $masBarato['precio_mas_bajo'] : null,
                        'vendedor_mas_barato' => $masBarato ? $masBarato['vendedor'] : null,
                        'mi_tienda_es_mas_barata' => $miTienda !== '' ? $soyElMasBarato : null,
                        'productos' => $productos
                    ];
                }
                
                return [
                    'success' => true,
                    'message' => 'No se encontraron productos para el modelo.',
                    'total_encontrados' => 0,
                    'productos' => []
                ];
            }

            return [
                'success' => false,
                'message' => 'No se pudo extraer la data JSON (posible cambio de estructura o captcha).'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Excepción: ' . $e->getMessage()
            ];
        }
    }
}
