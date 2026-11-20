<?php

namespace App\Services\Deltron\Categories;

use App\Services\Deltron\Contracts\DeltronCategoryClassifierInterface;

abstract class AbstractCategorySpecMapper implements DeltronCategoryClassifierInterface
{
    /**
     * @return int
     */
    abstract public function getCategoriaId(): int;

    /**
     * @return string
     */
    abstract public function getCategoriaNombre(): string;

    /**
     * @return int[]
     */
    abstract public function getGruposIds(): array;

    public function supportsGrupo(int $idGrupo): bool
    {
        return in_array($idGrupo, $this->getGruposIds(), true);
    }

    public function classifyEarly(array $context): ?int
    {
        return null;
    }

    public function getMapaClavesCategoria(?int $idGrupo = null): array
    {
        return [];
    }

    public function normalizarValorCategoria(int $idCaracteristica, string $valorDeltron, ?string $claveDeltron = null, array $sugerencias = []): ?string
    {
        return null;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        // Universales por defecto
        return [10, 11, 12, 13, 14, 51, 52, 53, 63];
    }

    public function enrichAndNormalizeSpecs(
        int $idGrupo,
        array $specsMapeadas,
        array $rawSpecs,
        string $textoCompleto,
        array $sugerenciasPorChar = []
    ): array {
        return $specsMapeadas;
    }

    // ==========================================
    // HELPERS COMUNES DE EXTRACCIÓN Y LIMPIEZA
    // ==========================================

    protected function matchSugerencia(string $valor, array $sugerencias): ?string
    {
        $v = trim($valor);
        if (empty($sugerencias) || $v === '') {
            return null;
        }

        foreach ($sugerencias as $sug) {
            if (strcasecmp($sug, $v) === 0) {
                return $sug;
            }
        }

        foreach ($sugerencias as $sug) {
            if (stripos($v, $sug) !== false) {
                return $sug;
            }
        }

        return null;
    }

    protected function extractHz(string $texto): ?string
    {
        if (preg_match_all('/\b(\d{2,3})\s*(?:HZ)?\b/i', $texto, $matches)) {
            $nums = array_map('intval', $matches[1]);
            rsort($nums);
            foreach ($nums as $n) {
                if (in_array($n, [60, 75, 90, 100, 120, 144, 165, 170, 180, 200, 240, 280, 360, 500], true)) {
                    return $n . ' Hz';
                }
            }
        }
        return null;
    }

    protected function extractPulgadas(string $texto): ?string
    {
        if (preg_match('/(11\.6|12\.4|13\.3|13\.4|14(?:\.0)?|14\.5|15\.6|16(?:\.0)?|17(?:\.3)?|19(?:\.5)?|21\.5|22(?:\.0)?|23\.8|24(?:\.0)?|24\.5|27(?:\.0)?|29(?:\.0)?|31\.5|32(?:\.0)?|34(?:\.0)?|49(?:\.0)?)\s*(?:\"|\'\'|PULG)/i', $texto, $m)) {
            return $m[1] . '"';
        }
        return null;
    }

    protected function extractResolucion(string $texto): ?string
    {
        if (preg_match('/(3840\s*x\s*2160|4K\s*UHD|\b4K\b)/i', $texto)) {
            return '3840 x 2160';
        }
        if (preg_match('/(2560\s*x\s*1600|WQXGA)/i', $texto)) {
            return '2560 x 1600';
        }
        if (preg_match('/(2560\s*x\s*1440|2K|WQHD|\bQHD\b)/i', $texto)) {
            return '2560 x 1440';
        }
        if (preg_match('/(1920\s*x\s*1200|WUXGA|FHD\+)/i', $texto)) {
            return '1920 x 1200';
        }
        if (preg_match('/(1920\s*x\s*1080|FULL\s*HD|\bFHD\b|1080P)/i', $texto)) {
            return '1920 x 1080';
        }
        if (preg_match('/(1366\s*x\s*768|\bHD\b)/i', $texto)) {
            return '1366 x 768';
        }
        if (preg_match('/(\d{3,4}\s*x\s*\d{3,4})/i', $texto, $m)) {
            return preg_replace('/\s+/', ' ', $m[1]);
        }
        return null;
    }

    protected function stripExpansionPhrases(string $texto): string
    {
        // 1. Líneas estructuradas de expansión máxima o comentarios sobre capacidad máxima soportada
        $limpio = preg_replace('/^[ \t]*-?[ \t]*EXPANSI[OÓ]N\s+M[AÁ]XIMA\s*:[^\r\n]*/imu', ' ', $texto);
        $limpio = preg_replace('/^[ \t]*-?[ \t]*COMENTARIOS\s*:[^\r\n]*(?:SOPORTA\s+HASTA|DE\s+HASTA|EXPANSI[OÓ]N|EXPANDIBLE|M[AÁ]XIM[OA]|UP\s+TO)[^\r\n]*/imu', ' ', $limpio ?? $texto);

        // 2. Cláusulas de expansión en prosa/comentarios sin cortar en puntos internos como "M.2" o "4.0"
        $limpio = preg_replace(
            '/(?:EXPANSI[OÓ]N\s+M[AÁ]XIMA|MEMORIA\s+M[AÁ]XIMA(?:\s+SOPORTADA)?|ALMACENAMIENTO\s+M[AÁ]XIMO(?:\s+SOPORTADO)?|SOPORTA\s+HASTA|SOPORTAD[AO]\s+HASTA|EXPANDIBLE\s+HASTA|AMPLIABLE\s+HASTA|ACTUALIZABLE\s+HASTA|UP\s+TO\s+\d+\s*(?:GB|TB)|DE\s+HASTA\s+(?:UN[AO]?\s+)?\d+\s*(?:GB|TB)|HASTA\s+(?:UN[AO]?\s+)?\d+\s*(?:GB|TB))(?:[^\r\n;.]|\.(?!\s|$))*/iu',
            ' ',
            $limpio ?? $texto
        );

        return $limpio ?? $texto;
    }

    protected function extractMemoriaRam(string $texto): ?string
    {
        $limpio = $this->stripExpansionPhrases($texto);

        if (preg_match('/\b(2|3|4|8|12|16|24|32|48|64|128)\s*GB?\s*(?:DDR[345]|LPDDR[45]X?|SO-?DIMM|SODIMM|RAM)\b/i', $limpio, $m)) {
            return $m[1] . ' GB';
        }

        $sinVideo = preg_replace('/\[VIDEO[^\]]*\].*?(?=\n\s*\[|\z)/si', ' ', $limpio) ?? $limpio;
        $sinVideo = preg_replace('/\b(?:RTX|GTX|RX|RADEON|GEFORCE|ARC|VRAM)\s*[A-Z0-9-]{0,12}\s+\d{1,2}\s*GB\b/i', ' ', $sinVideo) ?? $sinVideo;
        if (preg_match('/\b(2|3|4|8|12|16|24|32|48|64|128)\s*GB\b(?!\s*(?:SSD|NVME|M\.2|HDD|EMMC|UFS|GDDR\d*|VRAM|RTX|GTX|VIDEO))/i', $sinVideo, $m)) {
            return $m[1] . ' GB';
        }

        return null;
    }

    protected function extractAlmacenamiento(string $texto): ?string
    {
        $limpio = $this->stripExpansionPhrases($texto);

        $normalizar = static function (?string $tb, ?string $gb): string {
            if ($tb !== null && $tb !== '') {
                return $tb . 'TB';
            }
            if ($gb === '1024') {
                return '1TB';
            }
            return $gb . ' GB';
        };

        // Prioridad 1: capacidad junto a palabra clave de almacenamiento
        if (preg_match('/\b(?:(4|2|1)\s*TB?|(1024|512|256|128|64|32)\s*GB?)\s*(?:SSD|NVME|M\.2|HDD|EMMC|UFS|PCIE|SOLID)\b/i', $limpio, $m)) {
            return $normalizar(($m[1] ?? '') ?: null, ($m[2] ?? '') ?: null);
        }
        if (preg_match('/\b(?:SSD|NVME|M\.2|HDD|EMMC|UFS|DISCO(?:\s+DURO|\s+S[OÓ]LIDO)?)(?:\s+(?:M\.2|NVME|PCIE|SATA|DE)){0,3}\s+(?:(4|2|1)\s*TB?|(1024|512|256|128|64|32)\s*GB?)\b/i', $limpio, $m)) {
            return $normalizar(($m[1] ?? '') ?: null, ($m[2] ?? '') ?: null);
        }

        // Prioridad 2: capacidad en orden de aparición (excluyendo RAM ej. 128GB DDR5)
        if (preg_match('/\b(?:(4|2|1)\s*TB|(1024|512|256|128)\s*GB(?!\s*(?:DDR[345]|LPDDR[45]X?|SO-?DIMM|SODIMM|RAM)))\b/i', $limpio, $m)) {
            return $normalizar(($m[1] ?? '') ?: null, ($m[2] ?? '') ?: null);
        }

        // Prioridad 3: capacidades pequeñas (64/32 GB, e.g. eMMC) solo si no es RAM ni VRAM
        if (preg_match('/\b(64|32)\s*GB(?!\s*(?:DDR[345]|LPDDR[45]X?|SO-?DIMM|SODIMM|RAM|GDDR\d*|VRAM))\b/i', $limpio, $m)) {
            return $m[1] . ' GB';
        }

        return null;
    }

    protected function extractWatts(string $texto): ?string
    {
        if (preg_match('/\b(\d{2,4})\s*W(?:ATTS?)?\b/i', $texto, $m)) {
            return $m[1] . ' W';
        }
        return null;
    }

    protected function extractDimensionesPaquete(string $texto): ?string
    {
        if (preg_match('/([\d\.]+)\s*x\s*([\d\.]+)\s*x\s*([\d\.]+)\s*(CM|MM|M)?/i', $texto, $dm)) {
            $u = strtolower($dm[4] ?? 'cm');
            return "{$dm[1]} x {$dm[2]} x {$dm[3]} {$u}";
        }
        return null;
    }

    protected function extractPesoPaquete(string $texto): ?string
    {
        if (preg_match('/([\d\.]+)\s*(KG|KILOS?|G|GR|GRAMOS)?/i', $texto, $m)) {
            $num = (float) $m[1];
            $u = strtoupper($m[2] ?? 'KG');
            if ($u === 'G' || $u === 'GR' || $u === 'GRAMOS' || ($num > 100 && $u === 'KG')) {
                return (string) round($num / 1000, 3);
            }
            return (string) $num;
        }
        return null;
    }
}

