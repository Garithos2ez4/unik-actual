<?php

namespace App\Services\Deltron\Classifiers;

use App\Services\Deltron\Contracts\DeltronCategoryClassifierInterface;

/**
 * Clasificador y normalizador modular de Deltron para la Categoría 1: LAPTOPS (PORTÁTILES).
 *
 * Alinea la clasificación de grupos (Laptop Gamer, Laptop Empresarial, Laptop Hogar)
 * y el mapeo de especificaciones técnicas con el ERP y Falabella (LaptopMapper),
 * garantizando que al descargar la plantilla Excel de Falabella todos los campos obligatorios:
 * Tipo de Computador, Núcleos, Pulgadas, Procesador, Sistema Operativo, Almacenamiento,
 * Resolución, Memoria RAM, Gráficos, Hz, Dimensiones y Peso estén completos y válidos.
 */
class LaptopsClassifier implements DeltronCategoryClassifierInterface
{
    public const CATEGORIA_ID = 1; // Laptops / Portátiles

    public const GRUPO_LAPTOP_GAMER       = 1;
    public const GRUPO_LAPTOP_EMPRESARIAL = 2;
    public const GRUPO_LAPTOP_HOGAR       = 3;

    // Grupos relacionados a accesorios de laptops (para clasificarlos anticipadamente sin confundirlos con laptops)
    public const GRUPO_COOLER_LAPTOP      = 4;
    public const GRUPO_FUNDAS_LAPTOP      = 5;
    public const GRUPO_CARGADORES_LAPTOP  = 69;
    public const GRUPO_REPUESTOS_LAPTOP   = 92;
    public const GRUPO_BATERIA_LAPTOP     = 101;

    public function getCategoriaId(): int
    {
        return self::CATEGORIA_ID;
    }

    public function getGruposIds(): array
    {
        return [
            self::GRUPO_LAPTOP_GAMER,
            self::GRUPO_LAPTOP_EMPRESARIAL,
            self::GRUPO_LAPTOP_HOGAR,
            self::GRUPO_COOLER_LAPTOP,
            self::GRUPO_FUNDAS_LAPTOP,
            self::GRUPO_CARGADORES_LAPTOP,
            self::GRUPO_REPUESTOS_LAPTOP,
            self::GRUPO_BATERIA_LAPTOP,
        ];
    }

    public function supportsGrupo(int $idGrupo): bool
    {
        return in_array($idGrupo, $this->getGruposIds(), true);
    }

    /**
     * Reglas de prioridad temprana:
     * Si el item en Deltron pertenece a accesorios de notebook (Coolers, Fundas, Cargadores, Baterías),
     * se clasifica aquí ANTES de que las reglas generales de Laptop confundan "NOTEBOOK , ACCESORIOS"
     * con una Laptop física.
     */
    public function classifyEarly(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';

        // Detectar si Deltron categorizó el producto como "ACCESORIOS DE NOTEBOOK"
        if (
            str_contains($catDeltron, 'NOTEBOOK , ACCESORIOS') ||
            str_contains($catDeltron, 'ACCESORIOS DE NOTEBOOK') ||
            str_contains($catDeltron, 'ACCESORIOS NOTEBOOK') ||
            str_contains($subDeltron, 'NOTEBOOK, ACCESORIOS') ||
            str_contains($subDeltron, 'ACCESORIOS NOTEBOOK')
        ) {
            if (str_contains($titulo, 'CARGADOR') || str_contains($titulo, 'ADAPTADOR') || str_contains($subDeltron, 'CARGADOR') || str_contains($name, 'ADAP')) {
                return self::GRUPO_CARGADORES_LAPTOP; // 69
            }
            if (str_contains($titulo, 'COOLER') || str_contains($titulo, 'BASE') || str_contains($titulo, 'PAD DE ENFRIAMIENTO') || str_contains($titulo, 'STAND')) {
                return self::GRUPO_COOLER_LAPTOP; // 4
            }
            if (str_contains($titulo, 'FUNDA') || str_contains($titulo, 'CASE') || str_contains($titulo, 'SLEEVE')) {
                return self::GRUPO_FUNDAS_LAPTOP; // 5
            }
            if (str_contains($titulo, 'BATERIA') || str_contains($titulo, 'BATERÍA')) {
                return self::GRUPO_BATERIA_LAPTOP; // 101
            }
            return self::GRUPO_REPUESTOS_LAPTOP; // 92
        }

        return null;
    }

    /**
     * Clasificación principal para Laptops (Gamer, Empresarial, Hogar).
     */
    public function classify(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';
        $sku        = strtoupper($context['item']['sku'] ?? '');
        $pn         = strtoupper($context['item']['part_number'] ?? '');

        $esLaptop = (
            str_contains($catDeltron, 'NOTEBOOK') ||
            str_contains($catDeltron, 'PORTATIL') ||
            str_contains($catDeltron, 'LAPTOP') ||
            str_contains($subDeltron, 'NOTEBOOK') ||
            str_contains($subDeltron, 'PORTATIL') ||
            str_contains($subDeltron, 'LAPTOP') ||
            str_contains($titulo, 'LAPTOP') ||
            str_contains($titulo, 'NOTEBOOK') ||
            str_contains($titulo, 'MACBOOK') ||
            str_contains($titulo, 'CHROMEBOOK') ||
            str_starts_with($titulo, 'NB ') ||
            str_starts_with($name, 'NB ') ||
            str_starts_with($sku, 'NB') ||
            preg_match('/\b(ELITEBOOK|THINKPAD|PROBOOK|LATITUDE|EXPERTBOOK|IDEAPAD|VIVOBOOK|ZENBOOK|YOGA|ASPIRE|SWIFT|PAVILION|ENVY|SPECTRE|OMEN|VICTUS|LOQ|LEGION|TUF|ROG|PREDATOR|NITRO|VOSTRO|MACBOOK)\b/i', $titulo . ' ' . $name)
        );

        if (!$esLaptop) {
            return null;
        }

        $fuente = $titulo . ' ' . $subDeltron . ' ' . $name;

        // 1. Laptop Gamer (ID 1)
        if (
            str_contains($fuente, 'GAMER') ||
            str_contains($fuente, 'GAMING') ||
            str_contains($fuente, 'RTX') ||
            str_contains($fuente, 'LOQ') ||
            str_contains($fuente, 'VICTUS') ||
            str_contains($fuente, 'TUF') ||
            str_contains($fuente, 'ROG') ||
            str_contains($fuente, 'PREDATOR') ||
            str_contains($fuente, 'NITRO') ||
            str_contains($fuente, 'LEGION') ||
            str_contains($fuente, 'OMEN') ||
            str_contains($fuente, 'KATANA') ||
            str_contains($fuente, 'CYBORG') ||
            str_contains($fuente, 'SWORD') ||
            str_contains($fuente, 'RAIDER') ||
            str_contains($fuente, 'STRIX') ||
            str_contains($fuente, 'ZEPHYRUS') ||
            str_contains($fuente, 'HELIOS') ||
            preg_match('/\b(GTX\s*\d{4}|RTX\s*\d{4})\b/i', $fuente)
        ) {
            return self::GRUPO_LAPTOP_GAMER; // 1
        }

        // 2. Laptop Empresarial (ID 2)
        if (
            str_contains($fuente, 'THINKPAD') ||
            str_contains($fuente, 'PROBOOK') ||
            str_contains($fuente, 'LATITUDE') ||
            str_contains($fuente, 'EXPERTBOOK') ||
            str_contains($fuente, 'ELITEBOOK') ||
            str_contains($fuente, 'VOSTRO') ||
            str_contains($fuente, 'PRECISION') ||
            str_contains($fuente, 'ZBOOK') ||
            str_contains($fuente, 'THINKBOOK') ||
            str_contains($fuente, 'WORKSTATION') ||
            str_contains($fuente, 'EMPRESARIAL') ||
            str_contains($fuente, 'CORPORATIV')
        ) {
            return self::GRUPO_LAPTOP_EMPRESARIAL; // 2
        }

        // 3. Laptop Hogar / Uso General (ID 3)
        return self::GRUPO_LAPTOP_HOGAR; // 3
    }

    /**
     * Características requeridas y permitidas para Laptops en Unike Store y Falabella.
     */
    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        // Para Laptops físicas (1, 2, 3)
        if (in_array($idGrupo, [self::GRUPO_LAPTOP_GAMER, self::GRUPO_LAPTOP_EMPRESARIAL, self::GRUPO_LAPTOP_HOGAR], true)) {
            return [
                1,  // Hz
                2,  // Pulgadas
                3,  // Resolucion
                4,  // Procesador
                5,  // Memoria RAM
                6,  // Almacenamiento
                7,  // Graficos
                8,  // Teclado
                9,  // Conectividad
                10, // Peso
                11, // Alto
                12, // Ancho
                13, // Largo
                14, // Color
                15, // Sistema Operativo
                16, // Bateria
                17, // Puertos
                18, // Tiempo de respuesta
                21, // Panel
                25, // Idioma
                29, // Diseño
                67, // Microfono (Webcam integrada)
                69, // Led (Retroiluminación)
                90, // NUCLEOS
                91, // Segmento
            ];
        }

        // Para accesorios de laptops
        return [9, 10, 11, 12, 13, 14, 26, 27, 42, 53, 63, 72, 85, 91, 101];
    }

    /**
     * Enriquecimiento, inferencia desde texto y normalización 100% compatible con Falabella (LaptopMapper) y ERP.
     */
    public function enrichAndNormalizeSpecs(
        int $idGrupo,
        array $specsMapeadas,
        array $rawSpecs,
        string $textoCompleto,
        array $sugerenciasPorChar = []
    ): array {
        // Si es un accesorio, delegar dimensiones/peso base
        if (!in_array($idGrupo, [self::GRUPO_LAPTOP_GAMER, self::GRUPO_LAPTOP_EMPRESARIAL, self::GRUPO_LAPTOP_HOGAR], true)) {
            return $this->enrichAccesorioLaptopSpecs($idGrupo, $specsMapeadas, $rawSpecs, $textoCompleto);
        }

        $textoAnalisis = $textoCompleto . "\n" . implode("\n", array_map(
            fn ($k, $v) => "{$k}: {$v}",
            array_keys($rawSpecs),
            array_values($rawSpecs)
        ));

        // 1. PROCESADOR (ID 4)
        $specsMapeadas[4] = $this->resolveProcesador($specsMapeadas[4] ?? ($rawSpecs['PROCESADOR'] ?? ''), $textoAnalisis);

        // 2. NÚCLEOS (ID 90) - Crítico para Columna O de Falabella ('nucleosFbk')
        $specsMapeadas[90] = $this->resolveNucleos($specsMapeadas[90] ?? ($rawSpecs['NÚCLEOS'] ?? ($rawSpecs['NUMERO DE NUCLEOS'] ?? '')), $specsMapeadas[4], $textoAnalisis);

        // 3. PULGADAS (ID 2) - Crítico para Columna P de Falabella
        $specsMapeadas[2] = $this->resolvePulgadas($specsMapeadas[2] ?? ($rawSpecs['PULGADAS'] ?? ($rawSpecs['TAMAÑO DE PANTALLA'] ?? '')), $textoAnalisis);

        // 4. MEMORIA RAM (ID 5)
        $specsMapeadas[5] = $this->resolveMemoriaRam($specsMapeadas[5] ?? ($rawSpecs['MEMORIA RAM'] ?? ($rawSpecs['RAM'] ?? '')), $textoAnalisis);

        // 5. ALMACENAMIENTO (ID 6) - Crítico para Columna S de Falabella (ALMACENAMIENTO_LIST)
        $specsMapeadas[6] = $this->resolveAlmacenamiento($specsMapeadas[6] ?? ($rawSpecs['ALMACENAMIENTO'] ?? ($rawSpecs['DISCO DURO'] ?? '')), $textoAnalisis);

        // 6. RESOLUCIÓN (ID 3) - Crítico para Columna T de Falabella (RESOLUCION_MAP)
        $specsMapeadas[3] = $this->resolveResolucion($specsMapeadas[3] ?? ($rawSpecs['RESOLUCIÓN'] ?? ($rawSpecs['RESOLUCION'] ?? '')), $textoAnalisis);

        // 7. SISTEMA OPERATIVO (ID 15) - Crítico para Columna R de Falabella
        $specsMapeadas[15] = $this->resolveSistemaOperativo($specsMapeadas[15] ?? ($rawSpecs['SISTEMA OPERATIVO'] ?? ''), $textoAnalisis);

        // 8. GRÁFICOS / TARJETA DE VIDEO (ID 7)
        $specsMapeadas[7] = $this->resolveGraficos($specsMapeadas[7] ?? ($rawSpecs['GRAFICOS'] ?? ($rawSpecs['TARJETA DE VIDEO'] ?? '')), $idGrupo, $specsMapeadas[4], $textoAnalisis);

        // 9. TASA DE REFRESCO / HZ (ID 1)
        $specsMapeadas[1] = $this->resolveHz($specsMapeadas[1] ?? ($rawSpecs['TASA DE REFRESCO'] ?? ''), $idGrupo, $textoAnalisis);

        // 10. COLOR (ID 14) - Compatible con Falabella
        $specsMapeadas[14] = $this->resolveColorLaptop($specsMapeadas[14] ?? ($rawSpecs['COLOR'] ?? ''), $textoAnalisis);

        // 11. SEGMENTO (ID 91)
        $specsMapeadas[91] = match ($idGrupo) {
            self::GRUPO_LAPTOP_GAMER       => 'Gamer',
            self::GRUPO_LAPTOP_EMPRESARIAL => 'Productividad',
            default                        => 'Estudiante',
        };

        // 12. IDIOMA DEL TECLADO (ID 25)
        if (empty($specsMapeadas[25])) {
            if (preg_match('/\b(INGLES|INGLÉS|ENGLISH|US\s+LAYOUT)\b/i', $textoAnalisis) && !preg_match('/\b(ESPAÑOL|ESPANOL|SPANISH|LATINO)\b/i', $textoAnalisis)) {
                $specsMapeadas[25] = 'Inglés';
            } else {
                $specsMapeadas[25] = 'Español';
            }
        }

        // 13. PANEL (ID 21)
        if (empty($specsMapeadas[21])) {
            if (preg_match('/\bOLED\b/i', $textoAnalisis)) {
                $specsMapeadas[21] = 'OLED';
            } elseif (preg_match('/\b(WVA|VA)\b/i', $textoAnalisis)) {
                $specsMapeadas[21] = 'VA';
            } else {
                $specsMapeadas[21] = 'IPS';
            }
        }

        // 14. BATERÍA (ID 16)
        if (empty($specsMapeadas[16])) {
            if (preg_match('/\b(\d{1,2}\s*CELDAS?(?:\s*,\s*\d{2,3}\s*WH)?|\d{2,3}\s*WH)\b/i', $textoAnalisis, $mBat)) {
                $specsMapeadas[16] = ucwords(strtolower(trim($mBat[1])));
            } else {
                $specsMapeadas[16] = ($idGrupo === self::GRUPO_LAPTOP_GAMER) ? '4 Celdas 70WH' : '3 Celdas 41WH';
            }
        }

        // 15. MICRÓFONO / WEBCAM (ID 67)
        if (empty($specsMapeadas[67])) {
            $specsMapeadas[67] = 'Integrado con cámara web';
        }

        // 16. LED / RETROILUMINACIÓN (ID 69)
        if (empty($specsMapeadas[69])) {
            if ($idGrupo === self::GRUPO_LAPTOP_GAMER || preg_match('/\bRGB\b/i', $textoAnalisis)) {
                $specsMapeadas[69] = 'RGB';
            } elseif (preg_match('/\b(RETROILUMINAD|BACKLIT)\b/i', $textoAnalisis)) {
                $specsMapeadas[69] = 'Blanco';
            } else {
                $specsMapeadas[69] = 'Sin RGB';
            }
        }

        // 17. DIMENSIONES (Alto 11, Ancho 12, Largo 13 en cm) y PESO (10 en kg)
        // Para que las columnas V, W, X, Y del Excel de Falabella se llenen perfectamente sin celdas vacías
        $specsMapeadas = $this->ensureDimensionesYPesoLaptop($idGrupo, $specsMapeadas, $rawSpecs, $textoAnalisis);

        return $specsMapeadas;
    }

    /**
     * Resuelve y limpia el procesador para SEO y ficha técnica.
     * Ej: "Intel Core i7-13700H", "AMD Ryzen 7 7735HS", "Intel Core Ultra 7 155H".
     */
    protected function resolveProcesador(string $procActual, string $textoAnalisis): string
    {
        $proc = trim($procActual);

        // Si ya vino procesador válido, limpiamos frecuencias redundantes
        if ($proc !== '' && !preg_match('/^(N\/A|VARIOS|NO)$/i', $proc)) {
            $proc = preg_replace('/(\d+(?:\.\d+)?\s*GHZ.*)$/i', '', $proc);
            $proc = preg_replace('/\s+HASTA.*$/i', '', $proc);
            $proc = preg_replace('/\s+CON TURBO.*$/i', '', $proc);
            $proc = trim($proc, " ,-");
            if (strlen($proc) >= 6) {
                return $proc;
            }
        }

        // Inferir desde texto si estaba vacío o incompleto
        if (preg_match('/\b(INTEL\s+CORE\s+(?:ULTRA\s+[579]\s+\d{3}[A-Z]*|i[3579]-\d{4,5}[A-Z]*))\b/i', $textoAnalisis, $m)) {
            return ucwords(strtolower($m[1]));
        }
        if (preg_match('/\b(AMD\s+RYZEN\s+[3579]\s+\d{4}[A-Z]*)\b/i', $textoAnalisis, $m)) {
            return ucwords(strtolower($m[1]));
        }
        if (preg_match('/\b(INTEL\s+PROCESSOR\s+[A-Z0-9]+)\b/i', $textoAnalisis, $m)) {
            return ucwords(strtolower($m[1]));
        }
        if (preg_match('/\b(APPLE\s+M[1234](?:\s+(?:PRO|MAX))?)\b/i', $textoAnalisis, $m)) {
            return ucwords(strtolower($m[1]));
        }

        return $proc !== '' ? $proc : 'Intel Core i5';
    }

    /**
     * Resuelve los núcleos numéricos (Falabella espera 'Octa core', 'Deca core', etc., a través de CORES_MAP).
     * Devuelve número limpio como string: '4', '6', '8', '10', '12', '14', '16', '24'.
     */
    protected function resolveNucleos(string $coresActual, string $procesador, string $textoAnalisis): string
    {
        // 1. Extraer número si ya está presente
        if (preg_match('/\b(\d{1,2})\b/', $coresActual, $m)) {
            return $m[1];
        }

        // 2. Extraer de Deltron specs text (ej: "10 NÚCLEOS", "8 CORES", "12C/16T")
        if (preg_match('/\b(\d{1,2})\s*(?:NÚCLEOS|NUCLEOS|CORES|C)\b/i', $textoAnalisis, $m)) {
            return $m[1];
        }

        // 3. Inferencia precisa según familia de procesador
        $procUpper = strtoupper($procesador);

        if (str_contains($procUpper, '14900') || str_contains($procUpper, '13900')) return '24';
        if (str_contains($procUpper, '14700')) return '20';
        if (str_contains($procUpper, '13700') || str_contains($procUpper, '12700') || str_contains($procUpper, 'ULTRA 7')) return '16';
        if (str_contains($procUpper, '13600') || str_contains($procUpper, 'ULTRA 5')) return '14';
        if (str_contains($procUpper, '13500') || str_contains($procUpper, '12500')) return '12';
        if (str_contains($procUpper, '12450') || str_contains($procUpper, '13420') || str_contains($procUpper, '1235U') || str_contains($procUpper, '1335U')) return '10';
        if (str_contains($procUpper, 'RYZEN 7') || str_contains($procUpper, 'I7')) return '8';
        if (str_contains($procUpper, 'RYZEN 5') || str_contains($procUpper, '1215U')) return '6';
        if (str_contains($procUpper, 'RYZEN 3') || str_contains($procUpper, 'I3') || str_contains($procUpper, 'N100')) return '4';

        return '8';
    }

    /**
     * Resuelve las pulgadas de la pantalla. Ej: 15.6", 14", 16", 17.3".
     */
    protected function resolvePulgadas(string $pulgActual, string $textoAnalisis): string
    {
        if (preg_match('/(11\.6|12\.4|13\.3|13\.4|14(?:\.0)?|14\.5|15\.6|16(?:\.0)?|17(?:\.3)?)/i', $pulgActual, $m)) {
            return $m[1] . '"';
        }

        if (preg_match('/(11\.6|12\.4|13\.3|13\.4|14(?:\.0)?|14\.5|15\.6|16(?:\.0)?|17(?:\.3)?)\s*(?:\"|\'\'|PULG)/i', $textoAnalisis, $m)) {
            return $m[1] . '"';
        }

        return '15.6"';
    }

    /**
     * Resuelve la memoria RAM en formato estándar: "8 GB", "16 GB", "32 GB", "64 GB".
     */
    protected function resolveMemoriaRam(string $ramActual, string $textoAnalisis): string
    {
        if (preg_match('/\b(4|8|12|16|24|32|64)\s*GB\b/i', $ramActual, $m)) {
            return $m[1] . ' GB';
        }

        if (preg_match('/\b(4|8|12|16|24|32|64)\s*GB\b/i', $textoAnalisis, $m)) {
            return $m[1] . ' GB';
        }

        return '16 GB';
    }

    /**
     * Resuelve el almacenamiento adaptado exactamente a la lista oficial de Falabella (ALMACENAMIENTO_LIST):
     * '128GB', '256 GB', '512 GB', '1TB', '2TB'.
     */
    protected function resolveAlmacenamiento(string $almActual, string $textoAnalisis): string
    {
        $fuente = $almActual . ' ' . $textoAnalisis;

        if (preg_match('/\b(2)\s*TB\b/i', $fuente)) {
            return '2TB';
        }
        if (preg_match('/\b(1)\s*TB\b/i', $fuente) || preg_match('/\b1024\s*GB\b/i', $fuente)) {
            return '1TB';
        }
        if (preg_match('/\b512\s*GB\b/i', $fuente)) {
            return '512 GB';
        }
        if (preg_match('/\b256\s*GB\b/i', $fuente)) {
            return '256 GB';
        }
        if (preg_match('/\b128\s*GB\b/i', $fuente)) {
            return '128GB';
        }

        return '512 GB';
    }

    /**
     * Resuelve la resolución en un formato compatible con RESOLUCION_MAP de Falabella:
     * '1920 x 1080' (FHD), '1920 x 1200' (WUXGA / FHD+), '2560 x 1440' (WQHD), '2560 x 1600' (WQXGA), '3840 x 2160' (4K UHD).
     */
    protected function resolveResolucion(string $resActual, string $textoAnalisis): string
    {
        $fuente = $resActual . ' ' . $textoAnalisis;

        if (preg_match('/(3840\s*x\s*2160|4K\s*UHD|4K)/i', $fuente)) {
            return '3840 x 2160';
        }
        if (preg_match('/(2560\s*x\s*1600|WQXGA)/i', $fuente)) {
            return '2560 x 1600';
        }
        if (preg_match('/(2560\s*x\s*1440|2K|WQHD|QHD)/i', $fuente)) {
            return '2560 x 1440';
        }
        if (preg_match('/(1920\s*x\s*1200|WUXGA|FHD\+)/i', $fuente)) {
            return '1920 x 1200';
        }
        if (preg_match('/(1920\s*x\s*1080|FULL\s*HD|FHD|1080P)/i', $fuente)) {
            return '1920 x 1080';
        }
        if (preg_match('/(1366\s*x\s*768|HD)/i', $fuente)) {
            return '1366 x 768';
        }

        return '1920 x 1080';
    }

    /**
     * Resuelve el Sistema Operativo alineado con LaptopMapper::resolveSistemaOperativo():
     * 'Windows 11 Home', 'Windows 11 Pro', 'Free DOS', 'Linux', 'Mac OS'.
     */
    protected function resolveSistemaOperativo(string $soActual, string $textoAnalisis): string
    {
        $fuente = mb_strtolower($soActual . ' ' . $textoAnalisis);

        if (str_contains($fuente, 'mac') || str_contains($fuente, 'apple')) {
            return 'Mac OS';
        }
        if (str_contains($fuente, 'win 11 pro') || str_contains($fuente, 'windows 11 pro')) {
            return 'Windows 11 Pro';
        }
        if (str_contains($fuente, 'win 11') || str_contains($fuente, 'windows 11')) {
            return 'Windows 11 Home';
        }
        if (str_contains($fuente, 'win 10') || str_contains($fuente, 'windows 10')) {
            return 'Windows 10';
        }
        if (str_contains($fuente, 'free') || str_contains($fuente, 'dos') || str_contains($fuente, 'sin so') || str_contains($fuente, 'no os')) {
            return 'Free DOS';
        }
        if (str_contains($fuente, 'linux') || str_contains($fuente, 'ubuntu') || str_contains($fuente, 'endless')) {
            return 'Linux';
        }

        return 'Windows 11 Home';
    }

    /**
     * Resuelve la tarjeta de video / GPU. Ej: "NVIDIA GeForce RTX 4060 8GB", "Intel Iris Xe Graphics".
     */
    protected function resolveGraficos(string $gpuActual, int $idGrupo, string $procesador, string $textoAnalisis): string
    {
        $fuente = $gpuActual . ' ' . $textoAnalisis;

        // 1. Detectar tarjeta dedicada
        if (preg_match('/\b(RTX\s*\d{4}(?:\s*(?:TI|SUPER))?(?:\s*\d{1,2}\s*GB)?)\b/i', $fuente, $mGpu)) {
            return 'NVIDIA GeForce ' . strtoupper(trim($mGpu[1]));
        }
        if (preg_match('/\b(GTX\s*\d{4}(?:\s*(?:TI|SUPER))?(?:\s*\d{1,2}\s*GB)?)\b/i', $fuente, $mGpu)) {
            return 'NVIDIA GeForce ' . strtoupper(trim($mGpu[1]));
        }
        if (preg_match('/\b(RADEON\s*RX\s*\d{4}[A-Z]*(?:\s*\d{1,2}\s*GB)?)\b/i', $fuente, $mGpu)) {
            return 'AMD ' . ucwords(strtolower(trim($mGpu[1])));
        }

        // 2. Si es Gamer pero no detectó modelo exacto
        if ($idGrupo === self::GRUPO_LAPTOP_GAMER) {
            return 'NVIDIA GeForce RTX 3050 6GB';
        }

        // 3. Gráficos integrados
        if (stripos($procesador, 'Ryzen') !== false || stripos($fuente, 'Radeon') !== false) {
            return 'AMD Radeon Graphics';
        }

        return 'Intel Iris Xe Graphics';
    }

    /**
     * Resuelve la tasa de refresco en Hz.
     */
    protected function resolveHz(string $hzActual, int $idGrupo, string $textoAnalisis): string
    {
        if (preg_match('/\b(60|75|100|120|144|165|240|360)\s*Hz\b/i', $hzActual, $m)) {
            return $m[1] . ' Hz';
        }

        if (preg_match('/\b(60|75|100|120|144|165|240|360)\s*Hz\b/i', $textoAnalisis, $m)) {
            return $m[1] . ' Hz';
        }

        return ($idGrupo === self::GRUPO_LAPTOP_GAMER) ? '144 Hz' : '60 Hz';
    }

    /**
     * Resuelve el color adaptado a las opciones válidas de Falabella.
     */
    protected function resolveColorLaptop(string $rawColor, string $textoAnalisis): string
    {
        $fuente = mb_strtolower($rawColor . ' ' . $textoAnalisis);

        if (str_contains($fuente, 'platead') || str_contains($fuente, 'silver') || str_contains($fuente, 'plata')) {
            return 'Plateado';
        }
        if (str_contains($fuente, 'negro') || str_contains($fuente, 'black') || str_contains($fuente, 'abyssal') || str_contains($fuente, 'onyx')) {
            return 'Negro';
        }
        if (str_contains($fuente, 'azul') || str_contains($fuente, 'blue') || str_contains($fuente, 'navy')) {
            return 'Azul';
        }
        if (str_contains($fuente, 'blanco') || str_contains($fuente, 'white')) {
            return 'Blanco';
        }
        if (str_contains($fuente, 'dorad') || str_contains($fuente, 'gold')) {
            return 'Dorado';
        }

        return 'Gris'; // Gris (Graphite / Platinum Grey / Luna Grey es el más común)
    }

    /**
     * Garantiza que Alto, Ancho, Largo estén en cm y Peso en kg con valores por defecto realistas.
     */
    protected function ensureDimensionesYPesoLaptop(int $idGrupo, array $specs, array $rawSpecs, string $textoAnalisis): array
    {
        // Dimensiones en cm
        foreach ([11, 12, 13] as $idDim) {
            if (!empty($specs[$idDim])) {
                $val = (string) $specs[$idDim];
                if (preg_match('/([\d\.]+)\s*mm/i', $val, $mMm)) {
                    $cm = round(floatval($mMm[1]) / 10, 2);
                    $specs[$idDim] = $cm . ' cm';
                } elseif (preg_match('/([\d\.]+)/', $val, $mNum)) {
                    $num = floatval($mNum[1]);
                    // Si vino en mm sin unidad (ej. 359 en vez de 35.9)
                    if ($num > 70) {
                        $num = round($num / 10, 2);
                    }
                    $specs[$idDim] = $num . ' cm';
                }
            }
        }

        // Peso en kg
        if (!empty($specs[10])) {
            $pesoVal = (string) $specs[10];
            if (preg_match('/([\d\.]+)\s*(g|gr|gramos)\b/i', $pesoVal, $mGr)) {
                $specs[10] = (string) round(floatval($mGr[1]) / 1000, 3);
            } elseif (preg_match('/([\d\.]+)/', $pesoVal, $mP)) {
                $numP = floatval($mP[1]);
                if ($numP > 100) { // Si vino en gramos
                    $numP = round($numP / 1000, 3);
                }
                if ($numP > 0) {
                    $specs[10] = (string) $numP;
                } else {
                    unset($specs[10]);
                }
            }
        }

        // Defaults por grupo de laptop si Deltron no trae dimensiones o peso
        $defaults = match ($idGrupo) {
            self::GRUPO_LAPTOP_GAMER       => [11 => '2.5 cm', 12 => '26 cm', 13 => '36 cm', 10 => '2.3'],
            self::GRUPO_LAPTOP_EMPRESARIAL => [11 => '1.9 cm', 12 => '23 cm', 13 => '32.5 cm', 10 => '1.5'],
            default                        => [11 => '2 cm',   12 => '24 cm', 13 => '35.9 cm', 10 => '1.75'],
        };

        foreach ($defaults as $idCar => $defVal) {
            if (empty($specs[$idCar]) || trim((string) $specs[$idCar]) === '0') {
                $specs[$idCar] = $defVal;
            }
        }

        return $specs;
    }

    /**
     * Mapeo de especificaciones para accesorios de laptop (Coolers, Fundas, Cargadores, Baterías).
     */
    protected function enrichAccesorioLaptopSpecs(int $idGrupo, array $specs, array $rawSpecs, string $textoAnalisis): array
    {
        if (empty($specs[14])) {
            $specs[14] = 'Negro';
        }
        if (empty($specs[11])) $specs[11] = '4 cm';
        if (empty($specs[12])) $specs[12] = '26 cm';
        if (empty($specs[13])) $specs[13] = '36 cm';
        if (empty($specs[10])) $specs[10] = '0.4';

        if ($idGrupo === self::GRUPO_CARGADORES_LAPTOP) {
            if (empty($specs[42]) && preg_match('/\b(\d{2,3})\s*W\b/i', $textoAnalisis, $mW)) {
                $specs[42] = $mW[1] . ' W';
            }
        }

        return $specs;
    }
}

