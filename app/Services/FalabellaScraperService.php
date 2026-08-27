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

                $buscarProductos = function ($array) use (&$buscarProductos, &$productosEncontrados) {
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

                        if (!$pasaFiltro1) {
                            continue;
                        }

                        // Filtro 1.5: Dinámico leyendo el título local
                        // Extraemos la primera palabra relevante del título de Falabella
                        if (!empty($nombreProductoLocal)) {
                            $palabrasTituloFalabella = explode(' ', preg_replace('/[^A-Z0-9 ]/', '', $tituloUpper));
                            $primeraPalabraFalabella = '';
                            foreach ($palabrasTituloFalabella as $p) {
                                if (strlen($p) > 2) { // Buscar la primera palabra real (ej. TINTA, CHIP, IMPRESORA)
                                    $primeraPalabraFalabella = $p;
                                    break;
                                }
                            }

                            // Verificamos si esa primera palabra existe en el título de nuestro producto
                            if ($primeraPalabraFalabella !== '') {
                                $tituloLocalLimpio = preg_replace('/[^A-Z0-9 ]/', '', strtoupper($nombreProductoLocal));
                                $palabrasTituloLocal = explode(' ', $tituloLocalLimpio);
                                
                                // Si la primera palabra de Falabella (el sustantivo principal) NO está en nuestro título, es probable que sea un accesorio
                                if (!in_array($primeraPalabraFalabella, $palabrasTituloLocal)) {
                                    continue;
                                }
                            }
                        }

                        // Filtro 2: Coincidencia flexible para variaciones de modelo
                        $palabrasModelo = array_filter(explode(' ', strtoupper(trim($modelo))));
                        $palabrasValidasModelo = [];

                        // Extraemos solo las palabras relevantes (>2 caracteres)
                        foreach ($palabrasModelo as $palabraMod) {
                            $pMod = preg_replace('/[^A-Z0-9]/', '', $palabraMod);
                            if (strlen($pMod) > 2) {
                                $palabrasValidasModelo[] = $pMod;
                            }
                        }

                        $marcaCompetidor = strtoupper($item['brand'] ?? '');
                        $tituloLimpio = preg_replace('/[^A-Z0-9]/', '', $tituloUpper);
                        $marcaLimpia = preg_replace('/[^A-Z0-9]/', '', $marcaCompetidor);
                        $vendedorLimpio = preg_replace('/[^A-Z0-9]/', '', strtoupper($vendedor));

                        $totalRequeridas = count($palabrasValidasModelo);
                        $encontradas = 0;

                        // Contamos cuántas palabras de la búsqueda existen realmente en el producto
                        foreach ($palabrasValidasModelo as $pMod) {
                            if (
                                strpos($tituloLimpio, $pMod) !== false ||
                                strpos($vendedorLimpio, $pMod) !== false ||
                                strpos($marcaLimpia, $pMod) !== false
                            ) {
                                $encontradas++;
                            }
                        }

                        // LÓGICA DE UMBRAL: 
                        // Si buscamos 3 o más palabras, permitimos que 1 falle (ej. "HA" vs "GA")
                        // Si son 1 o 2 palabras, exigimos coincidencia total para no traer basura.
                        $umbral = $totalRequeridas >= 3 ? $totalRequeridas - 1 : $totalRequeridas;

                        if ($encontradas < $umbral) {
                            continue; // Descartamos solo si no alcanza el umbral mínimo
                        }

                        // Filtro 3: Evitar packs/combos de la competencia si nuestro producto es por unidad
                        $esPackLocal = preg_match('/\b(PACK|KIT|UNIDADES|UND|COMBO|CAJA X|X\d+)\b/i', $nombreProductoLocal);
                        $esPackCompetidor = preg_match('/\b(PACK|KIT|UNIDADES|UND|COMBO|CAJA X|X\d+)\b/i', $titulo);

                        if (!$esPackLocal && $esPackCompetidor) {
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

                    usort($productos, function ($a, $b) {
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
