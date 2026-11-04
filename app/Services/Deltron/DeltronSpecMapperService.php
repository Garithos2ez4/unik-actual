<?php

namespace App\Services\Deltron;

use App\Models\Catalogo\Caracteristicas;
use App\Models\Catalogo\Caracteristicas_Producto;
use App\Models\Catalogo\Caracteristicas_Sugerencias;
use App\Models\Catalogo\Producto;
use App\Models\Inventario\InventarioProveedorSku;
use Illuminate\Support\Facades\Log;

/**
 * Servicio Padre / Orquestador de Mapeo de Especificaciones Técnicas de Deltron.
 *
 * Responsabilidades:
 * 1. Obtención de Fichas de Deltron (Caché local o API).
 * 2. Parseo de HTML (tablas complejas de 1 a 4+ columnas) y texto plano.
 * 3. Mapeo universal base (Dimensiones, Peso, Puertos, Color).
 * 4. Delegación del mapeo y normalización específica al módulo de la categoría correspondiente (Cat01 a Cat17).
 * 5. Persistencia y sincronización segura en Caracteristicas_Producto.
 */
class DeltronSpecMapperService
{
    protected DeltronApiService $apiService;
    protected DeltronClassifierService $classifierService;

    public function __construct(DeltronApiService $apiService, ?DeltronClassifierService $classifierService = null)
    {
        $this->apiService = $apiService;
        $this->classifierService = $classifierService ?? new DeltronClassifierService();
    }

    /**
     * Parsea la tabla HTML de especificaciones de Deltron a un array asociativo [CLAVE => VALOR].
     * Maneja tablas de 1 celda (rowspan), 2 celdas estándar, 3 celdas (sección) y 4+ celdas (multinivel).
     */
    public function parseSpecsHtml(?string $html): array
    {
        if (empty($html) || trim($html) === '') {
            return [];
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $tables = $xpath->query('//table[not(.//table)]');
        if ($tables->length === 0) {
            $tables = $xpath->query('//table');
        }

        $specs = [];
        foreach ($tables as $table) {
            $rows = $xpath->query('./tr | ./tbody/tr', $table);
            foreach ($rows as $row) {
                $cols = $xpath->query('./td | ./th', $row);
                if ($cols->length === 0) continue;

                $cellTexts = [];
                foreach ($cols as $col) {
                    $t = trim(preg_replace('/\s+/', ' ', $col->textContent));
                    if ($t !== '') {
                        $cellTexts[] = $t;
                    }
                }

                if (empty($cellTexts)) continue;

                if (count($cellTexts) === 1 && str_contains($cellTexts[0], ':')) {
                    $parts = explode(':', $cellTexts[0], 2);
                    $k = strtoupper(trim(rtrim($parts[0], ':')));
                    $v = trim($parts[1]);
                    if ($k !== '' && $v !== '') {
                        $specs[$k] = $v;
                    }
                } elseif (count($cellTexts) === 2) {
                    $k = strtoupper(trim(rtrim($cellTexts[0], ':')));
                    $v = trim($cellTexts[1]);
                    if ($k !== '' && $v !== '') {
                        $specs[$k] = $v;
                    }
                } elseif (count($cellTexts) === 3) {
                    $sec = strtoupper(trim(rtrim($cellTexts[0], ':')));
                    $k = strtoupper(trim(rtrim($cellTexts[1], ':')));
                    $v = trim($cellTexts[2]);
                    if ($k !== '' && $v !== '') {
                        $specs[$k] = $v;
                        if (str_contains($sec, 'ALMACEN') && !isset($specs['ALMACENAMIENTO'])) {
                            $specs['ALMACENAMIENTO'] = $v;
                            $specs['CAPACIDAD DE ALMACENAMIENTO'] = $v;
                        }
                    }
                } elseif (count($cellTexts) >= 4) {
                    $v = trim(end($cellTexts));
                    $sec = strtoupper(trim(rtrim($cellTexts[0], ':')));
                    $sub = strtoupper(trim(rtrim($cellTexts[1], ':')));
                    $k = strtoupper(trim(rtrim($cellTexts[count($cellTexts) - 2], ':')));

                    if ($k !== '' && $v !== '') {
                        $specs[$k] = $v;
                        $specs[$sec . ' ' . $k] = $v;
                        $specs[$sub . ' ' . $k] = $v;
                        if (str_contains($sec, 'ALMACEN') || str_contains($sub, 'ALMACEN')) {
                            $specs['ALMACENAMIENTO'] = $v;
                            $specs['CAPACIDAD DE ALMACENAMIENTO'] = $v;
                        }
                    }
                }
            }
        }

        return $specs;
    }

    /**
     * Parsea bloques de texto de Deltron (specs_texto, descripcion_completa)
     * buscando patrones "[CLAVE: VALOR]", "- CLAVE: VALOR" o líneas compuestas con guiones.
     */
    public function parseSpecsText(?string $text): array
    {
        if (empty($text)) {
            return [];
        }

        $specs = [];

        // 1. Patrón [CLAVE: VALOR]
        if (preg_match_all('/\[([^:\]\n]+):\s*([^\]\n]+)\]/i', $text, $m)) {
            foreach ($m[1] as $idx => $k) {
                $kUpper = strtoupper(trim($k));
                $v = trim($m[2][$idx]);
                if ($kUpper !== '' && $v !== '') {
                    $specs[$kUpper] = $v;
                }
            }
        }

        // 2. Patrón "- CLAVE: VALOR"
        if (preg_match_all('/^[ \t]*-[ \t]*([^:\n]+):[ \t]*([^\n]+)$/m', $text, $m2)) {
            foreach ($m2[1] as $idx => $k) {
                $kUpper = strtoupper(trim($k));
                $v = trim($m2[2][$idx]);
                if (!isset($specs[$kUpper]) && $kUpper !== '' && $v !== '') {
                    $specs[$kUpper] = $v;
                }
            }
        }

        // 3. Patrón compuesto con guiones (Almacenamiento)
        if (preg_match_all('/^[ \t]*-[ \t]*ALMACENAMIENTO.*?-[ \t]*CAPACIDAD[ \t]*-[ \t]*([0-9]+\s*(?:TB|GB))/mi', $text, $m3)) {
            foreach ($m3[1] as $valAlm) {
                $specs['ALMACENAMIENTO'] = trim($valAlm);
                $specs['CAPACIDAD DE ALMACENAMIENTO'] = trim($valAlm);
            }
        }

        return $specs;
    }

    /**
     * Diccionario universal base de sinónimos y equivalencias de claves de Deltron hacia idCaracteristica.
     */
    public function getMapaClavesACaracteristica(): array
    {
        return [
            // Frecuencia / Hz
            'TASA DE REFRESCO' => 1,
            'FRECUENCIA DE ACTUALIZACION' => 1,
            'FRECUENCIA' => 1,
            'TASA DE REFRESCO / FRECUENCIA' => 1,

            // Pulgadas
            'TAMAÑO DE PANTALLA' => 2,
            'PULGADAS' => 2,
            'TAMAÑO' => 2,

            // Resolución
            'RESOLUCIÓN' => 3,
            'RESOLUCION' => 3,
            'RESOLUCIÓN MÁXIMA' => 3,
            'RESOLUCION MAXIMA' => 3,
            'RESOLUCIÓN NATIVA' => 3,

            // Procesador
            'PROCESADOR' => 4,
            'CPU' => 4,
            'CHIPSET' => 4,

            // Memoria RAM
            'MEMORIA RAM' => 5,
            'CAPACIDAD DE MEMORIA' => 5,
            'RAM' => 5,

            // Almacenamiento
            'ALMACENAMIENTO' => 6,
            'CAPACIDAD DE ALMACENAMIENTO' => 6,
            'DISCO DURO' => 6,
            'UNIDAD DE ESTADO SOLIDO' => 6,

            // Gráficos
            'GRAFICOS' => 7,
            'GRÁFICOS' => 7,
            'TARJETA DE VIDEO' => 7,
            'CHIPSET DE VIDEO' => 7,
            'GPU' => 7,

            // Teclado
            'TECLADO' => 8,

            // Conectividad
            'CONECTIVIDAD' => 9,
            'CONEXIÓN INALÁMBRICA' => 9,
            'CONEXION INALAMBRICA' => 9,
            'INALÁMBRICO' => 9,
            'INALAMBRICO' => 9,
            'BLUETOOTH' => 9,
            'VERSIÓN DE BLUETOOTH' => 9,
            'VERSION DE BLUETOOTH' => 9,

            // Peso
            'PESO NETO' => 10,
            'PESO' => 10,

            // Color
            'COLOR' => 14,
            'COLORES' => 14,

            // Sistema Operativo
            'SISTEMA OPERATIVO' => 15,
            'SISTEMAS OPERATIVOS' => 15,

            // Batería
            'BATERÍA RECARGABLE' => 16,
            'BATERIA' => 16,
            'BATERÍA' => 16,
            'BATERÍA (CAPACIDAD)' => 16,
            'CAPACIDAD DE BATERÍA' => 16,

            // Puertos
            'PUERTOS' => 17,
            'PUERTO DE CARGA' => 17,
            'ENTRADAS' => 17,

            // Monitor
            'TIEMPO DE RESPUESTA' => 18,
            'BRILLO' => 19,
            'CONTRASTE' => 20,
            'RELACIÓN DE CONTRASTE' => 20,
            'TIPO DE PANEL' => 21,
            'PANEL' => 21,
            'CURVATURA' => 22,
            'PANTALLA CURVA' => 22,
            'ÁNGULO DE VISIÓN' => 23,
            'MONTAJE VESA' => 24,
            'MONTAJE DE PARED' => 24,

            // Periféricos / Cables
            'DISTRIBUCIÓN (LAYOUT)' => 25,
            'DISTRIBUCION' => 25,
            'IDIOMA' => 25,
            'CABLE' => 26,
            'TIPO DE CABLE' => 26,
            'LONGITUD DE CABLE' => 27,
            'LONGITUD DEL CABLE' => 27,
            'CANTIDAD DE TECLAS' => 28,
            'NÚMERO DE TECLAS' => 28,
            'FORMATO' => 29,
            'DISEÑO' => 29,
            'TIPO DE TECLADO' => 30,
            'DPI' => 58,
            'RESOLUCIÓN DPI' => 58,
            'SENSOR' => 59,
            'BOTONES' => 60,
            'CANTIDAD DE BOTONES' => 60,
            'MICRÓFONO' => 67,
            'MICROFONO' => 67,
            'RETROILUMINACIÓN' => 69,
            'ILUMINACIÓN' => 69,
            'RGB' => 69,
            'TIPO DE CONEXIÓN' => 72,
            'CONEXIÓN' => 72,

            // Empaque y Dimensiones
            'MATERIAL' => 53,
            'POTENCIA' => 42,
            'CONSUMO' => 42,
            'CAPACIDAD' => 45,
            'INTERFAZ' => 46,
            'COMPATIBILIDAD' => 63,
            'EMPAQUE (ALTO X ANCHO X LARGO)' => 51,
            'EMPAQUE (ALTO x ANCHO x LARGO)' => 51,
            'DIMENSIONES DE EMPAQUE' => 51,
            'DIMENSIONES(PAQUETE)' => 51,
            'PESO BRUTO' => 52,
            'PESO(PAQUETE)' => 52,
            'PESO DEL PAQUETE' => 52,

            // Componentes PC / Case
            'FUENTE DE PODER' => 70,
            'INCLUYE FUENTE DE PODER' => 70,
            'VIDRIO LATERAL' => 71,
            'TEMPERED GLASS' => 71,
            'FORMATO DE PLACA' => 76,
            'MOTHERBOARDS SOPORTADAS (FF)' => 76,
            'MOTHERBOARDS SOPORTADAS' => 76,
            'FACTOR DE FORMA' => 77,
            'FACTOR DE FORMA DE CASE' => 77,
            'COOLER' => 78,
            'COOLERS' => 78,
            'VENTILADORES ARGB INCLUIDOS' => 78,

            // Procesador & Segmento
            'CANTIDAD DE NÚCLEOS' => 90,
            'NÚCLEOS' => 90,
            'NUCLEOS' => 90,
            'SEGMENTO' => 91,
            'GAMA' => 91,
        ];
    }

    /**
     * Normaliza un valor de Deltron adaptándolo a las sugerencias de la BD o reglas universales.
     */
    public function normalizarValor(int $idCaracteristica, string $valorDeltron, ?string $claveDeltron = null, array $sugerencias = []): string
    {
        $v = trim($valorDeltron);

        // 1. Coincidencia directa con sugerencias de la BD
        if (!empty($sugerencias)) {
            foreach ($sugerencias as $sug) {
                if (strcasecmp($sug, $v) === 0) {
                    return $sug;
                }
            }
        }

        // 2. Normalización universal para características comunes
        switch ($idCaracteristica) {
            case 1: // Hz
                if (preg_match_all('/(\d{2,3})\s*(?:HZ)?/i', $v, $matches)) {
                    $nums = array_map('intval', $matches[1]);
                    rsort($nums);
                    return $nums[0] . ' Hz';
                }
                break;

            case 2: // Pulgadas
                if (preg_match('/(\d+(?:\.\d+)?)\s*(?:PULG|"|\')?/i', $v, $m)) {
                    return $m[1] . '"';
                }
                break;

            case 3: // Resolucion
                if (preg_match('/(\d{3,4}\s*x\s*\d{3,4})/i', $v, $m)) {
                    return preg_replace('/\s+/', ' ', $m[1]);
                }
                break;

            case 10: // Peso
                if (preg_match('/([\d\.]+)\s*(KG|G|GR|GRAMOS)?/i', $v, $m)) {
                    $cant = (float) $m[1];
                    $unidad = strtoupper($m[2] ?? 'KG');
                    if ($unidad === 'G' || $unidad === 'GR' || $unidad === 'GRAMOS' || $cant > 100) {
                        return (string) round($cant / 1000, 3);
                    }
                    return (string) $cant;
                }
                break;

            case 14: // Color
                foreach ($sugerencias as $sug) {
                    if (stripos($v, $sug) !== false) return $sug;
                }
                break;

            case 51: // Dimensiones(paquete)
                if (preg_match('/([\d\.]+)\s*x\s*([\d\.]+)\s*x\s*([\d\.]+)\s*(CM|MM|M)?/i', $v, $dm)) {
                    $u = strtolower($dm[4] ?? 'cm');
                    return "{$dm[1]} x {$dm[2]} x {$dm[3]} {$u}";
                }
                break;

            case 52: // Peso(paquete)
                if (preg_match('/([\d\.]+)/', $v, $m)) {
                    return $m[1];
                }
                break;

            case 69: // Led
                if (stripos($v, 'SIN RETRO') !== false || stripos($v, 'NO') !== false) return 'Sin RGB';
                if (stripos($v, 'RGB') !== false || stripos($v, 'SI') !== false || stripos($v, 'ARGB') !== false) return 'RGB';
                break;

            case 72: // Conexión
                if (stripos($v, 'BLUETOOTH') !== false || preg_match('/\bBT\b/i', $v)) return 'Bluetooth';
                if (stripos($v, 'INALAMBRIC') !== false || stripos($v, 'WIRELESS') !== false || stripos($v, '2.4') !== false) return 'Inalámbrico';
                if (stripos($v, 'CABLE') !== false || stripos($v, 'USB') !== false) return 'USB';
                break;

            case 91: // Segmento
                if (stripos($v, 'GAM') !== false) return 'Gamer';
                if (stripos($v, 'OFICINA') !== false || stripos($v, 'PRODUCTIV') !== false) return 'Productividad';
                if (stripos($v, 'ESTUDIANT') !== false) return 'Estudiante';
                break;
        }

        return mb_substr($v, 0, 100);
    }

    /**
     * Mapea especificaciones técnicas de Deltron hacia las IDs de características de Unike Store,
     * coordinando con el módulo hijo de la categoría del producto.
     */
    public function mapearEspecificaciones(array $rawSpecs, ?int $idGrupo = null, ?string $textoCompleto = null): array
    {
        $mapaClaves = $this->getMapaClavesACaracteristica();
        $categoryClassifier = $idGrupo ? $this->classifierService->resolveClassifierForGrupo($idGrupo) : null;

        // Incorporar claves especializadas del módulo hijo si existen
        if ($categoryClassifier) {
            $mapaClaves = array_merge($mapaClaves, $categoryClassifier->getMapaClavesCategoria($idGrupo));
        }

        $resultado = [];

        // Obtener sugerencias de características para normalización
        $sugerenciasPorChar = [];
        $suggs = Caracteristicas_Sugerencias::all();
        foreach ($suggs as $sug) {
            $sugerenciasPorChar[$sug->idCaracteristica][] = $sug->sugerencia;
        }

        // 1. Dimensiones físicas compuestas universales (Alto 11, Ancho 12, Largo 13)
        foreach ($rawSpecs as $k => $v) {
            if (str_contains($k, 'DIMENSI')) {
                if (preg_match('/([\d\.]+)\s*x\s*([\d\.]+)\s*x\s*([\d\.]+)\s*(CM|MM|M)?/i', $v, $dm)) {
                    $u = strtolower($dm[4] ?? 'cm');
                    $nums = [(float)$dm[1], (float)$dm[2], (float)$dm[3]];
                    sort($nums);
                    $resultado[11] = $nums[0] . ' ' . $u;
                    $resultado[12] = $nums[1] . ' ' . $u;
                    $resultado[13] = $nums[2] . ' ' . $u;
                }
            }
        }

        // 2. Mapeo de claves directas
        foreach ($rawSpecs as $claveDeltron => $valorDeltron) {
            $claveUpper = strtoupper(trim($claveDeltron));
            if (isset($mapaClaves[$claveUpper])) {
                $idCar = $mapaClaves[$claveUpper];
                if (!isset($resultado[$idCar])) {
                    $suggsChar = $sugerenciasPorChar[$idCar] ?? [];

                    // Permitir que el hijo normalice primero según sus reglas de negocio
                    $valNorm = $categoryClassifier
                        ? $categoryClassifier->normalizarValorCategoria($idCar, $valorDeltron, $claveUpper, $suggsChar)
                        : null;

                    if ($valNorm === null) {
                        $valNorm = $this->normalizarValor($idCar, $valorDeltron, $claveUpper, $suggsChar);
                    }

                    if ($valNorm !== '') {
                        $resultado[$idCar] = $valNorm;
                    }
                }
            }
        }

        // 3. Consolidación de puertos (ID 17) si vino en bloque [PUERTOS] o desglose
        if (empty($resultado[17])) {
            $portsFound = [];
            if ($textoCompleto && preg_match('/\[PUERTOS\](.*?)(?:\[|\Z)/si', $textoCompleto, $mPortsBlock)) {
                if (preg_match_all('/^[ \t]*-[ \t]*([^:\n]+):[ \t]*([^\n]+)$/m', $mPortsBlock[1], $mpLines)) {
                    foreach ($mpLines[1] as $idxP => $kp) {
                        $portsFound[] = trim($kp) . ': ' . trim($mpLines[2][$idxP]);
                    }
                }
            }

            if (empty($portsFound)) {
                foreach ($rawSpecs as $rk => $rv) {
                    $rkUpper = strtoupper(trim($rk));
                    if (preg_match('/^(AUDIO|USB\s*\d|TYPE-C|TIPO-C|JACK\s*AUDIO|HDMI|DISPLAYPORT)\b/i', $rkUpper)) {
                        $portsFound[] = trim($rk) . ': ' . trim($rv);
                    }
                }
            }

            if (!empty($portsFound)) {
                $resultado[17] = implode(', ', $portsFound);
            }
        }

        // 4. Delegar enriquecimiento, inferencia y validaciones al módulo hijo de la categoría
        if ($categoryClassifier && $idGrupo) {
            $textoAnalisis = ($textoCompleto ?? '') . "\n" . ($rawSpecs['CARACTERISTICAS'] ?? '');
            $resultado = $categoryClassifier->enrichAndNormalizeSpecs(
                $idGrupo,
                $resultado,
                $rawSpecs,
                $textoAnalisis,
                $sugerenciasPorChar
            );
        }

        return $resultado;
    }

    /**
     * Llena o actualiza las especificaciones de un producto Unike consultando la ficha oficial de Deltron.
     */
    public function llenarEspecificacionesProducto(
        Producto $producto,
        ?string $deltronSku = null,
        bool $soloCache = false,
        bool $forceOverwrite = false
    ): array {
        // 1. Obtener SKU de Deltron
        $sku = $deltronSku;
        if (empty($sku)) {
            $invProvSku = InventarioProveedorSku::whereHas('inventarioProveedor', function ($q) use ($producto) {
                $q->where('idProducto', $producto->idProducto);
            })->where('idProveedor', 1)->first();

            if ($invProvSku && !empty($invProvSku->sku_proveedor)) {
                $sku = $invProvSku->sku_proveedor;
            }
        }

        if (empty($sku)) {
            $cleanPn = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($producto->partNumber ?? '')));
            $cleanMod = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($producto->modelo ?? '')));
            $fichasFiles = glob(storage_path('app/deltron_fichas/*.json'));
            foreach ($fichasFiles as $f) {
                $content = @json_decode(@file_get_contents($f), true);
                if ($content && is_array($content)) {
                    $fPn = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($content['part_number'] ?? '')));
                    $fMod = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($content['modelo_real'] ?? '')));
                    if (($cleanPn && $fPn === $cleanPn) || ($cleanMod && $fMod === $cleanMod)) {
                        $sku = $content['sku'] ?? null;
                        if ($sku) break;
                    }
                }
            }
        }

        $categoryClassifier = $this->classifierService->resolveClassifierForGrupo((int) $producto->idGrupo);

        if (empty($sku) && !$categoryClassifier) {
            return [
                'success' => false,
                'message' => 'No se encontró un SKU de Deltron vinculado a este producto para obtener sus especificaciones.',
                'insertadas' => 0,
                'actualizadas' => 0
            ];
        }

        // 2. Obtener ficha oficial de Deltron
        $ficha = null;
        if (!empty($sku)) {
            if ($soloCache) {
                $safeFilename = preg_replace('/[^A-Z0-9_-]/', '_', strtoupper(trim($sku)));
                $snapshotPath = storage_path('app/deltron_fichas/' . $safeFilename . '.json');
                $ficha = file_exists($snapshotPath) ? @json_decode(@file_get_contents($snapshotPath), true) : null;
            } else {
                $ficha = $this->apiService->getOrFetchFichaSku($sku);
            }
        }

        if ((!$ficha || (empty($ficha['specs_html']) && empty($ficha['descripcion_completa']))) && !$categoryClassifier) {
            return [
                'success' => false,
                'message' => "La API de Deltron no devolvió especificaciones técnicas para el SKU {$sku}.",
                'insertadas' => 0,
                'actualizadas' => 0
            ];
        }

        // 3. Extraer y mapear especificaciones
        $rawSpecsHtml = $ficha ? $this->parseSpecsHtml($ficha['specs_html'] ?? '') : [];
        $rawSpecsText = $ficha ? $this->parseSpecsText(($ficha['descripcion_completa'] ?? '') . "\n" . ($ficha['specs_texto'] ?? '')) : [];
        $rawSpecs = array_merge($rawSpecsText, $rawSpecsHtml);

        $textoCompleto = ($producto->nombreProducto ?? '') . "\n" .
                         ($producto->descripcionProducto ?? '') . "\n" .
                         ($ficha['name'] ?? '') . "\n" .
                         ($ficha['descripcion_completa'] ?? '') . "\n" .
                         ($ficha['descripcion_larga'] ?? '') . "\n" .
                         ($ficha['specs_texto'] ?? '');

        if (empty($rawSpecs) && trim($textoCompleto) === '') {
            return [
                'success' => false,
                'message' => "No se pudieron extraer campos de la ficha técnica de Deltron ({$sku}).",
                'insertadas' => 0,
                'actualizadas' => 0
            ];
        }

        $specsMapeadas = $this->mapearEspecificaciones($rawSpecs, $producto->idGrupo, $textoCompleto);
        if (empty($specsMapeadas)) {
            return [
                'success' => false,
                'message' => "Se extrajeron datos de Deltron pero ninguna especificación coincidió con el catálogo de características de Unike Store.",
                'insertadas' => 0,
                'actualizadas' => 0
            ];
        }

        // 4. Filtrar por características permitidas (Grupo, Módulo Hijo, Existentes, Universales)
        $idsGrupo = \App\Models\Catalogo\Caracteristicas_Grupo::where('idGrupoProducto', $producto->idGrupo)
            ->pluck('idCaracteristica')
            ->map(fn ($id) => (int) $id)
            ->all();

        $idsClassifier = $categoryClassifier
            ? $categoryClassifier->getAllowedCaracteristicasForGrupo((int) $producto->idGrupo)
            : [];

        $idsUniversales = [10, 11, 12, 13, 14, 27, 41, 51, 52, 53, 63];
        $idsExistentes = Caracteristicas_Producto::where('idProducto', $producto->idProducto)
            ->pluck('idCaracteristica')
            ->map(fn ($id) => (int) $id)
            ->all();

        $idsPermitidos = array_unique(array_merge($idsGrupo, $idsClassifier, $idsExistentes, $idsUniversales));
        if (!empty($idsPermitidos)) {
            $specsMapeadas = array_intersect_key($specsMapeadas, array_flip($idsPermitidos));
        }

        if (empty($specsMapeadas)) {
            return [
                'success' => false,
                'message' => "Deltron ({$sku}) no trae datos para las características configuradas en el grupo de este producto.",
                'insertadas' => 0,
                'actualizadas' => 0
            ];
        }

        // 5. Persistir en Caracteristicas_Producto
        $insertadas = 0;
        $actualizadas = 0;
        $conservadas = 0;
        $detallesGuardados = [];

        foreach ($specsMapeadas as $idCar => $valor) {
            $carMaster = Caracteristicas::find($idCar);
            if (!$carMaster || $carMaster->tipo === 'INVALIDO') continue;

            $valor = mb_substr(trim((string) $valor), 0, 100);
            if ($valor === '') continue;

            $existente = Caracteristicas_Producto::where('idProducto', $producto->idProducto)
                ->where('idCaracteristica', $idCar)
                ->first();

            if ($existente) {
                $actual = trim((string) $existente->caracteristicaProducto);
                $esFallbackGenerico = in_array($actual, ['Negro', '4 cm', '26 cm', '36 cm', '0.4', '1.75', '2 cm', '24 cm', '35.9 cm'], true)
                    && $valor !== $actual;

                if ($forceOverwrite || $actual === '' || $actual === '0' || $esFallbackGenerico) {
                    Caracteristicas_Producto::where('idProducto', $producto->idProducto)
                        ->where('idCaracteristica', $idCar)
                        ->update(['caracteristicaProducto' => $valor]);
                    $actualizadas++;
                } else {
                    $conservadas++;
                    continue;
                }
            } else {
                Caracteristicas_Producto::create([
                    'idProducto' => $producto->idProducto,
                    'idCaracteristica' => $idCar,
                    'caracteristicaProducto' => $valor
                ]);
                $insertadas++;
            }

            $detallesGuardados[] = [
                'idCaracteristica' => $idCar,
                'especificacion' => $carMaster->especificacion,
                'valor' => $valor
            ];
        }

        return [
            'success' => true,
            'message' => "Especificaciones desde Deltron ({$sku}): {$insertadas} nuevas, {$actualizadas} completadas, {$conservadas} conservadas.",
            'sku' => $sku,
            'insertadas' => $insertadas,
            'actualizadas' => $actualizadas,
            'conservadas' => $conservadas,
            'total' => count($detallesGuardados),
            'detalles' => $detallesGuardados
        ];
    }
}
