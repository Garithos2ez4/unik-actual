<?php

namespace App\Services\Deltron\Categories\Cat03_Monitores;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class MonitoresCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 3;
    public const CATEGORIA_NOMBRE = 'Monitores';

    public const GRUPO_MONITOR_FHD       = 11;
    public const GRUPO_MONITOR_QHD       = 12;
    public const GRUPO_MONITOR_UHD_4K    = 13;
    public const GRUPO_MONITOR_CURVO     = 14;
    public const GRUPO_MONITOR_ULTRAWIDE = 15;
    public const GRUPO_TELEVISORES       = 16;
    public const GRUPO_PROYECTORES       = 17;
    public const GRUPO_ACCESORIO_TV      = 18;
    public const GRUPO_SOPORTE_RACK      = 19;
    public const GRUPO_MONITOR_PORTATIL  = 82;
    public const GRUPO_MONITOR_HD        = 89;
    public const GRUPO_MONITOR_UHD_5K    = 179;

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
            self::GRUPO_MONITOR_FHD,
            self::GRUPO_MONITOR_QHD,
            self::GRUPO_MONITOR_UHD_4K,
            self::GRUPO_MONITOR_CURVO,
            self::GRUPO_MONITOR_ULTRAWIDE,
            self::GRUPO_TELEVISORES,
            self::GRUPO_PROYECTORES,
            self::GRUPO_ACCESORIO_TV,
            self::GRUPO_SOPORTE_RACK,
            self::GRUPO_MONITOR_PORTATIL,
            self::GRUPO_MONITOR_HD,
            self::GRUPO_MONITOR_UHD_5K,
        ];
    }

    public function classifyEarly(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';

        // Soportes / Racks (Monitores y Televisores)
        $sku = strtoupper($context['item']['sku'] ?? '');
        $name = strtoupper($context['name'] ?? '');

        $esMochilaOMaleta = str_contains($catDeltron, 'MOCHILA') || str_contains($catDeltron, 'ACCESORIOS DE VIAJE') ||
            str_contains($subDeltron, 'MOCHILA') || str_contains($subDeltron, 'BACKPACK') || str_contains($subDeltron, 'MALETA') ||
            str_contains($titulo, 'BACKPACK') || str_contains($titulo, 'MOCHILA') || str_contains($titulo, 'MALETA') ||
            str_contains($titulo, 'MALETIN') || str_contains($titulo, 'MALETÍN') || str_contains($name, 'BACKPACK') ||
            str_starts_with($sku, 'NBACK') || str_starts_with($name, 'BP ');

        $esPeriferico = str_contains($catDeltron, 'MOUSE') || str_contains($catDeltron, 'TRACKBALL') ||
            str_contains($catDeltron, 'PUNTERO') || str_contains($subDeltron, 'MOUSE') ||
            str_contains($titulo, 'MOUSE') || str_contains($titulo, 'MOUSEPAD') || str_contains($titulo, 'PAD') ||
            str_contains($titulo, 'TECLADO') || str_contains($titulo, 'AURICULAR') || str_contains($titulo, 'AUDIFONO');

        if (!$esMochilaOMaleta && !$esPeriferico) {
            if (
                str_contains($titulo, 'SOPORTE PARA MONITOR') ||
                str_contains($titulo, 'SOPORTE DE MONITOR') ||
                str_contains($titulo, 'SOPORTE PARA TV') ||
                str_contains($titulo, 'SOPORTE DE TV') ||
                str_contains($titulo, 'BRAZO PARA MONITOR') ||
                str_contains($titulo, 'RACK PARA') ||
                str_contains($subDeltron, 'RACKS PARA') ||
                ((str_contains($catDeltron, 'MONITOR') || str_contains($subDeltron, 'MONITOR') || str_contains($catDeltron, 'TELEVISOR') || str_contains($subDeltron, 'TELEVISOR') || str_contains($catDeltron, 'RACK')) &&
                 preg_match('/\b(RACK|RACKS|SOPORTE)\b/i', $titulo))
            ) {
                return self::GRUPO_SOPORTE_RACK;
            }
        }

        // Proyectores y Ecrans (Pantallas de Proyección)
        if (
            str_contains($catDeltron, 'PROYECTOR') ||
            str_contains($catDeltron, 'PROYECCION') ||
            str_contains($subDeltron, 'PROYECTOR') ||
            str_contains($subDeltron, 'ECRAN') ||
            str_contains($titulo, 'PROYECTOR') ||
            str_contains($titulo, 'ECRAN') ||
            str_contains($titulo, 'PANTALLA DE PROYECCION') ||
            str_contains($titulo, 'TELON')
        ) {
            return self::GRUPO_PROYECTORES;
        }

        // Televisores
        if (
            str_contains($catDeltron, 'TELEVISOR') ||
            str_contains($subDeltron, 'TELEVISOR') ||
            str_contains($titulo, 'SMART TV') ||
            str_contains($titulo, 'TELEVISOR')
        ) {
            return self::GRUPO_TELEVISORES;
        }

        return null;
    }

    public function classify(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';

        $esMonitor = (
            str_contains($catDeltron, 'MONITOR') ||
            str_contains($subDeltron, 'MONITOR') ||
            str_contains($titulo, 'MONITOR') ||
            str_contains($catDeltron, 'DIGITAL SIGNAGE') ||
            str_starts_with($name, 'MN ') ||
            str_starts_with($name, 'MON ')
        );

        if (!$esMonitor) {
            return null;
        }

        $fuente = $titulo . ' ' . $subDeltron . ' ' . $name;

        if (str_contains($fuente, 'PORTATIL') || str_contains($fuente, 'PORTÁTIL') || str_contains($fuente, 'PORTABLE')) {
            return self::GRUPO_MONITOR_PORTATIL;
        }
        if (str_contains($fuente, '5K') || str_contains($fuente, '5120')) {
            return self::GRUPO_MONITOR_UHD_5K;
        }
        if (str_contains($fuente, 'ULTRAWIDE') || str_contains($fuente, 'ULTRA WIDE') || str_contains($fuente, '21:9') || str_contains($fuente, '32:9')) {
            return self::GRUPO_MONITOR_ULTRAWIDE;
        }
        if (str_contains($fuente, 'CURVO') || str_contains($fuente, 'CURVED') || str_contains($fuente, '1500R') || str_contains($fuente, '1800R') || str_contains($fuente, '1000R')) {
            return self::GRUPO_MONITOR_CURVO;
        }
        if (str_contains($fuente, '4K') || str_contains($fuente, 'UHD') || str_contains($fuente, '3840')) {
            return self::GRUPO_MONITOR_UHD_4K;
        }
        if (str_contains($fuente, 'QHD') || str_contains($fuente, '2K') || str_contains($fuente, 'WQHD') || str_contains($fuente, '2560')) {
            return self::GRUPO_MONITOR_QHD;
        }
        if (str_contains($fuente, 'HD') && !str_contains($fuente, 'FULL HD') && !str_contains($fuente, 'FHD') && str_contains($fuente, '1366')) {
            return self::GRUPO_MONITOR_HD;
        }

        return self::GRUPO_MONITOR_FHD;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        if ($idGrupo === self::GRUPO_SOPORTE_RACK) {
            return [10, 11, 12, 13, 14, 45, 51, 52, 53, 56, 57];
        }
        if ($idGrupo === self::GRUPO_PROYECTORES) {
            return [3, 10, 11, 12, 13, 17, 19, 20, 37, 51, 52, 54, 55];
        }
        if ($idGrupo === self::GRUPO_TELEVISORES) {
            return [1, 2, 3, 17, 21, 37, 51, 52, 75];
        }

        return [
            1, 2, 3, 10, 11, 12, 13, 14, 17, 18, 19, 20, 21, 22, 23, 24, 37, 51, 52, 54, 55, 63, 75,
        ];
    }

    public function enrichAndNormalizeSpecs(
        int $idGrupo,
        array $specsMapeadas,
        array $rawSpecs,
        string $textoCompleto,
        array $sugerenciasPorChar = []
    ): array {
        if ($idGrupo === self::GRUPO_SOPORTE_RACK) {
            return $specsMapeadas;
        }

        $textoAnalisis = $textoCompleto . ' ' . implode(' ', $rawSpecs);

        // 1. Hz (1)
        if (empty($specsMapeadas[1])) {
            $hz = $this->extractHz($textoAnalisis);
            if ($hz) $specsMapeadas[1] = $hz;
        }

        // 2. Pulgadas (2)
        if (empty($specsMapeadas[2])) {
            $p = $this->extractPulgadas($textoAnalisis);
            if ($p) $specsMapeadas[2] = $p;
        }

        // 3. Resolución (3)
        if (empty($specsMapeadas[3])) {
            $res = match ($idGrupo) {
                self::GRUPO_MONITOR_UHD_4K => '3840 x 2160',
                self::GRUPO_MONITOR_QHD    => '2560 x 1440',
                self::GRUPO_MONITOR_HD     => '1366 x 768',
                default                    => $this->extractResolucion($textoAnalisis) ?? '1920 x 1080',
            };
            $specsMapeadas[3] = $res;
        }

        // 4. Panel (21)
        if (empty($specsMapeadas[21])) {
            if (preg_match('/\bOLED\b/i', $textoAnalisis)) $specsMapeadas[21] = 'OLED';
            elseif (preg_match('/\b(VA|WVA)\b/i', $textoAnalisis)) $specsMapeadas[21] = 'VA';
            elseif (preg_match('/\bTN\b/i', $textoAnalisis)) $specsMapeadas[21] = 'TN';
            else $specsMapeadas[21] = 'IPS';
        }

        // 5. Curvo (22)
        if (empty($specsMapeadas[22])) {
            $specsMapeadas[22] = ($idGrupo === self::GRUPO_MONITOR_CURVO || preg_match('/\b(CURVO|CURVED|\d{3,4}R)\b/i', $textoAnalisis))
                ? 'Si' : 'No';
        }

        // 6. Tiempo de respuesta (18)
        if (empty($specsMapeadas[18])) {
            if (preg_match('/\b([\d\.]+)\s*MS\b/i', $textoAnalisis, $mMs)) {
                $specsMapeadas[18] = $mMs[1] . ' MS';
            }
        }

        // 7. Brillo (19)
        if (empty($specsMapeadas[19])) {
            if (preg_match('/\b(\d{3})\s*(?:CD\/M2|NITS)\b/i', $textoAnalisis, $mBr)) {
                $specsMapeadas[19] = $mBr[1] . ' cd/m2';
            }
        }

        // 8. Relación de aspecto (54)
        if (empty($specsMapeadas[54])) {
            if ($idGrupo === self::GRUPO_MONITOR_ULTRAWIDE || preg_match('/\b(21:9|32:9)\b/', $textoAnalisis, $mAsp)) {
                $specsMapeadas[54] = $mAsp[1] ?? '21:9';
            } else {
                $specsMapeadas[54] = '16:9';
            }
        }

        // 9. Montaje de pared (24)
        if (empty($specsMapeadas[24])) {
            $specsMapeadas[24] = preg_match('/\b(VESA|MONTAJE|WALL\s*MOUNT)\b/i', $textoAnalisis) ? 'Si' : 'No';
        }

        // 10. Color (14)
        if (empty($specsMapeadas[14])) {
            $specsMapeadas[14] = str_contains(strtoupper($textoAnalisis), 'BLANCO') ? 'Blanco' : 'Negro';
        }

        return $specsMapeadas;
    }
}

