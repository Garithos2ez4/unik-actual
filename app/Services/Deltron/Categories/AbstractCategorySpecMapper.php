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

    protected function extractMemoriaRam(string $texto): ?string
    {
        if (preg_match('/\b(4|8|12|16|24|32|48|64|128)\s*GB\s*(?:DDR[45]|LPDDR[45]X?|SO-?DIMM|SODIMM|RAM)?\b/i', $texto, $m)) {
            return $m[1] . ' GB';
        }
        return null;
    }

    protected function extractAlmacenamiento(string $texto): ?string
    {
        if (preg_match('/\b(2)\s*TB\b/i', $texto)) {
            return '2TB';
        }
        if (preg_match('/\b(1)\s*TB\b/i', $texto) || preg_match('/\b1024\s*GB\b/i', $texto)) {
            return '1TB';
        }
        if (preg_match('/\b(512|256|128)\s*GB\b/i', $texto, $m)) {
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

