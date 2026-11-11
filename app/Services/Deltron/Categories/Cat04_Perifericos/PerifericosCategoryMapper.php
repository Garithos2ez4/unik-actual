<?php

namespace App\Services\Deltron\Categories\Cat04_Perifericos;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class PerifericosCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 4;
    public const CATEGORIA_NOMBRE = 'Perifericos';

    public const GRUPO_TECLADOS        = 24;
    public const GRUPO_MOUSES          = 25;
    public const GRUPO_AURICULARES     = 26;
    public const GRUPO_PARLANTES       = 27;
    public const GRUPO_ESTABILIZADORES = 28;
    public const GRUPO_UPS             = 29;
    public const GRUPO_CONTROLES       = 30;
    public const GRUPO_ALFOMBRILLAS    = 31;
    public const GRUPO_LECTORAS        = 32;
    public const GRUPO_GRABADORAS      = 33;
    public const GRUPO_WEBCAM          = 34;
    public const GRUPO_MOCHILAS        = 35;
    public const GRUPO_MALETAS         = 36;
    public const GRUPO_SILLAS_GAMER    = 37;
    public const GRUPO_MEMORIAS_USB    = 38;
    public const GRUPO_MEMORIAS_SD     = 39;
    public const GRUPO_COMBOS          = 85;
    public const GRUPO_MICROFONO       = 95;
    public const GRUPO_CLONADOR_DISCO  = 178;
    public const GRUPO_MESA_PORTATIL   = 213;

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
            self::GRUPO_TECLADOS,
            self::GRUPO_MOUSES,
            self::GRUPO_AURICULARES,
            self::GRUPO_PARLANTES,
            self::GRUPO_ESTABILIZADORES,
            self::GRUPO_UPS,
            self::GRUPO_CONTROLES,
            self::GRUPO_ALFOMBRILLAS,
            self::GRUPO_LECTORAS,
            self::GRUPO_GRABADORAS,
            self::GRUPO_WEBCAM,
            self::GRUPO_MOCHILAS,
            self::GRUPO_MALETAS,
            self::GRUPO_SILLAS_GAMER,
            self::GRUPO_MEMORIAS_USB,
            self::GRUPO_MEMORIAS_SD,
            self::GRUPO_COMBOS,
            self::GRUPO_MICROFONO,
            self::GRUPO_CLONADOR_DISCO,
            self::GRUPO_MESA_PORTATIL,
        ];
    }

    public function classifyEarly(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';

        // 1. Auriculares / Audífonos / Headsets
        if (
            str_contains($catDeltron, 'AURICULAR') || str_contains($catDeltron, 'AUDIFONO') ||
            str_contains($subDeltron, 'AURICULAR') || str_contains($subDeltron, 'AUDIFONO') ||
            str_contains($titulo, 'AURICULAR') || str_contains($titulo, 'AUDIFONO') || str_contains($titulo, 'AUDÍFONO') ||
            str_contains($titulo, 'HEADSET') || str_starts_with($name, 'HS ') || str_starts_with($name, 'HEADSET')
        ) {
            return self::GRUPO_AURICULARES;
        }

        // 2. Mochilas / Maletas
        $sku = strtoupper($context['item']['sku'] ?? '');
        if (
            str_contains($catDeltron, 'MOCHILA') ||
            str_contains($subDeltron, 'MOCHILA') ||
            str_contains($subDeltron, 'BACKPACK') ||
            str_contains($titulo, 'BACKPACK') ||
            str_contains($titulo, 'MOCHILA') ||
            str_contains($name, 'BACKPACK') ||
            str_starts_with($name, 'BP ') ||
            str_starts_with($sku, 'NBACK') ||
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
                return self::GRUPO_MALETAS;
            }
            return self::GRUPO_MOCHILAS;
        }

        // 2. Combos Teclado + Mouse
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
            return self::GRUPO_COMBOS;
        }

        return null;
    }

    public function classify(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';

        // 1. Memorias Flash / USB / SD
        if (str_contains($catDeltron, 'MEMORIAS FLASH') || str_contains($subDeltron, 'MEM FLASH') || str_contains($name, 'USB DRIVE')) {
            if (str_contains($subDeltron, 'SECURE DIGITAL') || str_contains($titulo, 'MICROSD') || str_contains($titulo, 'MEMORIA SD')) {
                return self::GRUPO_MEMORIAS_SD;
            }
            return self::GRUPO_MEMORIAS_USB;
        }

        // 2. Sillas Gamer
        if (str_contains($catDeltron, 'SILLA') || str_contains($titulo, 'SILLA GAMER') || str_contains($titulo, 'SILLA')) {
            return self::GRUPO_SILLAS_GAMER;
        }

        // 3. Webcams
        if (
            str_contains($catDeltron, 'VIDEOCONFERENCIA') ||
            str_contains($subDeltron, 'VIDEOCONFERENCIA') ||
            str_contains($titulo, 'WEBCAM') ||
            str_contains($titulo, 'CAMARA WEB') ||
            str_contains($titulo, 'CÁMARA WEB')
        ) {
            return self::GRUPO_WEBCAM;
        }

        // 4. Lectoras / Grabadoras
        if (
            str_contains($catDeltron, 'CDROM') ||
            str_contains($catDeltron, 'DVD') ||
            str_contains($catDeltron, 'BLU RAY') ||
            str_contains($subDeltron, 'DVD') ||
            str_contains($titulo, 'DVD-WRITER') ||
            str_contains($titulo, 'GRABADORA EXTERNA')
        ) {
            return self::GRUPO_GRABADORAS;
        }

        // 5. UPS / Estabilizadores
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
                return self::GRUPO_ESTABILIZADORES;
            }
            return self::GRUPO_UPS;
        }

        // 6. Audio
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
            str_contains($name, 'AUDIFONO') ||
            str_contains($name, 'HEADSET') ||
            str_contains($name, 'AURICULAR') ||
            str_contains($name, 'EARB')
        ) {
            if (
                str_contains($titulo, 'PARLANTE') ||
                str_contains($titulo, 'SPEAKER') ||
                str_contains($titulo, 'SOUNDBAR') ||
                str_contains($titulo, 'BARRA DE SONIDO') ||
                str_contains($subDeltron, 'PARLANTE')
            ) {
                return self::GRUPO_PARLANTES;
            }

            // Si el título o name menciona audífono/auricular/headset, SIEMPRE es Auriculares
            // (evita que audífonos con micrófono categorizados en Deltron como "AUDIO, MICROFONO USB" vayan a micrófonos)
            if (
                str_contains($titulo, 'AUDIFONO') ||
                str_contains($titulo, 'AUDÍFONO') ||
                str_contains($titulo, 'HEADSET') ||
                str_contains($titulo, 'AURICULAR') ||
                str_contains($name, 'AUDIFONO') ||
                str_contains($name, 'HEADSET') ||
                str_contains($name, 'AURICULAR')
            ) {
                return self::GRUPO_AURICULARES;
            }

            if (
                (str_contains($titulo, 'MICROFONO') || str_contains($titulo, 'MICRÓFONO') || str_contains($subDeltron, 'MICROFONO') || str_contains($name, 'MICROFONO'))
            ) {
                return self::GRUPO_MICROFONO;
            }

            return self::GRUPO_AURICULARES;
        }

        // 7. Alfombrillas / Mousepad
        if (
            str_contains($catDeltron, 'PAD') ||
            str_contains($subDeltron, 'PAD') ||
            str_contains($titulo, 'MOUSEPAD') ||
            str_contains($titulo, 'MOUSE PAD') ||
            str_contains($titulo, 'ALFOMBR')
        ) {
            return self::GRUPO_ALFOMBRILLAS;
        }

        // 8. Teclados
        if (
            str_contains($catDeltron, 'TECLADO') ||
            str_contains($subDeltron, 'TECLADO') ||
            str_contains($titulo, 'TECLADO') ||
            str_contains($titulo, 'KEYBOARD')
        ) {
            return self::GRUPO_TECLADOS;
        }

        // 9. Mouses
        if (
            str_contains($catDeltron, 'MOUSE') ||
            str_contains($subDeltron, 'MOUSE') ||
            str_contains($titulo, 'MOUSE') ||
            str_contains($titulo, 'RATON') ||
            str_contains($titulo, 'RATÓN')
        ) {
            return self::GRUPO_MOUSES;
        }

        return null;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        $base = [9, 10, 11, 12, 13, 14, 26, 27, 50, 51, 52, 53, 63, 72, 91];

        $especificas = match ($idGrupo) {
            self::GRUPO_MOUSES          => [58, 59, 60, 69, 16],
            self::GRUPO_TECLADOS        => [25, 28, 29, 30, 69, 16],
            self::GRUPO_COMBOS          => [25, 28, 29, 30, 58, 59, 60, 69],
            self::GRUPO_AURICULARES     => [1, 16, 42, 64, 65, 66, 67, 68, 69],
            self::GRUPO_PARLANTES       => [1, 16, 17, 42, 64, 66, 68, 69, 75],
            self::GRUPO_MICROFONO       => [1, 17, 64, 67, 69],
            self::GRUPO_UPS,
            self::GRUPO_ESTABILIZADORES => [16, 17, 42, 45, 55],
            self::GRUPO_WEBCAM          => [1, 3, 23, 37, 67],
            self::GRUPO_ALFOMBRILLAS    => [29, 69],
            self::GRUPO_MOCHILAS,
            self::GRUPO_MALETAS         => [2, 17, 29, 45],
            self::GRUPO_MEMORIAS_USB,
            self::GRUPO_MEMORIAS_SD     => [45, 48],
            default                     => [],
        };

        return array_values(array_unique(array_merge($base, $especificas)));
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

        // 1. Color (14)
        $specsMapeadas[14] = $this->normalizarColorPeriferico((string)($specsMapeadas[14] ?? ($rawSpecs['COLOR'] ?? '')), $textoAnalisis);

        // 2. Conexión (72)
        $con = ($specsMapeadas[72] ?? '') . ' ' . $textoAnalisis;
        if (stripos($con, 'bluetooth') !== false || preg_match('/\bbt\b/i', $con)) {
            $specsMapeadas[72] = 'Bluetooth';
        } elseif (stripos($con, 'inalambric') !== false || stripos($con, 'wireless') !== false || stripos($con, '2.4') !== false) {
            $specsMapeadas[72] = 'Inalámbrico';
        } else {
            $specsMapeadas[72] = 'USB';
        }

        // 3. Segmento (91)
        if (empty($specsMapeadas[91])) {
            $specsMapeadas[91] = stripos($textoAnalisis, 'GAMER') !== false ? 'Gamer' : 'Productividad';
        }

        // 4. Específicas según grupo
        if ($idGrupo === self::GRUPO_MOUSES || $idGrupo === self::GRUPO_COMBOS) {
            if (empty($specsMapeadas[58])) {
                if (preg_match('/\b(\d{3,5})\s*DPI\b/i', $textoAnalisis, $mDpi)) {
                    $specsMapeadas[58] = $mDpi[1] . ' DPI';
                } else {
                    $specsMapeadas[58] = ($specsMapeadas[91] === 'Gamer') ? '3200 DPI' : '1200 DPI';
                }
            }
            if (empty($specsMapeadas[59])) $specsMapeadas[59] = 'Óptico';
            if (empty($specsMapeadas[60])) $specsMapeadas[60] = ($specsMapeadas[91] === 'Gamer') ? '6' : '3';
        }

        if ($idGrupo === self::GRUPO_TECLADOS || $idGrupo === self::GRUPO_COMBOS) {
            if (empty($specsMapeadas[30])) {
                $specsMapeadas[30] = preg_match('/\b(MECANIC|MECÁNIC|MECHANICAL)\b/i', $textoAnalisis) ? 'Mecánico' : 'Membrana';
            }
            if (empty($specsMapeadas[25])) {
                $specsMapeadas[25] = preg_match('/\b(INGLES|INGLÉS|ENGLISH|US\s+LAYOUT)\b/i', $textoAnalisis) ? 'Inglés' : 'Español';
            }
        }

        return $this->ensureDimensionesYPesoPeriferico($idGrupo, $specsMapeadas);
    }

    protected function normalizarColorPeriferico(string $rawColor, string $textoAnalisis): string
    {
        $fuente = mb_strtolower(trim($rawColor . ' ' . $textoAnalisis));
        if (str_contains($fuente, 'blanco') || str_contains($fuente, 'white')) return 'Blanco';
        if (str_contains($fuente, 'azul') || str_contains($fuente, 'blue')) return 'Azul';
        if (str_contains($fuente, 'rojo') || str_contains($fuente, 'red')) return 'Rojo';
        if (str_contains($fuente, 'rosa') || str_contains($fuente, 'pink')) return 'Rosado';
        if (str_contains($fuente, 'verde') || str_contains($fuente, 'green')) return 'Verde';
        if (str_contains($fuente, 'gris') || str_contains($fuente, 'gray')) return 'Gris';
        if (str_contains($fuente, 'platead') || str_contains($fuente, 'silver')) return 'Plateado';
        return 'Negro';
    }

    protected function ensureDimensionesYPesoPeriferico(int $idGrupo, array $specs): array
    {
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

