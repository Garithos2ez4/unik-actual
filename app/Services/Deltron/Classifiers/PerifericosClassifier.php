<?php

namespace App\Services\Deltron\Classifiers;

use App\Services\Deltron\Contracts\DeltronCategoryClassifierInterface;
use App\Services\Deltron\DeltronClassifierService;

/**
 * Clasificador y normalizador modular de Deltron para la Categoría 4: PERIFÉRICOS.
 *
 * Alinea la clasificación de grupos y el mapeo de características del ERP con los Mappers
 * de Falabella (como MouseMapper), garantizando que al descargar la plantilla Excel de Falabella
 * todos los campos obligatorios (Color, Conexión, Segmento, DPI, Sensor, Botones, Alto, Ancho, Largo, Peso)
 * estén listos para publicar sin errores de validación.
 */
class PerifericosClassifier implements DeltronCategoryClassifierInterface
{
    public const CATEGORIA_ID = 4; // Periféricos

    public const GRUPO_TECLADOS        = 24;
    public const GRUPO_MOUSES          = 25;
    public const GRUPO_AURICULARES     = 26;
    public const GRUPO_PARLANTES       = 27;
    public const GRUPO_ESTABILIZADORES = 28;
    public const GRUPO_UPS             = 29;
    public const GRUPO_ALFOMBRILLAS    = 31;
    public const GRUPO_GRABADORAS      = 33;
    public const GRUPO_WEBCAM          = 34;
    public const GRUPO_MOCHILAS        = 35;
    public const GRUPO_MALETAS         = 36;
    public const GRUPO_COMBOS          = 85;
    public const GRUPO_MICROFONO       = 95;

    public function getCategoriaId(): int
    {
        return self::CATEGORIA_ID;
    }

    public function getGruposIds(): array
    {
        return [
            self::GRUPO_TECLADOS,
            self::GRUPO_MOUSES,
            self::GRUPO_AURICULARES,
            self::GRUPO_PARLANTES,
            self::GRUPO_ESTABILIZADORES,
            self::GRUPO_UPS,
            self::GRUPO_ALFOMBRILLAS,
            self::GRUPO_GRABADORAS,
            self::GRUPO_WEBCAM,
            self::GRUPO_MOCHILAS,
            self::GRUPO_MALETAS,
            self::GRUPO_COMBOS,
            self::GRUPO_MICROFONO,
        ];
    }

    public function supportsGrupo(int $idGrupo): bool
    {
        return in_array($idGrupo, $this->getGruposIds(), true);
    }

    /**
     * Reglas de prioridad temprana:
     * 1. Mochilas / Maletas (antes de Laptops para no clasificar "Mochila para laptop 15.6" como Laptop).
     * 2. Combos Teclado + Mouse (antes de Teclados o Mouses individuales).
     */
    public function classifyEarly(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';

        // 1. MOCHILAS Y MALETAS (35 / 36)
        if (
            str_contains($catDeltron, 'MOCHILA') ||
            str_contains($subDeltron, 'MOCHILA') ||
            str_contains($titulo, 'BACKPACK') ||
            str_contains($titulo, 'MOCHILA') ||
            str_contains($catDeltron, 'ACCESORIOS DE VIAJE') ||
            str_contains($titulo, 'MALETA') ||
            str_contains($titulo, 'MALETIN') ||
            str_contains($titulo, 'MALETÍN') ||
            str_contains($subDeltron, 'MALETA')
        ) {
            if (
                str_contains($titulo, 'MALETA') ||
                str_contains($titulo, 'MALETIN') ||
                str_contains($titulo, 'MALETÍN') ||
                str_contains($titulo, 'BRIEFCASE') ||
                str_contains($subDeltron, 'MALETA')
            ) {
                return self::GRUPO_MALETAS; // 36
            }
            return self::GRUPO_MOCHILAS; // 35
        }

        // 2. COMBOS / KITS TECLADO + MOUSE (85)
        if (
            str_contains($subDeltron, 'TECLADO+MOUSE') ||
            str_contains($subDeltron, 'KIT INALAMB') ||
            (str_contains($titulo, 'TECLADO') && (str_contains($titulo, 'MOUSE') || str_contains($titulo, 'MSE'))) ||
            str_contains($titulo, 'COMBO KIT') ||
            str_contains($titulo, 'KIT GAMER') ||
            str_contains($titulo, 'COMBO TECLADO') ||
            str_starts_with($name, 'KBMS') ||
            str_starts_with($name, 'KB+MSE')
        ) {
            return self::GRUPO_COMBOS; // 85
        }

        return null;
    }

    /**
     * Clasificación principal para todos los demás grupos de Periféricos.
     */
    public function classify(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';

        // 1. WEBCAMS / CÁMARAS DE VIDEOCONFERENCIA (34)
        if (
            str_contains($catDeltron, 'VIDEOCONFERENCIA') ||
            str_contains($subDeltron, 'VIDEOCONFERENCIA') ||
            str_contains($titulo, 'WEBCAM') ||
            str_contains($titulo, 'CAMARA WEB') ||
            str_contains($titulo, 'CÁMARA WEB')
        ) {
            return self::GRUPO_WEBCAM; // 34
        }

        // 2. LECTORAS Y GRABADORAS CD / DVD / BLU-RAY (33)
        if (
            str_contains($catDeltron, 'CDROM') ||
            str_contains($catDeltron, 'DVD') ||
            str_contains($catDeltron, 'BLU RAY') ||
            str_contains($subDeltron, 'DVD') ||
            str_contains($titulo, 'DVD-WRITER') ||
            str_contains($titulo, 'GRABADORA EXTERNA')
        ) {
            return self::GRUPO_GRABADORAS; // 33
        }

        // 3. UPS Y ESTABILIZADORES (29 / 28)
        if (
            str_contains($catDeltron, 'UPS') ||
            str_contains($catDeltron, 'ESTABILIZADORES') ||
            str_contains($subDeltron, 'UPS') ||
            str_contains($subDeltron, 'ESTABILIZADOR') ||
            str_contains($titulo, 'ESTABILIZADOR')
        ) {
            if (
                str_contains($subDeltron, 'ESTABILIZADOR') ||
                str_contains($titulo, 'ESTABILIZADOR') ||
                (str_contains($titulo, 'AVR') && !str_contains($titulo, 'UPS'))
            ) {
                return self::GRUPO_ESTABILIZADORES; // 28
            }
            return self::GRUPO_UPS; // 29
        }

        // 4. AUDIO: PARLANTES (27), MICRÓFONOS (95), AURICULARES (26)
        if (
            str_contains($catDeltron, 'AUDIO') ||
            str_contains($subDeltron, 'AUDIO') ||
            str_contains($titulo, 'AUDIFONO') ||
            str_contains($titulo, 'AUDÍFONO') ||
            str_contains($titulo, 'HEADSET') ||
            str_contains($titulo, 'AURICULAR') ||
            str_contains($titulo, 'PARLANTE') ||
            str_contains($titulo, 'SPEAKER') ||
            str_contains($titulo, 'MICROFONO') ||
            str_contains($titulo, 'MICRÓFONO') ||
            str_contains($titulo, 'TWS') ||
            str_contains($name, 'EARB')
        ) {
            if (
                str_contains($titulo, 'PARLANTE') ||
                str_contains($titulo, 'SPEAKER') ||
                str_contains($titulo, 'SOUNDBAR') ||
                str_contains($titulo, 'BARRA DE SONIDO') ||
                str_contains($subDeltron, 'PARLANTE')
            ) {
                return self::GRUPO_PARLANTES; // 27
            }

            if (
                (str_contains($titulo, 'MICROFONO') || str_contains($titulo, 'MICRÓFONO') || str_contains($subDeltron, 'MICROFONO')) &&
                !str_contains($titulo, 'AUDIFONO') &&
                !str_contains($titulo, 'AUDÍFONO') &&
                !str_contains($titulo, 'HEADSET') &&
                !str_contains($titulo, 'AURICULAR')
            ) {
                return self::GRUPO_MICROFONO; // 95
            }

            return self::GRUPO_AURICULARES; // 26
        }

        // 5. ALFOMBRILLAS / MOUSEPAD (31)
        if (
            str_contains($catDeltron, 'PAD') ||
            str_contains($subDeltron, 'PAD') ||
            str_contains($titulo, 'MOUSEPAD') ||
            str_contains($titulo, 'MOUSE PAD') ||
            str_contains($titulo, 'ALFOMBR')
        ) {
            return self::GRUPO_ALFOMBRILLAS; // 31
        }

        // 6. TECLADOS (24)
        if (
            str_contains($catDeltron, 'TECLADO') ||
            str_contains($subDeltron, 'TECLADO') ||
            str_contains($titulo, 'TECLADO') ||
            str_contains($titulo, 'KEYBOARD')
        ) {
            return self::GRUPO_TECLADOS; // 24
        }

        // 7. MOUSES (25)
        if (
            str_contains($catDeltron, 'MOUSE') ||
            str_contains($subDeltron, 'MOUSE') ||
            str_contains($titulo, 'MOUSE') ||
            str_contains($titulo, 'RATON') ||
            str_contains($titulo, 'RATÓN')
        ) {
            return self::GRUPO_MOUSES; // 25
        }

        return null;
    }

    /**
     * Características permitidas/requeridas por grupo en Periféricos para que el ERP y Falabella
     * dispongan siempre de todas las especificaciones necesarias.
     */
    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        // Base universal para cualquier periférico (Dimensiones, Peso, Color, Conexión, Segmento, Material)
        $base = [9, 10, 11, 12, 13, 14, 26, 27, 50, 53, 63, 72, 91];

        $especificas = match ($idGrupo) {
            self::GRUPO_MOUSES          => [58, 59, 60, 69, 16],         // DPI, Sensor, Botones, Led, Batería
            self::GRUPO_TECLADOS        => [25, 28, 29, 30, 69, 16],     // Idioma, Nº teclas, Diseño, Tipo teclado, Led, Batería
            self::GRUPO_COMBOS          => [25, 29, 30, 58, 59, 60, 69], // Teclado + Mouse specs
            self::GRUPO_AURICULARES     => [1, 16, 42, 64, 67, 69],      // Hz, Batería, Potencia, Impedancia, Micrófono, Led
            self::GRUPO_PARLANTES       => [1, 16, 17, 42, 64, 69],      // Hz, Batería, Puertos, Potencia, Impedancia, Led
            self::GRUPO_MICROFONO       => [1, 17, 64, 67, 69],          // Hz, Puertos, Impedancia, Micrófono, Led
            self::GRUPO_UPS,
            self::GRUPO_ESTABILIZADORES => [16, 17, 42, 45, 55],         // Batería, Puertos/Salidas, Potencia, Capacidad, Consumo
            self::GRUPO_WEBCAM          => [1, 3, 23, 67],               // Hz/FPS, Resolución, Ángulo visión, Micrófono
            self::GRUPO_ALFOMBRILLAS    => [29, 69],                     // Diseño/Tamaño, Led
            self::GRUPO_MOCHILAS,
            self::GRUPO_MALETAS         => [2, 29, 45],                  // Pulgadas, Diseño, Capacidad
            self::GRUPO_GRABADORAS      => [46, 47, 48],                 // Interfaz, Vel. rotación, Vel. transferencia
            default                     => [],
        };

        return array_values(array_unique(array_merge($base, $especificas)));
    }

    /**
     * Enriquecimiento y normalización alineada con Falabella Mappers (ej. MouseMapper) y ERP.
     */
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

        // 1. Normalizar e inferir COLOR (ID 14) compatible con Falabella
        $colorActual = $specsMapeadas[14] ?? ($rawSpecs['COLOR'] ?? '');
        $specsMapeadas[14] = $this->normalizarColorFalabella((string) $colorActual, $textoAnalisis);

        // 2. Normalizar e inferir CONEXIÓN (ID 72), CONECTIVIDAD (ID 9) y CABLEADO (ID 26)
        $conexionRaw = ($specsMapeadas[72] ?? '') . ' ' . ($specsMapeadas[9] ?? '') . ' ' . ($specsMapeadas[26] ?? '') . ' ' . $textoAnalisis;
        $conexionNorm = $this->normalizarConexionFalabella($conexionRaw);
        $specsMapeadas[72] = $conexionNorm; // 'Bluetooth' | 'Inalámbrico' | 'USB'
        if (empty($specsMapeadas[9])) {
            $specsMapeadas[9] = $conexionNorm;
        }
        if (empty($specsMapeadas[26])) {
            $specsMapeadas[26] = ($conexionNorm === 'USB') ? 'Alámbrico' : 'Inalámbrico';
        }

        // 3. Normalizar e inferir SEGMENTO (ID 91) compatible con Falabella ('Gamer', 'Productividad', 'Estudiante', 'Creador de Contenido')
        $segRaw = ($specsMapeadas[91] ?? '') . ' ' . ($specsMapeadas[50] ?? '') . ' ' . $textoAnalisis;
        $specsMapeadas[91] = $this->normalizarSegmentoFalabella($segRaw);

        // 4. Normalizar e inferir LED / RGB (ID 69)
        if (!empty($specsMapeadas[69]) || preg_match('/\b(RGB|ARGB|RETROILUMINAD|ILUMINACION LED|LED)\b/i', $textoAnalisis)) {
            $ledRaw = ($specsMapeadas[69] ?? '') . ' ' . $textoAnalisis;
            if (preg_match('/\b(SIN RETRO|SIN RGB|SIN LUZ|NO RGB)\b/i', $ledRaw)) {
                $specsMapeadas[69] = 'Sin RGB';
            } elseif (preg_match('/\b(RGB|ARGB|RAINBOW|MULTICOLOR|LED)\b/i', $ledRaw)) {
                $specsMapeadas[69] = 'RGB';
            }
        }

        // 5. Reglas específicas por Grupo de Periférico
        switch ($idGrupo) {
            case self::GRUPO_MOUSES:
                $specsMapeadas = $this->enrichMouseSpecs($specsMapeadas, $rawSpecs, $textoAnalisis);
                break;

            case self::GRUPO_TECLADOS:
                $specsMapeadas = $this->enrichTecladoSpecs($specsMapeadas, $rawSpecs, $textoAnalisis);
                break;

            case self::GRUPO_COMBOS:
                $specsMapeadas = $this->enrichTecladoSpecs($specsMapeadas, $rawSpecs, $textoAnalisis);
                $specsMapeadas = $this->enrichMouseSpecs($specsMapeadas, $rawSpecs, $textoAnalisis);
                break;

            case self::GRUPO_AURICULARES:
            case self::GRUPO_PARLANTES:
            case self::GRUPO_MICROFONO:
                $specsMapeadas = $this->enrichAudioSpecs($idGrupo, $specsMapeadas, $rawSpecs, $textoAnalisis);
                break;

            case self::GRUPO_UPS:
            case self::GRUPO_ESTABILIZADORES:
                $specsMapeadas = $this->enrichEnergiaSpecs($specsMapeadas, $rawSpecs, $textoAnalisis);
                break;

            case self::GRUPO_WEBCAM:
                $specsMapeadas = $this->enrichWebcamSpecs($specsMapeadas, $rawSpecs, $textoAnalisis);
                break;

            case self::GRUPO_MOCHILAS:
            case self::GRUPO_MALETAS:
                $specsMapeadas = $this->enrichMochilaSpecs($specsMapeadas, $rawSpecs, $textoAnalisis);
                break;
        }

        // 6. Normalizar y garantizar DIMENSIONES (Alto 11, Ancho 12, Largo 13 en cm) y PESO (10 en kg)
        // para que al exportar el Excel de Falabella nunca salten errores por medidas vacías o en 0.
        $specsMapeadas = $this->ensureDimensionesYPesoFalabella($idGrupo, $specsMapeadas, $rawSpecs, $textoAnalisis);

        return $specsMapeadas;
    }

    /**
     * Reglas de enriquecimiento para Mouses (idGrupo = 25), 100% alineadas con App\Services\Falabella\Mappers\MouseMapper.
     */
    protected function enrichMouseSpecs(array $specs, array $rawSpecs, string $textoAnalisis): array
    {
        $esGamer = ($specs[91] ?? '') === 'Gamer';

        // DPI (ID 58): formato requerido por MouseMapper "XXXX DPI"
        $dpiRaw = ($specs[58] ?? '') . ' ' . ($rawSpecs['DPI'] ?? '') . ' ' . ($rawSpecs['RESOLUCIÓN'] ?? '') . ' ' . $textoAnalisis;
        if (preg_match_all('/\b(\d{3,5})\s*DPI\b/i', $dpiRaw, $mDpi)) {
            $nums = array_map('intval', $mDpi[1]);
            rsort($nums);
            $specs[58] = $nums[0] . ' DPI';
        } elseif (!empty($specs[58]) && preg_match('/\b(\d{3,5})\b/', (string) $specs[58], $mNum)) {
            $specs[58] = $mNum[1] . ' DPI';
        } else {
            $specs[58] = $esGamer ? '3200 DPI' : '1200 DPI';
        }

        // Sensor (ID 59): opciones válidas Falabella ['Óptico', 'Láser', 'BlueTrack', 'Infrarrojo', 'Dual']
        $sensorRaw = mb_strtolower(($specs[59] ?? '') . ' ' . ($rawSpecs['SENSOR'] ?? '') . ' ' . $textoAnalisis);
        if (str_contains($sensorRaw, 'laser') || str_contains($sensorRaw, 'láser')) {
            $specs[59] = 'Láser';
        } elseif (str_contains($sensorRaw, 'bluetrack') || str_contains($sensorRaw, 'blue track')) {
            $specs[59] = 'BlueTrack';
        } elseif (str_contains($sensorRaw, 'infrarrojo')) {
            $specs[59] = 'Infrarrojo';
        } elseif (str_contains($sensorRaw, 'dual')) {
            $specs[59] = 'Dual';
        } else {
            $specs[59] = 'Óptico';
        }

        // Botones (ID 60): entero numérico como string (ej. '3', '6', '7')
        $botonesRaw = ($specs[60] ?? '') . ' ' . ($rawSpecs['BOTONES'] ?? '') . ' ' . ($rawSpecs['CANTIDAD DE BOTONES'] ?? '');
        if (preg_match('/\b(\d{1,2})\b/', $botonesRaw, $mBot)) {
            $specs[60] = $mBot[1];
        } elseif (preg_match('/\b(\d{1,2})\s*(?:BOTONES|BUTTONS|BTN)\b/i', $textoAnalisis, $mBotTxt)) {
            $specs[60] = $mBotTxt[1];
        } else {
            $specs[60] = $esGamer ? '6' : '3';
        }

        return $specs;
    }

    /**
     * Reglas de enriquecimiento para Teclados (idGrupo = 24) y Combos (idGrupo = 85).
     */
    protected function enrichTecladoSpecs(array $specs, array $rawSpecs, string $textoAnalisis): array
    {
        // Tipo de teclado (ID 30): 'Mecánico' | 'Membrana'
        $tipoRaw = ($specs[30] ?? '') . ' ' . $textoAnalisis;
        if (preg_match('/\b(MECANIC|MECÁNIC|MECHANICAL|SWITCH\s+(?:RED|BLUE|BROWN|OUTEMU|KAILH|CHERRY))\b/i', $tipoRaw)) {
            $specs[30] = 'Mecánico';
        } else {
            $specs[30] = 'Membrana';
        }

        // Idioma / Distribución (ID 25): 'Español' | 'Inglés'
        $idiomaRaw = ($specs[25] ?? '') . ' ' . ($rawSpecs['IDIOMA'] ?? '') . ' ' . ($rawSpecs['DISTRIBUCION'] ?? '') . ' ' . $textoAnalisis;
        if (preg_match('/\b(INGLES|INGLÉS|ENGLISH|US\s+LAYOUT)\b/i', $idiomaRaw) && !preg_match('/\b(ESPAÑOL|ESPANOL|SPANISH|LATINO)\b/i', $idiomaRaw)) {
            $specs[25] = 'Inglés';
        } else {
            $specs[25] = 'Español';
        }

        // Diseño / Formato (ID 29)
        if (empty($specs[29])) {
            if (preg_match('/\b(60%|MINI|COMPACTO)\b/i', $textoAnalisis)) {
                $specs[29] = 'Compacto (60%)';
            } elseif (preg_match('/\b(TKL|TENKEYLESS|80%|87\s*TECLAS)\b/i', $textoAnalisis)) {
                $specs[29] = 'TKL (80%)';
            } elseif (preg_match('/\b(ERGONOMIC|ERGONÓMIC)\b/i', $textoAnalisis)) {
                $specs[29] = 'Ergonómico';
            } else {
                $specs[29] = 'Estándar';
            }
        }

        return $specs;
    }

    /**
     * Reglas de enriquecimiento para Auriculares (26), Parlantes (27) y Micrófonos (95).
     */
    protected function enrichAudioSpecs(int $idGrupo, array $specs, array $rawSpecs, string $textoAnalisis): array
    {
        // Micrófono (ID 67)
        if ($idGrupo === self::GRUPO_MICROFONO) {
            $specs[67] = 'Sí';
        } elseif ($idGrupo === self::GRUPO_AURICULARES && empty($specs[67])) {
            if (preg_match('/\b(HEADSET|CON MICROFONO|CON MICRÓFONO|MIC\b|HANDSFREE|MANOS LIBRES|TWS|GAMER)\b/i', $textoAnalisis)) {
                $specs[67] = 'Integrado';
            }
        }

        // Impedancia (ID 64): ej. "32 Ohm"
        if (empty($specs[64]) && preg_match('/\b(\d{1,3})\s*(?:OHM|Ω)\b/i', $textoAnalisis, $mOhm)) {
            $specs[64] = $mOhm[1] . ' Ohm';
        }

        // Potencia RMS (ID 42): ej. "10 W"
        if (empty($specs[42]) && preg_match('/\b(\d{1,4}(?:\.\d+)?)\s*W(?:ATTS?|RMS)?\b/i', $textoAnalisis, $mW)) {
            $specs[42] = $mW[1] . ' W';
        }

        return $specs;
    }

    /**
     * Reglas de enriquecimiento para UPS (29) y Estabilizadores (28).
     */
    protected function enrichEnergiaSpecs(array $specs, array $rawSpecs, string $textoAnalisis): array
    {
        // Capacidad VA (ID 45) y Potencia W (ID 42)
        if (preg_match('/\b(\d{3,5})\s*VA\b/i', $textoAnalisis, $mVa)) {
            if (empty($specs[45])) {
                $specs[45] = $mVa[1] . ' VA';
            }
            if (empty($specs[42])) {
                $specs[42] = $mVa[1] . ' VA';
            }
        }
        if (preg_match('/\b(\d{3,4})\s*W\b/i', $textoAnalisis, $mW)) {
            $specs[42] = isset($mVa[1]) ? ($mVa[1] . 'VA / ' . $mW[1] . 'W') : ($mW[1] . ' W');
        }

        return $specs;
    }

    /**
     * Reglas de enriquecimiento para Webcams (34).
     */
    protected function enrichWebcamSpecs(array $specs, array $rawSpecs, string $textoAnalisis): array
    {
        if (empty($specs[3])) {
            if (preg_match('/\b(4K|3840\s*x\s*2160|UHD)\b/i', $textoAnalisis)) {
                $specs[3] = '3840 x 2160';
            } elseif (preg_match('/\b(2K|QHD|2560\s*x\s*1440)\b/i', $textoAnalisis)) {
                $specs[3] = '2560 x 1440';
            } elseif (preg_match('/\b(1080P|FHD|FULL\s*HD|1920\s*x\s*1080)\b/i', $textoAnalisis)) {
                $specs[3] = '1920 x 1080';
            } elseif (preg_match('/\b(720P|HD|1280\s*x\s*720)\b/i', $textoAnalisis)) {
                $specs[3] = '1280 x 720';
            }
        }
        if (empty($specs[67])) {
            $specs[67] = 'Integrado';
        }
        return $specs;
    }

    /**
     * Reglas de enriquecimiento para Mochilas (35) y Maletas (36).
     */
    protected function enrichMochilaSpecs(array $specs, array $rawSpecs, string $textoAnalisis): array
    {
        if (empty($specs[2]) && preg_match('/\b(13(?:\.3)?|14|15(?:\.6)?|16|17(?:\.3)?)\s*(?:PULG|"|\'\'|PULGADAS)?\b/i', $textoAnalisis, $mPulg)) {
            $specs[2] = $mPulg[1] . '"';
        }
        if (empty($specs[53])) {
            if (preg_match('/\b(NYLON|NAILON)\b/i', $textoAnalisis)) {
                $specs[53] = 'Nylon';
            } else {
                $specs[53] = 'Poliéster';
            }
        }
        return $specs;
    }

    /**
     * Normaliza el color exactamente al catálogo de valores permitidos por Falabella:
     * ['Amarillo', 'Azul', 'Beige', 'Blanco', 'Burdeo', 'Café', 'Gris', 'Plateado', 'Morado', 'Naranjo', 'Dorado', 'Negro', 'Rojo', 'Rosado', 'Verde']
     */
    public function normalizarColorFalabella(string $rawColor, string $textoAnalisis = ''): string
    {
        $fuente = mb_strtolower(trim($rawColor . ' ' . $textoAnalisis));

        $mapaColores = [
            'negro'     => 'Negro',
            'black'     => 'Negro',
            'blk'       => 'Negro',
            'blanco'    => 'Blanco',
            'white'     => 'Blanco',
            'wht'       => 'Blanco',
            'azul'      => 'Azul',
            'blue'      => 'Azul',
            'navy'      => 'Azul',
            'celeste'   => 'Azul',
            'cyan'      => 'Azul',
            'rojo'      => 'Rojo',
            'red'       => 'Rojo',
            'rosado'    => 'Rosado',
            'rosa'      => 'Rosado',
            'pink'      => 'Rosado',
            'rose gold' => 'Rosado',
            'rosegold'  => 'Rosado',
            'fucsia'    => 'Rosado',
            'verde'     => 'Verde',
            'green'     => 'Verde',
            'menta'     => 'Verde',
            'gris'      => 'Gris',
            'grey'      => 'Gris',
            'gray'      => 'Gris',
            'grafito'   => 'Gris',
            'carbón'    => 'Gris',
            'carbon'    => 'Gris',
            'plateado'  => 'Plateado',
            'plata'     => 'Plateado',
            'silver'    => 'Plateado',
            'morado'    => 'Morado',
            'lila'      => 'Morado',
            'violeta'   => 'Morado',
            'purple'    => 'Morado',
            'lilac'     => 'Morado',
            'naranjo'   => 'Naranjo',
            'naranja'   => 'Naranjo',
            'orange'    => 'Naranjo',
            'dorado'    => 'Dorado',
            'gold'      => 'Dorado',
            'amarillo'  => 'Amarillo',
            'yellow'    => 'Amarillo',
            'beige'     => 'Beige',
            'crema'     => 'Beige',
            'arena'     => 'Beige',
            'café'      => 'Café',
            'cafe'      => 'Café',
            'marrón'    => 'Café',
            'marron'    => 'Café',
            'brown'     => 'Café',
            'burdeo'    => 'Burdeo',
            'guinda'    => 'Burdeo',
            'vino'      => 'Burdeo',
        ];

        // 1. Buscar primero en el valor explícito de Color si existe
        if (trim($rawColor) !== '') {
            $rawLower = mb_strtolower(trim($rawColor));
            foreach ($mapaColores as $needle => $falabellaColor) {
                if (preg_match('/\b' . preg_quote($needle, '/') . '\b/u', $rawLower)) {
                    return $falabellaColor;
                }
            }
        }

        // 2. Buscar en el texto general (nombre / descripción / ficha)
        foreach ($mapaColores as $needle => $falabellaColor) {
            if (preg_match('/\b' . preg_quote($needle, '/') . '\b/u', $fuente)) {
                return $falabellaColor;
            }
        }

        return 'Negro';
    }

    /**
     * Normaliza la conexión exactamente a los valores esperados por Falabella ('Bluetooth', 'Inalámbrico', 'USB').
     */
    public function normalizarConexionFalabella(string $rawConexion): string
    {
        $c = mb_strtolower($rawConexion);

        if (str_contains($c, 'bluetooth') || preg_match('/\bbt\b/', $c)) {
            return 'Bluetooth';
        }
        if (
            str_contains($c, 'inalambric') ||
            str_contains($c, 'inalámbric') ||
            str_contains($c, 'wireless') ||
            str_contains($c, '2.4ghz') ||
            str_contains($c, '2.4 ghz') ||
            str_contains($c, 'receptor usb') ||
            str_contains($c, 'nano receptor') ||
            str_contains($c, 'tws')
        ) {
            return 'Inalámbrico';
        }

        return 'USB';
    }

    /**
     * Normaliza el segmento exactamente a las opciones de Falabella ('Gamer', 'Creador de Contenido', 'Estudiante', 'Productividad').
     */
    public function normalizarSegmentoFalabella(string $rawSegmento): string
    {
        $s = mb_strtolower($rawSegmento);

        if (
            str_contains($s, 'gamer') ||
            str_contains($s, 'gaming') ||
            str_contains($s, 'juego') ||
            str_contains($s, 'rgb') ||
            str_contains($s, 'mecánic') ||
            str_contains($s, 'mecanic')
        ) {
            return 'Gamer';
        }
        if (str_contains($s, 'creador') || str_contains($s, 'streaming') || str_contains($s, 'streamer')) {
            return 'Creador de Contenido';
        }
        if (str_contains($s, 'estudiant') || str_contains($s, 'escolar')) {
            return 'Estudiante';
        }

        return 'Productividad';
    }

    /**
     * Garantiza que Alto (11), Ancho (12), Largo (13) estén en cm y Peso (10) en kg,
     * aplicando conversión de unidades (mm -> cm, g -> kg) y valores por defecto realistas
     * por subgrupo si la ficha de Deltron no los incluye.
     */
    protected function ensureDimensionesYPesoFalabella(int $idGrupo, array $specs, array $rawSpecs, string $textoAnalisis): array
    {
        // Si alguna dimensión vino en MM, convertirla a CM para Falabella
        foreach ([11, 12, 13] as $idDim) {
            if (!empty($specs[$idDim])) {
                $val = (string) $specs[$idDim];
                if (preg_match('/([\d\.]+)\s*mm/i', $val, $mMm)) {
                    $cm = round(floatval($mMm[1]) / 10, 2);
                    $specs[$idDim] = $cm . ' cm';
                } elseif (preg_match('/([\d\.]+)/', $val, $mNum)) {
                    $num = floatval($mNum[1]);
                    // Si el número es > 80 en un periférico pequeño (ej. 120 por 120mm sin sufijo), pasarlo a cm
                    if ($num > 65 && in_array($idGrupo, [self::GRUPO_MOUSES, self::GRUPO_WEBCAM], true)) {
                        $num = round($num / 10, 2);
                    }
                    $specs[$idDim] = $num . ' cm';
                }
            }
        }

        // Normalizar Peso (ID 10) en kg
        if (!empty($specs[10])) {
            $pesoVal = (string) $specs[10];
            if (preg_match('/([\d\.]+)\s*(g|gr|gramos)\b/i', $pesoVal, $mGr)) {
                $specs[10] = (string) round(floatval($mGr[1]) / 1000, 3);
            } elseif (preg_match('/([\d\.]+)/', $pesoVal, $mP)) {
                $numP = floatval($mP[1]);
                // Si dice ej. "85" en un mouse/teclado/audífono, está en gramos -> convertir a kg
                if ($numP >= 15 && !in_array($idGrupo, [self::GRUPO_UPS, self::GRUPO_ESTABILIZADORES], true)) {
                    $numP = round($numP / 1000, 3);
                }
                if ($numP > 0) {
                    $specs[10] = (string) $numP;
                } else {
                    unset($specs[10]);
                }
            }
        }

        // Defaults por grupo de periférico cuando Deltron no reporta medidas o peso
        $defaults = match ($idGrupo) {
            self::GRUPO_MOUSES          => [11 => '4 cm',   12 => '6.5 cm', 13 => '12 cm',   10 => '0.1'],
            self::GRUPO_TECLADOS        => [11 => '3.5 cm', 12 => '14 cm',  13 => '44 cm',   10 => '0.55'],
            self::GRUPO_COMBOS          => [11 => '4.5 cm', 12 => '16 cm',  13 => '46 cm',   10 => '0.7'],
            self::GRUPO_AURICULARES     => [11 => '18 cm',  12 => '16 cm',  13 => '8 cm',    10 => '0.25'],
            self::GRUPO_PARLANTES       => [11 => '15 cm',  12 => '10 cm',  13 => '10 cm',   10 => '0.5'],
            self::GRUPO_MICROFONO       => [11 => '16 cm',  12 => '7 cm',   13 => '7 cm',    10 => '0.3'],
            self::GRUPO_ESTABILIZADORES => [11 => '9 cm',   12 => '12 cm',  13 => '22 cm',   10 => '1.2'],
            self::GRUPO_UPS             => [11 => '14 cm',  12 => '10 cm',  13 => '28 cm',   10 => '4.2'],
            self::GRUPO_ALFOMBRILLAS    => [11 => '0.3 cm', 12 => '25 cm',  13 => '30 cm',   10 => '0.15'],
            self::GRUPO_GRABADORAS      => [11 => '1.5 cm', 12 => '14 cm',  13 => '14.5 cm', 10 => '0.25'],
            self::GRUPO_WEBCAM          => [11 => '6 cm',   12 => '7 cm',   13 => '5 cm',    10 => '0.15'],
            self::GRUPO_MOCHILAS,
            self::GRUPO_MALETAS         => [11 => '45 cm',  12 => '31 cm',  13 => '15 cm',   10 => '0.65'],
            default                     => [11 => '5 cm',   12 => '10 cm',  13 => '15 cm',   10 => '0.2'],
        };

        foreach ($defaults as $idCar => $defVal) {
            if (empty($specs[$idCar]) || trim((string) $specs[$idCar]) === '0') {
                $specs[$idCar] = $defVal;
            }
        }

        return $specs;
    }
}

