<?php

namespace App\Services\Deltron\Categories\Cat06_Impresoras;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class ImpresorasCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 6;
    public const CATEGORIA_NOMBRE = 'Impresoras';

    public const GRUPO_IMPRESORA_TERMICA   = 40;
    public const GRUPO_IMPRESORA_LASER     = 41;
    public const GRUPO_IMPRESORA_TINTA     = 42;
    public const GRUPO_IMPRESORA_MATRICIAL = 43;
    public const GRUPO_TINTAS              = 44;
    public const GRUPO_CARTUCHOS           = 45;
    public const GRUPO_ALMOHADILLAS        = 46;
    public const GRUPO_CAJA_MANTENIMIENTO  = 47;
    public const GRUPO_CHIP_IMPRESORA      = 48;
    public const GRUPO_BOMBA_SUCCION       = 75;
    public const GRUPO_RESETEADORES        = 77;
    public const GRUPO_PAPELERIA           = 78;
    public const GRUPO_CABEZALES           = 79;
    public const GRUPO_CINTA_MATRICIAL     = 83;
    public const GRUPO_TONER               = 84;
    public const GRUPO_CABLE_FLEX          = 86;
    public const GRUPO_OTROS_SUMINISTROS   = 88;
    public const GRUPO_FUENTE_IMPRESORA    = 90;
    public const GRUPO_RODILLOS            = 93;
    public const GRUPO_ESCANER             = 97;
    public const GRUPO_BANDEJAS            = 98;

    public function getCategoriaId(): int
    {
        return self::CATEGORIA_ID;
    }

    public function getCategoriaNombre(): string
    {
        return self::CATEGORIA_NOMBRE;
    }

    public function getGruposIds(): array
    {
        return [
            self::GRUPO_IMPRESORA_TERMICA,
            self::GRUPO_IMPRESORA_LASER,
            self::GRUPO_IMPRESORA_TINTA,
            self::GRUPO_IMPRESORA_MATRICIAL,
            self::GRUPO_TINTAS,
            self::GRUPO_CARTUCHOS,
            self::GRUPO_ALMOHADILLAS,
            self::GRUPO_CAJA_MANTENIMIENTO,
            self::GRUPO_CHIP_IMPRESORA,
            self::GRUPO_BOMBA_SUCCION,
            self::GRUPO_RESETEADORES,
            self::GRUPO_PAPELERIA,
            self::GRUPO_CABEZALES,
            self::GRUPO_CINTA_MATRICIAL,
            self::GRUPO_TONER,
            self::GRUPO_CABLE_FLEX,
            self::GRUPO_OTROS_SUMINISTROS,
            self::GRUPO_FUENTE_IMPRESORA,
            self::GRUPO_RODILLOS,
            self::GRUPO_ESCANER,
            self::GRUPO_BANDEJAS,
        ];
    }

    public function classifyEarly(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';

        // Escáneres
        if (
            str_contains($catDeltron, 'ESCANER') ||
            str_contains($subDeltron, 'ESCANER') ||
            str_contains($titulo, 'ESCANER') ||
            str_contains($titulo, 'ESCÁNER') ||
            str_contains($titulo, 'SCANNER') ||
            str_contains($subDeltron, 'SCAN/COD/BARRAS')
        ) {
            return self::GRUPO_ESCANER;
        }

        return null;
    }

    public function classify(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';

        // Impresoras físicas
        if (str_contains($catDeltron, 'IMPRESORA') || str_contains($subDeltron, 'IMPRESORA') || str_contains($titulo, 'PRINTER')) {
            if (str_contains($titulo, 'LASER') || str_contains($subDeltron, 'LASER')) return self::GRUPO_IMPRESORA_LASER;
            if (str_contains($titulo, 'TERMICA') || str_contains($titulo, 'TERM') || str_contains($subDeltron, 'TERMICA')) return self::GRUPO_IMPRESORA_TERMICA;
            if (str_contains($titulo, 'MATRICIAL')) return self::GRUPO_IMPRESORA_MATRICIAL;
            return self::GRUPO_IMPRESORA_TINTA;
        }

        // Suministros directos
        if (str_contains($catDeltron, 'SUMINISTROS') || str_contains($subDeltron, 'SUMINIST')) {
            if (str_contains($subDeltron, 'TONER') || str_contains($titulo, 'TONER')) return self::GRUPO_TONER;
            if (str_contains($subDeltron, 'TINTAS') || str_contains($subDeltron, 'BOTELLAS') || str_contains($titulo, 'TINTA') || str_contains($titulo, 'BOTELLA')) return self::GRUPO_TINTAS;
            if (str_contains($titulo, 'CINTA')) return self::GRUPO_CINTA_MATRICIAL;
            if (str_contains($titulo, 'CARTUCHO')) return self::GRUPO_CARTUCHOS;
            return self::GRUPO_TINTAS;
        }

        return null;
    }

    public function getMapaClavesCategoria(?int $idGrupo = null): array
    {
        return [
            // Dimensiones físicas
            'ALTO' => 11,
            'ALTURA' => 11,
            'DIMENSIONES Y PESO ALTO' => 11,
            'ANCHO' => 12,
            'DIMENSIONES Y PESO ANCHO' => 12,
            'LARGO' => 13,
            'PROFUNDIDAD' => 13,
            'PROFUNDO' => 13,
            'DIMENSIONES Y PESO PROFUNDIDAD' => 13,
            'DIMENSIONES Y PESO LARGO' => 13,

            // Tecnología de impresión (31)
            'TECNOLOGIA DE IMPRESION' => 31,
            'TECNOLOGÍA DE IMPRESIÓN' => 31,
            'DESCRIPCION TECNOLOGIA DE IMPRESION' => 31,

            // Velocidad de impresión (32)
            'VELOCIDAD DE IMPRESION' => 32,
            'VELOCIDAD DE IMPRESIÓN' => 32,
            'ESPECIFICACIONES DE LA IMPRESORA VELOCIDAD DE IMPRESION' => 32,
            'VELOCIDAD DE IMPRESION MAXIMA EN NEGRO' => 32,
            'VELOCIDAD MAX COLOR NEGRO' => 32,
            'ESPECIFICACION IMPRESORA VELOCIDAD MAX COLOR NEGRO' => 32,
            'VELOCIDAD MAXIMA DE IMPRESION' => 32,
            'VELOCIDAD DE TRANSFERENCIA' => 32,

            // Funciones (33)
            'FUNCIONES' => 33,

            // Resolución de impresión (34)
            'RESOLUCION DE IMPRESION' => 34,
            'RESOLUCIÓN DE IMPRESIÓN' => 34,
            'ESPECIFICACIONES DE LA IMPRESORA RESOLUCION DE IMPRESION' => 34,
            'RESOLUCION DE IMPRESION MAXIMA EN NEGRO' => 34,
            'ESPECIFICACIONES DE LA IMPRESORA MAXIMA EN NEGRO' => 34,
            'MAXIMA EN NEGRO' => 34,
            'RESOLUCION MAX' => 34,
            'ESPECIFICACION IMPRESORA RESOLUCION MAX' => 34,
            'RESOLUCION MAXIMA DE IMPRESION' => 34,

            // Tipo de escáner (35)
            'TIPO DE ESCANER' => 35,
            'TIPO DE ESCÁNER' => 35,
            'ESPECIFICACIONES DEL ESCANER TIPO DE ESCANER' => 35,

            // Resolución escáner (36)
            'RESOLUCION DE ESCANEO' => 36,
            'RESOLUCIÓN DE ESCANEO' => 36,
            'RESOLUCION OPTICA' => 36,
            'RESOLUCIÓN ÓPTICA' => 36,
            'ESPECIFICACIONES DEL ESCANER RESOLUCION OPTICA' => 36,
            'RESOLUCION DE ESCANEO RESOLUCION OPTICA' => 36,
            'ESPECIF ESCANER RESOLUCION OPTICA' => 36,
            'RESOLUCION POR HARDWARE' => 36,
            'RESOLUCION DE ESCANEO RESOLUCION POR HARDWARE' => 36,

            // SO compatibles (37)
            'SISTEMAS OPERATIVOS COMPATIBLES' => 37,
            'COMPATIBILIDAD DE SISTEMAS OPERATIVOS' => 37,
            'SISTEMAS OPERATIVOS DE RED COMPATIBLES' => 37,
            'SISTEMA OPERATIVO COMPATIBLE' => 37,

            // Tamaño de impresión (38) y Tamaños de hoja (39)
            'TAMAÑO DE IMPRESION' => 38,
            'TAMAÑO DE IMPRESIÓN' => 38,
            'AREA MAXIMA DE IMPRESION' => 38,
            'MEDIO MAXIMO SOPORTADO' => 38,
            'TAMAÑO DE HOJAS' => 39,
            'SOPORTE DE PAPEL TAMAÑO DE HOJAS' => 39,
            'HOJAS SIMPLES TAMAÑO' => 39,
            'MEDIOS SOPORTADOS HOJAS SIMPLES TAMAÑO' => 39,
            'TAMAÑOS DE PAPEL SOPORTADOS' => 39,
        ];
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        // Impresoras completas
        if (in_array($idGrupo, [self::GRUPO_IMPRESORA_TERMICA, self::GRUPO_IMPRESORA_LASER, self::GRUPO_IMPRESORA_TINTA, self::GRUPO_IMPRESORA_MATRICIAL], true)) {
            return [
                9, 10, 11, 12, 13, 14, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 51, 52, 63,
            ];
        }

        // Tintas y consumibles
        return [14, 40, 51, 52, 61, 62, 63, 73, 74, 82, 84];
    }

    public function enrichAndNormalizeSpecs(
        int $idGrupo,
        array $specsMapeadas,
        array $rawSpecs,
        string $textoCompleto,
        array $sugerenciasPorChar = []
    ): array {
        $textoAnalisis = $textoCompleto . "\n" . implode("\n", array_map(
            fn ($k, $v) => "{$k}: {$v}",
            array_keys($rawSpecs),
            array_values($rawSpecs)
        ));

        $esImpresoraFisica = in_array($idGrupo, [
            self::GRUPO_IMPRESORA_TERMICA,
            self::GRUPO_IMPRESORA_LASER,
            self::GRUPO_IMPRESORA_TINTA,
            self::GRUPO_IMPRESORA_MATRICIAL,
        ], true);

        if (!$esImpresoraFisica) {
            if (empty($specsMapeadas[14])) {
                if (preg_match('/\b(CYAN|CIAN)\b/i', $textoAnalisis)) $specsMapeadas[14] = 'Cyan';
                elseif (preg_match('/\b(MAGENTA)\b/i', $textoAnalisis)) $specsMapeadas[14] = 'Magenta';
                elseif (preg_match('/\b(YELLOW|AMARILLO)\b/i', $textoAnalisis)) $specsMapeadas[14] = 'Amarillo';
                elseif (preg_match('/\b(BLACK|NEGRO)\b/i', $textoAnalisis)) $specsMapeadas[14] = 'Negro';
            }
            return $specsMapeadas;
        }

        // 1. Tecnología de impresión (31)
        $specsMapeadas[31] = match ($idGrupo) {
            self::GRUPO_IMPRESORA_LASER     => 'Láser',
            self::GRUPO_IMPRESORA_TERMICA   => 'Térmica',
            self::GRUPO_IMPRESORA_MATRICIAL => 'Matricial',
            default                         => 'Inyección de tinta',
        };

        // 2. Conectividad (9)
        $con = [];
        if (preg_match('/\b(WIFI|WI-FI|WIRELESS|INAL[AÁ]MBRIC[OA])\b/i', $textoAnalisis)) $con[] = 'Wi-Fi';
        if (preg_match('/\b(ETHERNET|RJ-?45|\bLAN\b)\b/i', $textoAnalisis)) $con[] = 'Ethernet';
        if (preg_match('/\b(BLUETOOTH|\bBT\b)\b/i', $textoAnalisis)) $con[] = 'Bluetooth';
        if (preg_match('/\b(SERIAL|RS-?232)\b/i', $textoAnalisis)) $con[] = 'Serial';
        $con[] = 'USB';
        $specsMapeadas[9] = implode(', ', array_unique($con));

        // 3. Funciones (33)
        $esMulti = (bool) preg_match('/\b(MULTIFUNCI[OÓ]N|MULTIFUNCIONAL|ALL\s+IN\s+ONE|\bMFP\b|COPIADORA|ESCANER|ESC[AÁ]NER|IMPRIME\s*[\/,]\s*ESCANEA|IMPRIME\s*[\/,]\s*COPIA)\b/iu', $textoAnalisis);
        $tieneFax = (bool) preg_match('/\bFAX\b/i', $textoAnalisis);
        if ($esMulti) {
            $specsMapeadas[33] = $tieneFax ? 'Imprime, Escanea, Copia, Fax' : 'Imprime, Escanea, Copia';
        } else {
            $specsMapeadas[33] = 'Impresión';
        }

        // 4. Velocidad de impresión (32)
        $vel = $this->resolveVelocidadImpresion((string) ($specsMapeadas[32] ?? ''), $rawSpecs, $textoAnalisis);
        if ($vel !== null) {
            $specsMapeadas[32] = $vel;
        }

        // 5. Resolución de impresión (34)
        $resImp = $this->resolveResolucionImpresion((string) ($specsMapeadas[34] ?? ''), $rawSpecs, $textoAnalisis);
        if ($resImp !== null) {
            $specsMapeadas[34] = $resImp;
        }

        // 6. Escáner: Tipo (35) y Resolución escáner (36)
        if ($esMulti) {
            $resEsc = $this->resolveResolucionEscaner((string) ($specsMapeadas[36] ?? ''), $rawSpecs, $textoAnalisis);
            if ($resEsc !== null) {
                $specsMapeadas[36] = $resEsc;
            }
            if (!empty($specsMapeadas[35])) {
                $specsMapeadas[35] = $this->normalizeTipoEscaner((string) $specsMapeadas[35]);
            } elseif (preg_match('/\b(CAMA\s+PLANA)\b/i', $textoAnalisis)) {
                $specsMapeadas[35] = preg_match('/\bADF\b/i', $textoAnalisis) ? 'Cama plana, ADF' : 'Cama plana';
            }
        } else {
            unset($specsMapeadas[35], $specsMapeadas[36]);
        }

        // 7. SO compatibles (37)
        $specsMapeadas[37] = $this->resolveSistemasOperativosCompatibles((string) ($specsMapeadas[37] ?? ''), $rawSpecs, $textoAnalisis);

        // 8. Tamaños de hoja (39) y Tamaño de impresión (38)
        [$tamImpresion, $tamanosHoja] = $this->resolveTamanosPapel(
            $idGrupo,
            (string) ($specsMapeadas[38] ?? ''),
            (string) ($specsMapeadas[39] ?? ''),
            $rawSpecs,
            $textoAnalisis
        );
        if ($tamImpresion !== null) {
            $specsMapeadas[38] = $tamImpresion;
        }
        if ($tamanosHoja !== null) {
            $specsMapeadas[39] = $tamanosHoja;
        }

        // 9. Tipo de tinta (40) en impresoras de tinta
        if ($idGrupo === self::GRUPO_IMPRESORA_TINTA && empty($specsMapeadas[40])) {
            if (preg_match('/\b(SUBLIMACI[OÓ]N)\b/iu', $textoAnalisis)) {
                $specsMapeadas[40] = 'Sublimación';
            } elseif (preg_match('/\b(PIGMENTADA)\b/i', $textoAnalisis)) {
                $specsMapeadas[40] = 'PIGMENTADA';
            } else {
                $specsMapeadas[40] = 'Dye Liquida';
            }
        }

        // 10. Color (14)
        if (empty($specsMapeadas[14])) {
            if (preg_match('/\b(BLANC[OA]|WHITE|\bWHT\b)\b/i', $textoAnalisis)) {
                $specsMapeadas[14] = 'Blanco';
            } else {
                $specsMapeadas[14] = 'Negro';
            }
        }

        // 11. Dimensiones (Alto 11, Ancho 12, Largo 13), Peso (10) y Empaque (51, 52)
        return $this->ensureDimensionesYPesoImpresora($specsMapeadas, $rawSpecs, $textoAnalisis);
    }

    protected function resolveVelocidadImpresion(string $actual, array $rawSpecs, string $textoAnalisis): ?string
    {
        $fuentes = [];
        foreach ($rawSpecs as $k => $v) {
            $kUp = strtoupper($k);
            if (str_contains($kUp, 'VELOCIDAD') && !str_contains($kUp, 'ESCAN') && !str_contains($kUp, 'COPIA') && !str_contains($kUp, 'FAX')) {
                $fuentes[] = $v;
            }
        }
        if ($actual !== '') {
            $fuentes[] = $actual;
        }

        $fuentePrioritaria = implode(' | ', $fuentes);

        // 1. Buscar en descripción / texto frases explícitas "velocidad (de impresión) (de) (hasta) X ppm/mm/s/cps"
        foreach ([$fuentePrioritaria, $textoAnalisis] as $src) {
            if (trim($src) === '') continue;

            // Caso Negro y Color explícitos (ej: "33 ppm negro / 15 ppm color" o "NEGRO 33 PPM Y COLOR 15 PPM")
            if (
                preg_match('/NEGRO[^0-9]{0,20}(\d+(?:[\.,]\d+)?)\s*(PPM|IPM)/iu', $src, $mN) &&
                preg_match('/COLOR[^0-9]{0,20}(\d+(?:[\.,]\d+)?)\s*(PPM|IPM)/iu', $src, $mC)
            ) {
                $u = strtolower($mN[2]);
                if ($mN[1] !== $mC[1]) {
                    return "Negro: {$mN[1]} {$u} / Color: {$mC[1]} {$u}";
                }
                return "{$mN[1]} {$u}";
            }

            if (preg_match('/(?:velocidad(?:\s+de\s+impresi[oó]n)?[\s:]*)(?:de\s+)?(?:hasta\s+)?(\d+(?:[\/.,]\d+)?)\s*(ppm|ipm|mm\/s|cps)\b/iu', $src, $m)) {
                $num = explode('/', str_replace(',', '.', $m[1]))[0];
                return "{$num} " . strtolower($m[2]);
            }

            if (preg_match('/\b(?:hasta\s+)?(\d+(?:[\/.,]\d+)?)\s*(ppm|mm\/s|cps)\b/iu', $src, $m)) {
                $num = explode('/', str_replace(',', '.', $m[1]))[0];
                return "{$num} " . strtolower($m[2]);
            }

            if ($src === $fuentePrioritaria && preg_match('/\b(?:hasta\s+)?(\d+(?:[\/.,]\d+)?)\s*(ipm)\b/iu', $src, $m)) {
                $num = explode('/', str_replace(',', '.', $m[1]))[0];
                return "{$num} ipm";
            }
        }

        return null;
    }

    protected function resolveResolucionImpresion(string $actual, array $rawSpecs, string $textoAnalisis): ?string
    {
        $fuentes = [];
        foreach ($rawSpecs as $k => $v) {
            $kUp = strtoupper($k);
            if (
                (str_contains($kUp, 'RESOLUC') || str_contains($kUp, 'MAXIMA EN NEGRO')) &&
                !str_contains($kUp, 'ESCAN') &&
                !str_contains($kUp, 'COPIA') &&
                !str_contains($kUp, 'FAX')
            ) {
                $fuentes[] = $v;
            }
        }
        if ($actual !== '') {
            $fuentes[] = $actual;
        }

        // Quitar secciones de escáner/copiadora/fax del fallback general para no confundir resoluciones
        $textoSinEscaner = preg_replace('/(?:ESPECIFICACIONES\s+DEL\s+ESCANER|RESOLUCION\s+DE\s+ESCANEO|RESOLUCION\s+DE\s+FAX).*$/si', ' ', $textoAnalisis) ?? $textoAnalisis;

        foreach ([implode(' | ', $fuentes), $textoSinEscaner, $textoAnalisis] as $src) {
            if (trim($src) === '') continue;
            // Normalizar puntos de miles como "1.200 x 1.200" o "4.800 x 1.200" o "5.760 x 1.440"
            $norm = preg_replace('/\b(\d)\.(\d{3})\b/', '$1$2', $src) ?? $src;

            if (preg_match('/(hasta\s+)?(\d{3,4})\s*[xX×]\s*(\d{3,4})\s*(?:dpi|ppp)\b/iu', $norm, $m)) {
                $tieneHasta = !empty($m[1]) || preg_match('/hasta\s+[\d\.]+\s*[xX×]\s*[\d\.]+\s*(?:dpi|ppp)/iu', $norm);
                return ($tieneHasta ? 'Hasta ' : '') . "{$m[2]} x {$m[3]} dpi";
            }

            if (preg_match('/\b(\d{3,4})\s*(?:dpi|ppp)\b/iu', $norm, $m)) {
                return "{$m[1]} dpi";
            }
        }

        return null;
    }

    protected function resolveResolucionEscaner(string $actual, array $rawSpecs, string $textoAnalisis): ?string
    {
        $fuentes = [];
        // Priorizar resolución óptica sobre hardware
        foreach ($rawSpecs as $k => $v) {
            $kUp = strtoupper($k);
            if (str_contains($kUp, 'OPTICA') || str_contains($kUp, 'ÓPTICA')) {
                $fuentes[] = $v;
            }
        }
        foreach ($rawSpecs as $k => $v) {
            $kUp = strtoupper($k);
            if ((str_contains($kUp, 'ESCAN') && str_contains($kUp, 'RESOLUC')) || str_contains($kUp, 'RESOLUCION POR HARDWARE')) {
                $fuentes[] = $v;
            }
        }
        if ($actual !== '') {
            $fuentes[] = $actual;
        }

        foreach ($fuentes as $src) {
            $norm = preg_replace('/\b(\d)\.(\d{3})\b/', '$1$2', $src) ?? $src;
            if (preg_match('/(hasta\s+)?(\d{3,4})\s*[xX×]\s*(\d{3,4})\s*(?:dpi|ppp)\b/iu', $norm, $m)) {
                $tieneHasta = !empty($m[1]);
                return ($tieneHasta ? 'Hasta ' : '') . "{$m[2]} x {$m[3]} dpi";
            }
            if (preg_match('/(hasta\s+)?(\d{3,4})\s*(?:dpi|ppp)\b/iu', $norm, $m)) {
                return "{$m[2]} x {$m[2]} dpi";
            }
        }

        return null;
    }

    protected function normalizeTipoEscaner(string $val): string
    {
        $tieneCama = (bool) preg_match('/CAMA\s+PLANA/i', $val);
        $tieneAdf  = (bool) preg_match('/\b(ADF|ALIMENTADOR\s+AUTOM[AÁ]TICO)\b/iu', $val);
        if ($tieneCama && $tieneAdf) return 'Cama plana, ADF';
        if ($tieneCama) return 'Cama plana';
        if ($tieneAdf) return 'ADF';
        return mb_substr(trim($val), 0, 100);
    }

    protected function resolveSistemasOperativosCompatibles(string $actual, array $rawSpecs, string $textoAnalisis): string
    {
        $src = $actual;
        if ($src === '') {
            foreach ($rawSpecs as $k => $v) {
                if (str_contains(strtoupper($k), 'SISTEMAS OPERATIVOS')) {
                    $src = $v;
                    break;
                }
            }
        }

        $pool = $src !== '' ? $src : $textoAnalisis;
        $os = [];
        if (preg_match('/\bWINDOWS\b/i', $pool)) {
            $vers = [];
            foreach (['11', '10', '8.1', '8', '7'] as $v) {
                if (preg_match('/\b' . preg_quote($v, '/') . '\b/', $pool)) {
                    $vers[] = $v;
                }
            }
            $os[] = !empty($vers) ? ('Windows ' . implode(', ', $vers)) : 'Windows';
            if (preg_match('/\b(SERVIDOR|SERVER)\b/i', $pool)) {
                $os[] = 'Windows Server';
            }
        }
        if (preg_match('/\b(MAC\s*OS|MACOS|OS\s*X)\b/i', $pool)) $os[] = 'macOS';
        if (preg_match('/\bLINUX\b/i', $pool)) $os[] = 'Linux';
        if (preg_match('/\bANDROID\b/i', $pool)) $os[] = 'Android';
        if (preg_match('/\b(IOS|IPAD|IPHONE|AIRPRINT)\b/i', $pool)) $os[] = 'iOS';

        if (!empty($os)) {
            return implode(', ', array_unique($os));
        }

        return 'Windows 11, 10, macOS';
    }

    protected function resolveTamanosPapel(
        int $idGrupo,
        string $tamImpActual,
        string $tamHojaActual,
        array $rawSpecs,
        string $textoAnalisis
    ): array {
        if ($idGrupo === self::GRUPO_IMPRESORA_TERMICA) {
            $anchoPapel = '80 mm';
            if (preg_match('/\b(58\s*mm|80\s*mm)\b/i', $textoAnalisis, $m)) {
                $anchoPapel = strtolower(preg_replace('/\s+/', ' ', $m[1]));
            }
            return [$tamImpActual !== '' ? $tamImpActual : $anchoPapel, $tamHojaActual !== '' ? $tamHojaActual : "Rollo térmico {$anchoPapel}"];
        }

        $fuentesHoja = [$tamHojaActual, $tamImpActual];
        foreach ($rawSpecs as $k => $v) {
            $kUp = strtoupper($k);
            if (str_contains($kUp, 'HOJA') || str_contains($kUp, 'PAPEL') || str_contains($kUp, 'MEDIO')) {
                $fuentesHoja[] = $v;
            }
        }
        $textoHojas = implode(' ', $fuentesHoja);
        if (trim($textoHojas) === '') {
            $textoHojas = $textoAnalisis;
        }

        $formatos = [];
        if (preg_match('/A3\+/i', $textoHojas)) $formatos[] = 'A3+';
        elseif (preg_match('/A3/i', $textoHojas)) $formatos[] = 'A3';
        if (preg_match('/A4/i', $textoHojas)) $formatos[] = 'A4';
        if (preg_match('/A5/i', $textoHojas)) $formatos[] = 'A5';
        if (preg_match('/A6/i', $textoHojas)) $formatos[] = 'A6';
        if (preg_match('/B5/i', $textoHojas)) $formatos[] = 'B5';
        if (preg_match('/B6/i', $textoHojas)) $formatos[] = 'B6';
        if (preg_match('/CARTA/i', $textoHojas)) $formatos[] = 'Carta';
        if (preg_match('/OFICIO/i', $textoHojas)) $formatos[] = 'Oficio';
        if (preg_match('/LEGAL/i', $textoHojas)) $formatos[] = 'Legal';
        if (preg_match('/EJECUTIVO|EXECUTIVE/i', $textoHojas)) $formatos[] = 'Ejecutivo';
        if (preg_match('/SOBRE|C5|DL/i', $textoHojas)) $formatos[] = 'Sobres';

        if (empty($formatos)) {
            $formatos = ['A4', 'Carta', 'Oficio'];
        }

        $listaHojas = implode(', ', $formatos);

        if (in_array('A3+', $formatos, true)) {
            $maxImp = 'A3+';
        } elseif (in_array('A3', $formatos, true)) {
            $maxImp = 'A3';
        } elseif (in_array('Oficio', $formatos, true) || in_array('Legal', $formatos, true)) {
            $maxImp = 'A4 / Oficio';
        } else {
            $maxImp = 'A4';
        }

        return [$maxImp, $listaHojas];
    }

    protected function ensureDimensionesYPesoImpresora(array $specsMapeadas, array $rawSpecs, string $textoAnalisis): array
    {
        $normDim = static function (?string $raw): ?string {
            if ($raw === null || trim($raw) === '') return null;
            if (preg_match('/(\d+(?:[\.,]\d+)?)\s*(CM|MM|M)?\b/i', $raw, $m)) {
                $num = (float) str_replace(',', '.', $m[1]);
                $u = strtoupper($m[2] ?? 'CM');
                if ($u === 'MM' || ($num > 120 && $u === 'CM')) {
                    $num = round($num / 10, 2);
                }
                return $num . ' cm';
            }
            return null;
        };

        $specsMapeadas[11] = $normDim((string) ($specsMapeadas[11] ?? ($rawSpecs['ALTO'] ?? ($rawSpecs['ALTURA'] ?? ''))));
        $specsMapeadas[12] = $normDim((string) ($specsMapeadas[12] ?? ($rawSpecs['ANCHO'] ?? '')));
        $specsMapeadas[13] = $normDim((string) ($specsMapeadas[13] ?? ($rawSpecs['PROFUNDIDAD'] ?? ($rawSpecs['LARGO'] ?? ($rawSpecs['PROFUNDO'] ?? '')))));

        if (empty($specsMapeadas[11]) || empty($specsMapeadas[12]) || empty($specsMapeadas[13])) {
            if (preg_match('/(\d+(?:[\.,]\d+)?)\s*(?:cm|mm)?\s*[xX×]\s*(\d+(?:[\.,]\d+)?)\s*(?:cm|mm)?\s*[xX×]\s*(\d+(?:[\.,]\d+)?)\s*(cm|mm)\b/i', $textoAnalisis, $m)) {
                $u = strtoupper($m[4]);
                $d1 = (float) str_replace(',', '.', $m[1]);
                $d2 = (float) str_replace(',', '.', $m[2]);
                $d3 = (float) str_replace(',', '.', $m[3]);
                if ($u === 'MM' || max($d1, $d2, $d3) > 120) {
                    $d1 = round($d1 / 10, 2);
                    $d2 = round($d2 / 10, 2);
                    $d3 = round($d3 / 10, 2);
                }
                $specsMapeadas[11] ??= $d1 . ' cm';
                $specsMapeadas[12] ??= $d2 . ' cm';
                $specsMapeadas[13] ??= $d3 . ' cm';
            }
        }

        if (($specsMapeadas[11] ?? null) === null) unset($specsMapeadas[11]);
        if (($specsMapeadas[12] ?? null) === null) unset($specsMapeadas[12]);
        if (($specsMapeadas[13] ?? null) === null) unset($specsMapeadas[13]);

        // Peso (10)
        if (empty($specsMapeadas[10])) {
            $pesoRaw = (string) ($rawSpecs['PESO'] ?? ($rawSpecs['PESO NETO'] ?? ($rawSpecs['PESO (KG)'] ?? '')));
            if ($pesoRaw !== '' && preg_match('/(\d+(?:[\.,]\d+)?)\s*(?:KG|KILOS?)?/i', $pesoRaw, $mp)) {
                $specsMapeadas[10] = str_replace(',', '.', $mp[1]) . ' kg';
            } elseif (preg_match('/peso[\s:]*(\d+(?:[\.,]\d+)?)\s*kg\b/i', $textoAnalisis, $mp)) {
                $specsMapeadas[10] = str_replace(',', '.', $mp[1]) . ' kg';
            }
        } elseif (is_numeric($specsMapeadas[10])) {
            $specsMapeadas[10] = $specsMapeadas[10] . ' kg';
        }

        // Dimensiones(paquete) (51) y Peso(paquete) (52)
        if (empty($specsMapeadas[51]) && !empty($specsMapeadas[11]) && !empty($specsMapeadas[12]) && !empty($specsMapeadas[13])) {
            $a = (float) $specsMapeadas[11];
            $b = (float) $specsMapeadas[12];
            $c = (float) $specsMapeadas[13];
            $specsMapeadas[51] = "{$a} x {$b} x {$c} cm";
        }
        if (empty($specsMapeadas[52]) && !empty($specsMapeadas[10])) {
            $specsMapeadas[52] = (string) ((float) $specsMapeadas[10]);
        }

        return $specsMapeadas;
    }
}

