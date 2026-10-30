<?php

namespace App\Services\Deltron;

use App\Models\Catalogo\Caracteristicas;
use App\Models\Catalogo\Caracteristicas_Producto;
use App\Models\Catalogo\Caracteristicas_Sugerencias;
use App\Models\Catalogo\Producto;
use App\Models\Inventario\InventarioProveedorSku;
use Illuminate\Support\Facades\Log;

class DeltronSpecMapperService
{
    protected DeltronApiService $apiService;

    public function __construct(DeltronApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Parsea la tabla HTML de especificaciones de Deltron a un array asociativo [CLAVE => VALOR].
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

                // Formatos Deltron comunes:
                // 1 celda con dos puntos (ocurre en filas con rowspan previo): [Clave: Valor]
                // 2 celdas: [Clave, Valor]
                // 3 celdas: [Sección, Clave, Valor]
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
                } elseif (count($cellTexts) >= 3) {
                    $k = strtoupper(trim(rtrim($cellTexts[1], ':')));
                    $v = trim($cellTexts[2]);
                    if ($k !== '' && $v !== '') {
                        $specs[$k] = $v;
                    }
                }
            }
        }

        return $specs;
    }

    /**
     * Parsea bloques de texto de Deltron (specs_texto, descripcion_completa)
     * buscando patrones "[CLAVE: VALOR]" o "- CLAVE: VALOR".
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

        return $specs;
    }

    /**
     * Diccionario de sinónimos y equivalencias entre claves de Deltron y el ID de Característica de Unike Store.
     */
    public function getMapaClavesACaracteristica(): array
    {
        return [
            // ID 1: Hz
            'TASA DE REFRESCO' => 1,
            'FRECUENCIA DE ACTUALIZACION' => 1,
            'FRECUENCIA' => 1,
            'TASA DE REFRESCO / FRECUENCIA' => 1,

            // ID 2: Pulgadas
            'TAMAÑO DE PANTALLA' => 2,
            'PULGADAS' => 2,
            'TAMAÑO' => 2,

            // ID 3: Resolucion
            'RESOLUCIÓN' => 3,
            'RESOLUCION' => 3,
            'RESOLUCIÓN MÁXIMA' => 3,
            'RESOLUCION MAXIMA' => 3,
            'RESOLUCIÓN NATIVA' => 3,

            // ID 4: Procesador
            'PROCESADOR' => 4,
            'CPU' => 4,
            'CHIPSET' => 4,

            // ID 5: Memoria RAM
            'MEMORIA RAM' => 5,
            'CAPACIDAD DE MEMORIA' => 5,
            'RAM' => 5,

            // ID 6: Almacenamiento
            'ALMACENAMIENTO' => 6,
            'CAPACIDAD DE ALMACENAMIENTO' => 6,
            'DISCO DURO' => 6,
            'UNIDAD DE ESTADO SOLIDO' => 6,

            // ID 7: Graficos
            'GRAFICOS' => 7,
            'GRÁFICOS' => 7,
            'TARJETA DE VIDEO' => 7,
            'CHIPSET DE VIDEO' => 7,
            'GPU' => 7,

            // ID 8: Teclado
            'TECLADO' => 8,

            // ID 9: Conectividad
            'CONECTIVIDAD' => 9,
            'CONEXIÓN INALÁMBRICA' => 9,
            'CONEXION INALAMBRICA' => 9,
            'INALÁMBRICO' => 9,
            'INALAMBRICO' => 9,
            'BLUETOOTH' => 9,
            'VERSIÓN DE BLUETOOTH' => 9,
            'VERSION DE BLUETOOTH' => 9,
            'ALCANCE BLUETOOTH' => 9,

            // ID 10: Peso
            'PESO NETO' => 10,
            'PESO' => 10,

            // ID 14: Color
            'COLOR' => 14,
            'COLORES' => 14,

            // ID 15: Sistema Operativo
            'SISTEMA OPERATIVO' => 15,
            'SISTEMAS OPERATIVOS' => 15,

            // ID 16: Bateria
            'BATERÍA RECARGABLE' => 16,
            'BATERIA' => 16,
            'BATERÍA' => 16,
            'BATERÍA (CAPACIDAD)' => 16,
            'BATERIA (CAPACIDAD)' => 16,
            'CAPACIDAD DE BATERÍA' => 16,
            'CAPACIDAD DE BATERIA' => 16,

            // ID 17: Puertos
            'PUERTOS' => 17,
            'PUERTO DE CARGA' => 17,
            'ENTRADAS' => 17,

            // ID 18: Tiempo de respuesta
            'TIEMPO DE RESPUESTA' => 18,

            // ID 19: Brillo
            'BRILLO' => 19,

            // ID 20: Contraste
            'CONTRASTE' => 20,
            'RELACIÓN DE CONTRASTE' => 20,

            // ID 21: Panel
            'TIPO DE PANEL' => 21,
            'PANEL' => 21,
            'TIPO' => 21, // En monitores suele ser IPS, VA, etc.

            // ID 22: Curvo
            'CURVATURA' => 22,
            'PANTALLA CURVA' => 22,
            'FORMATO DE PANTALLA' => 22,

            // ID 23: Angulo de vision
            'ÁNGULO DE VISIÓN' => 23,
            'ANGULO DE VISION' => 23,

            // ID 24: Montaje de pared
            'MONTAJE VESA' => 24,
            'MONTAJE DE PARED' => 24,

            // ID 25: Idioma
            'DISTRIBUCIÓN (LAYOUT)' => 25,
            'DISTRIBUCION' => 25,
            'IDIOMA' => 25,
            'LAYOUT' => 25,

            // ID 26: Cableado
            'CABLE' => 26,
            'TIPO DE CABLE' => 26,

            // ID 27: Longitud de cable
            'LONGITUD DE CABLE' => 27,
            'LONGITUD DEL CABLE' => 27,
            'LONGITUD' => 27,
            'LARGO DE CABLE' => 27,
            'LARGO DEL CABLE' => 27,

            // ID 28: Número de teclas
            'CANTIDAD DE TECLAS' => 28,
            'NÚMERO DE TECLAS' => 28,

            // ID 29: Diseño
            'FORMATO' => 29,
            'DISEÑO' => 29,

            // ID 30: Tipo de teclado
            'TIPO DE TECLADO' => 30,
            'TECNOLOGÍA' => 30, // en teclados suele ser MEMBRANA / MECANICO

            // ID 31: Tecnología de imp.
            'TECNOLOGÍA DE IMPRESIÓN' => 31,
            'TECNOLOGIA DE IMPRESION' => 31,

            // ID 32: Velocidad de imp.
            'VELOCIDAD DE IMPRESIÓN' => 32,
            'VELOCIDAD DE IMPRESION' => 32,

            // ID 33: Funciones
            'FUNCIONES' => 33,

            // ID 34: Resolución de imp.
            'RESOLUCIÓN DE IMPRESIÓN' => 34,

            // ID 35: Tipo de escáner
            'TIPO DE ESCÁNER' => 35,
            'TIPO DE ESCANER' => 35,

            // ID 36: Resolución escáner
            'RESOLUCIÓN DE ESCÁNER' => 36,

            // ID 37: SO compatibles
            'COMPATIBILIDAD SO' => 37,

            // ID 40: Tipo de tinta
            'TIPO DE TINTA' => 40,

            // ID 41: Certificado
            'CERTIFICADO' => 41,
            'CERTIFICACIÓN' => 41,
            'CERTIFICACION' => 41,

            // ID 42: Potencia
            'POTENCIA' => 42,
            'CONSUMO' => 42,

            // ID 45: Capacidad
            'CAPACIDAD' => 45,

            // ID 46: Interfaz
            'INTERFAZ' => 46,

            // ID 47: Ve. de rotación
            'VELOCIDAD DE ROTACIÓN' => 47,
            'VELOCIDAD DE ROTACION' => 47,

            // ID 48: Ve. transferencia
            'VELOCIDAD DE TRANSFERENCIA' => 48,

            // ID 50: Aplicaciones
            'USO RECOMENDADO' => 50,
            'USO' => 50,
            'APLICACIONES' => 50,

            // ID 53: Material
            'MATERIAL' => 53,

            // ID 54: Rel. de Aspecto
            'PROPORCIÓN' => 54,
            'RELACIÓN DE ASPECTO' => 54,

            // ID 55: Consumo
            'CONSUMO TÍPICO' => 55,

            // ID 56: Rotacion
            'PIVOTE' => 56,
            'GIRO' => 56,

            // ID 57: Inclinacion
            'INCLINACIÓN' => 57,

            // ID 58: DPI
            'DPI' => 58,
            'RESOLUCIÓN DPI' => 58,

            // ID 59: Sensor
            'SENSOR' => 59,
            'TIPO DE SENSOR' => 59,

            // ID 60: Botones
            'CANTIDAD DE BOTONES' => 60,
            'BOTONES' => 60,

            // ID 63: Compatibilidad
            'COMPATIBILIDAD' => 63,
            'PARA' => 63,
            'DISPOSITIVOS COMPATIBLES' => 63,

            // ID 64: IMPEDANCIA
            'IMPEDANCIA' => 64,

            // ID 67: Microfono
            'MICRÓFONO' => 67,
            'MICROFONO' => 67,

            // ID 69: Led
            'RETROILUMINACIÓN' => 69,
            'RETROILUMINACION' => 69,
            'ILUMINACIÓN' => 69,
            'RGB' => 69,

            // ID 70: Fuente de poder
            'FUENTE DE PODER' => 70,

            // ID 72: Conexión
            'TIPO DE CONEXIÓN' => 72,
            'TIPO DE CONEXION' => 72,
            'CONEXIÓN' => 72,
            'CONEXION' => 72,

            // ID 80: Tipo Memoria
            'TIPO DE MEMORIA' => 80,
            'TIPO MEMORIA' => 80,

            // ID 81: Mhz
            'VELOCIDAD DE MEMORIA' => 81,
            'MHZ' => 81,

            // ID 83: VRAM
            'VRAM' => 83,
            'MEMORIA DE VIDEO' => 83,

            // ID 85: Potencia de Carga
            'POTENCIA DE CARGA' => 85,
            'CORRIENTE' => 85,

            // ID 86: Velocidad de Lectura
            'VELOCIDAD DE LECTURA' => 86,
            'TRANSFERENCIA DE DATOS' => 86,

            // ID 87: Tipo De Conexion
            'TIPO DE CONECTOR' => 87,
            'CONECTOR' => 87,
            'CONECTORES' => 87,

            // ID 88: Presentacion
            'PRESENTACION' => 88,
            'PRESENTACIÓN' => 88,
            'EMPAQUE' => 88,

            // ID 90: NUCLEOS
            'CANTIDAD DE NÚCLEOS' => 90,
            'NÚCLEOS' => 90,
            'NUCLEOS' => 90,

            // ID 91: Segmento
            'GAMA' => 91,
            'SEGMENTO' => 91,

            // ID 92: Tipo de accesorio
            'DISPOSITIVO' => 92,
            'TIPO DE PRODUCTO' => 92,
            'TIPO DE ACCESORIO' => 92,
        ];
    }

    /**
     * Normaliza un valor de Deltron adaptándolo a las sugerencias registradas en Unike Store.
     */
    public function normalizarValor(int $idCaracteristica, string $valorDeltron, ?string $claveDeltron = null, array $sugerencias = []): string
    {
        $v = trim($valorDeltron);

        // Si hay sugerencias en base de datos para esta característica, buscar coincidencia
        if (!empty($sugerencias)) {
            // Coincidencia exacta o insensible a mayúsculas
            foreach ($sugerencias as $sug) {
                if (strcasecmp($sug, $v) === 0) {
                    return $sug;
                }
            }
        }

        // Reglas de normalización específicas por ID de Característica:
        switch ($idCaracteristica) {
            case 1: // Hz
                // Extraer el número más alto seguido de HZ o número solo (ej: "200 (QHD) ... / 144 ... HZ" -> 200 Hz o 144 Hz)
                if (preg_match_all('/(\d{2,3})\s*(?:HZ)?/i', $v, $matches)) {
                    $nums = array_map('intval', $matches[1]);
                    rsort($nums);
                    $maxHz = $nums[0] . ' Hz';
                    foreach ($sugerencias as $sug) {
                        if (str_contains($sug, (string)$nums[0])) return $sug;
                    }
                    return $maxHz;
                }
                break;

            case 2: // Pulgadas
                // Ej: "27 PULG" -> "27'" o "27\""
                if (preg_match('/(\d+(?:\.\d+)?)\s*(?:PULG|"|\')?/i', $v, $m)) {
                    $num = $m[1];
                    foreach ($sugerencias as $sug) {
                        if (str_starts_with($sug, $num)) return $sug;
                    }
                    return $num . '"';
                }
                break;

            case 3: // Resolucion
                // Ej: "2560 x 1440"
                if (preg_match('/(\d{3,4}\s*x\s*\d{3,4})/i', $v, $m)) {
                    $cleanRes = preg_replace('/\s+/', ' ', $m[1]);
                    foreach ($sugerencias as $sug) {
                        if (stripos($sug, $cleanRes) !== false) return $sug;
                    }
                    return $cleanRes;
                }
                break;

            case 10: // Peso
                // Ej: "6.59 KG" -> "6.59" o "41 GR" -> "41g"
                if (preg_match('/([\d\.]+)\s*(KG|G|GR|GRAMOS)?/i', $v, $m)) {
                    $cant = $m[1];
                    $unidad = strtoupper($m[2] ?? 'KG');
                    if ($unidad === 'G' || $unidad === 'GR' || $unidad === 'GRAMOS') {
                        return round(floatval($cant) / 1000, 3) . '';
                    }
                    return $cant;
                }
                break;

            case 14: // Color
                foreach ($sugerencias as $sug) {
                    if (stripos($v, $sug) !== false) return $sug;
                }
                break;

            case 18: // Tiempo de respuesta
                if (preg_match('/([\d\.]+)\s*MS/i', $v, $m)) {
                    $valMs = $m[1] . ' MS';
                    foreach ($sugerencias as $sug) {
                        if (strcasecmp($sug, $valMs) === 0) return $sug;
                    }
                    return $valMs;
                }
                break;

            case 21: // Panel
                if (stripos($v, 'IPS') !== false) return 'IPS';
                if (stripos($v, 'VA') !== false) return 'VA';
                if (stripos($v, 'OLED') !== false) return 'OLED';
                if (stripos($v, 'TN') !== false) return 'TN';
                break;

            case 22: // Curvo
                if (stripos($v, 'PLANO') !== false || strtoupper($v) === 'NO') return 'No';
                if (stripos($v, 'CURVO') !== false || stripos($v, 'CURV') !== false || strtoupper($v) === 'SI') return 'Si';
                break;

            case 30: // Tipo de teclado
                if (stripos($v, 'MEMBRANA') !== false) return 'Membrana';
                if (stripos($v, 'MECANIC') !== false || stripos($v, 'MECÁNIC') !== false) return 'Mecánico';
                break;

            case 53: // Material
                if (stripos($v, 'ABS') !== false) return 'ABS';
                if (stripos($v, 'ACERO') !== false) return 'Acero';
                if (stripos($v, 'ALUMIN') !== false) return 'Aluminio';
                foreach ($sugerencias as $sug) {
                    if (strcasecmp($sug, $v) === 0 || stripos($v, $sug) !== false) return $sug;
                }
                return mb_convert_case(mb_strtolower($v), MB_CASE_TITLE, 'UTF-8');

            case 63: // Compatibilidad
                return mb_convert_case(mb_strtolower($v), MB_CASE_TITLE, 'UTF-8');

            case 69: // Led
                if (stripos($v, 'SIN RETRO') !== false || stripos($v, 'NO') !== false) return 'Sin RGB';
                if (stripos($v, 'RGB') !== false || stripos($v, 'SI') !== false) return 'RGB';
                break;

            case 72: // Conexión
                if (stripos($v, 'INALAMBRIC') !== false || stripos($v, 'INALÁMBRIC') !== false || stripos($v, 'BLUETOOTH') !== false || stripos($v, '2.4') !== false) {
                    return 'Inalambrico';
                }
                if (stripos($v, 'CABLE') !== false || stripos($v, 'USB') !== false) {
                    return 'Cable';
                }
                break;

            case 85: // Potencia de Carga
                if (preg_match('/\b(\d{1,3})\s*W\b/i', $v, $m)) {
                    $valW = $m[1] . ' W';
                    foreach ($sugerencias as $sug) {
                        if (stripos($sug, $m[1]) !== false) return $sug;
                    }
                    return $valW;
                }
                break;

            case 86: // Velocidad de Lectura
                if (stripos($v, '480') !== false) {
                    return '480 Mbps';
                }
                foreach ($sugerencias as $sug) {
                    if (stripos($v, $sug) !== false) return $sug;
                }
                break;

            case 87: // Tipo De Conexion
                if (stripos($v, 'LIGHTNING') !== false && (stripos($v, 'TIPO C') !== false || stripos($v, 'TYPE C') !== false || stripos($v, 'USB-C') !== false || preg_match('/\bC\b/i', $v))) {
                    return 'C a Lightning';
                }
                if ((stripos($v, 'TIPO C') !== false || stripos($v, 'TYPE C') !== false) && (stripos($v, 'TIPO C') !== false || stripos($v, 'TYPE C') !== false)) {
                    return 'C a C';
                }
                foreach ($sugerencias as $sug) {
                    if (strcasecmp($sug, $v) === 0 || stripos($v, $sug) !== false) return $sug;
                }
                break;

            case 88: // Presentación
                $vUpper = strtoupper($v);
                foreach ($sugerencias as $sug) {
                    if (strcasecmp($sug, $v) === 0) return $sug;
                }
                return $vUpper;

            case 91: // Segmento
                if (stripos($v, 'GAM') !== false) return 'Gamer';
                if (stripos($v, 'OFICINA') !== false || stripos($v, 'PRODUCTIV') !== false) return 'Productividad';
                if (stripos($v, 'ESTUDIANT') !== false) return 'Estudiante';
                break;
        }

        // Si no aplica regla específica, recortar a máximo 100 caracteres para la base de datos
        return mb_substr($v, 0, 100);
    }

    /**
     * Mapea el conjunto de especificaciones extraídas de Deltron hacia las IDs de características de Unike.
     * Si se proporciona $idGrupo o $textoCompleto, se priorizan e infieren características complementarias.
     */
    public function mapearEspecificaciones(array $rawSpecs, ?int $idGrupo = null, ?string $textoCompleto = null): array
    {
        $mapaClaves = $this->getMapaClavesACaracteristica();
        $resultado = [];

        // Obtener sugerencias agrupadas por idCaracteristica para normalizar valores de tipo FILTRO
        $sugerenciasPorChar = [];
        $suggs = Caracteristicas_Sugerencias::all();
        foreach ($suggs as $sug) {
            $sugerenciasPorChar[$sug->idCaracteristica][] = $sug->sugerencia;
        }

        // 1. Procesar dimensiones compuestas si existen (ej: "ALTURA x ANCHO x LARGO: 2.80 x 15.00 x 32.80 CM")
        foreach ($rawSpecs as $k => $v) {
            if (str_contains($k, 'DIMENSI')) {
                if (preg_match('/([\d\.]+)\s*x\s*([\d\.]+)\s*x\s*([\d\.]+)\s*(CM|MM|M)?/i', $v, $dm)) {
                    $u = strtolower($dm[4] ?? 'cm');
                    // Alto (11), Ancho (12), Largo (13)
                    $resultado[11] = $dm[1] . ' ' . $u;
                    $resultado[12] = $dm[2] . ' ' . $u;
                    $resultado[13] = $dm[3] . ' ' . $u;
                }
            }
        }

        // 2. Mapear claves directas
        foreach ($rawSpecs as $claveDeltron => $valorDeltron) {
            $claveUpper = strtoupper(trim($claveDeltron));
            if (isset($mapaClaves[$claveUpper])) {
                $idCar = $mapaClaves[$claveUpper];
                // Solo si no fue asignado previamente
                if (!isset($resultado[$idCar])) {
                    $suggsChar = $sugerenciasPorChar[$idCar] ?? [];
                    $valNorm = $this->normalizarValor($idCar, $valorDeltron, $claveUpper, $suggsChar);
                    if ($valNorm !== '') {
                        $resultado[$idCar] = $valNorm;
                    }
                }
            }
        }

        // 3. Inferencias para cables o conectores si no se mapearon directamente
        $textoAnalisis = ($textoCompleto ?? '') . "\n" . ($rawSpecs['CARACTERISTICAS'] ?? '');

        // Tipo De Conexión (ID 87): ej "Tipo C a Lightning", "Tipo C a Tipo C"
        if (empty($resultado[87]) && $textoAnalisis !== '') {
            if (preg_match('/(?:tipo\s*c|type-?c|c)\s*(?:a|to|-)\s*lightning/i', $textoAnalisis)) {
                $resultado[87] = 'C a Lightning';
            } elseif (preg_match('/(?:tipo\s*c|type-?c|c)\s*(?:a|to|-)\s*(?:tipo\s*c|type-?c|c)/i', $textoAnalisis)) {
                $resultado[87] = 'C a C';
            }
        }

        // Potencia de Carga (ID 85): ej "20W Máx", "60W"
        if (empty($resultado[85]) && $textoAnalisis !== '') {
            if (preg_match('/\b(\d{1,3})\s*W\s*(?:máx|max|máximo)?\b/i', $textoAnalisis, $wm)) {
                $resultado[85] = $wm[1] . ' W';
            }
        }

        // Velocidad de Lectura / transferencia (ID 86): ej "480MB/s", "480 Mbps"
        if (empty($resultado[86]) && $textoAnalisis !== '') {
            if (preg_match('/\b(480\s*mb(?:\/s|ps)?)\b/i', $textoAnalisis)) {
                $resultado[86] = '480 Mbps';
            }
        }

        // Longitud de cable (ID 27): ej "2 MTS", "2 metros"
        if (empty($resultado[27]) && $textoAnalisis !== '') {
            if (preg_match('/\b(\d+(?:\.\d+)?)\s*(?:mts?|metros?)\b/i', $textoAnalisis, $lm)) {
                $resultado[27] = $lm[1] . ' MTS';
            }
        }

        return $resultado;
    }

    /**
     * Llena o actualiza las especificaciones de un producto Unike consultando la ficha oficial de Deltron.
     */
    public function llenarEspecificacionesProducto(Producto $producto, ?string $deltronSku = null, bool $soloCache = false): array
    {
        // 1. Obtener el SKU de Deltron
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
            // Intentar buscar por Part Number o Modelo en fichas locales
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

        if (empty($sku)) {
            return [
                'success' => false,
                'message' => 'No se encontró un SKU de Deltron vinculado a este producto para obtener sus especificaciones.',
                'insertadas' => 0,
                'actualizadas' => 0
            ];
        }

        // 2. Obtener ficha oficial de Deltron
        // En modo soloCache (importación masiva) solo se usa el snapshot local para no agotar
        // el límite de la API de Deltron (60 llamadas/hora).
        if ($soloCache) {
            $safeFilename = preg_replace('/[^A-Z0-9_-]/', '_', strtoupper(trim($sku)));
            $snapshotPath = storage_path('app/deltron_fichas/' . $safeFilename . '.json');
            $ficha = file_exists($snapshotPath) ? @json_decode(@file_get_contents($snapshotPath), true) : null;
        } else {
            $ficha = $this->apiService->getOrFetchFichaSku($sku);
        }
        if (!$ficha || (empty($ficha['specs_html']) && empty($ficha['descripcion_completa']))) {
            return [
                'success' => false,
                'message' => "La API de Deltron no devolvió especificaciones técnicas para el SKU {$sku}.",
                'insertadas' => 0,
                'actualizadas' => 0
            ];
        }

        // 3. Extraer y mapear especificaciones
        $rawSpecsHtml = $this->parseSpecsHtml($ficha['specs_html'] ?? '');
        $rawSpecsText = $this->parseSpecsText(($ficha['descripcion_completa'] ?? '') . "\n" . ($ficha['specs_texto'] ?? ''));
        $rawSpecs = array_merge($rawSpecsText, $rawSpecsHtml); // HTML sobrescribe/prioriza

        $textoCompleto = ($ficha['name'] ?? '') . "\n" .
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

        // 4. Filtrar por características permitidas:
        //    - Las configuradas en el grupo del producto
        //    - Las que el producto ya tenga asignadas actualmente en Caracteristicas_Producto
        //    - Las universales aplicables a cualquier producto informático/electrónico
        $idsGrupo = \App\Models\Catalogo\Caracteristicas_Grupo::where('idGrupoProducto', $producto->idGrupo)
            ->pluck('idCaracteristica')
            ->map(fn ($id) => (int) $id)
            ->all();

        $idsUniversales = [10, 11, 12, 13, 14, 27, 41, 51, 52, 53, 63];
        $idsExistentes = Caracteristicas_Producto::where('idProducto', $producto->idProducto)
            ->pluck('idCaracteristica')
            ->map(fn ($id) => (int) $id)
            ->all();

        $idsPermitidos = array_unique(array_merge($idsGrupo, $idsExistentes, $idsUniversales));
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

        // 5. Guardar en Caracteristicas_Producto
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
                // Solo se completan valores vacíos o en "0"; los valores ingresados manualmente se respetan
                if ($actual === '' || $actual === '0') {
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
            'message' => "Especificaciones desde Deltron ({$sku}): {$insertadas} nuevas, {$actualizadas} completadas, {$conservadas} conservadas (ya tenían valor).",
            'sku' => $sku,
            'insertadas' => $insertadas,
            'actualizadas' => $actualizadas,
            'conservadas' => $conservadas,
            'total' => count($detallesGuardados),
            'detalles' => $detallesGuardados
        ];
    }
}

