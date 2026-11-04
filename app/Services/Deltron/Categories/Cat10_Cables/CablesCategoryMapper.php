<?php

namespace App\Services\Deltron\Categories\Cat10_Cables;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class CablesCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 10;
    public const CATEGORIA_NOMBRE = 'Cables';

    public const GRUPO_CABLES_DATOS      = 68;
    public const GRUPO_CARGADORES_LAPTOP = 69;
    public const GRUPO_CINTA_TERMICA     = 70;
    public const GRUPO_SATA              = 71;
    public const GRUPO_ADAPTADOR_RED     = 72;
    public const GRUPO_ADAPTADOR_SATA    = 73;
    public const GRUPO_ADAPTADOR_VIDEO   = 74;
    public const GRUPO_MULTIPUERTOS      = 87;
    public const GRUPO_ADAPTADOR_SSD     = 99;
    public const GRUPO_CABLE_PODER       = 176;
    public const GRUPO_ADAPTADOR_AUDIO   = 180;
    public const GRUPO_CABLE_AUDIO_VIDEO = 186;

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
            self::GRUPO_CABLES_DATOS,
            self::GRUPO_CARGADORES_LAPTOP,
            self::GRUPO_CINTA_TERMICA,
            self::GRUPO_SATA,
            self::GRUPO_ADAPTADOR_RED,
            self::GRUPO_ADAPTADOR_SATA,
            self::GRUPO_ADAPTADOR_VIDEO,
            self::GRUPO_MULTIPUERTOS,
            self::GRUPO_ADAPTADOR_SSD,
            self::GRUPO_CABLE_PODER,
            self::GRUPO_ADAPTADOR_AUDIO,
            self::GRUPO_CABLE_AUDIO_VIDEO,
        ];
    }

    public function classify(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';

        if (str_contains($catDeltron, 'CABLEADO') || str_contains($titulo, 'CABLE') || str_contains($titulo, 'PATCH CORD')) {
            if (str_contains($titulo, 'PODER')) return self::GRUPO_CABLE_PODER;
            if (str_contains($titulo, 'AUDIO') || str_contains($titulo, 'VIDEO') || str_contains($titulo, 'HDMI')) return self::GRUPO_CABLE_AUDIO_VIDEO;
            return self::GRUPO_CABLES_DATOS;
        }

        if (str_contains($titulo, 'ADAPTADOR') || str_contains($titulo, 'HUB') || str_contains($titulo, 'MULTIPUERTO')) {
            if (str_contains($titulo, 'RED') || str_contains($titulo, 'ETHERNET') || str_contains($titulo, 'RJ45')) return self::GRUPO_ADAPTADOR_RED;
            if (str_contains($titulo, 'HDMI') || str_contains($titulo, 'VGA') || str_contains($titulo, 'DISPLAYPORT')) return self::GRUPO_ADAPTADOR_VIDEO;
            if (str_contains($titulo, 'AUDIO') || str_contains($titulo, 'JACK')) return self::GRUPO_ADAPTADOR_AUDIO;
            if (str_contains($titulo, 'HUB') || str_contains($titulo, 'MULTIPUERTO')) return self::GRUPO_MULTIPUERTOS;
            return self::GRUPO_CABLES_DATOS;
        }

        return null;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        return [9, 10, 11, 12, 13, 14, 27, 42, 46, 48, 51, 52, 53, 63, 85, 86, 87];
    }

    public function enrichAndNormalizeSpecs(
        int $idGrupo,
        array $specsMapeadas,
        array $rawSpecs,
        string $textoCompleto,
        array $sugerenciasPorChar = []
    ): array {
        $textoAnalisis = $textoCompleto . ' ' . implode(' ', $rawSpecs);

        // 1. Tipo De Conexión (87)
        if (empty($specsMapeadas[87])) {
            if (preg_match('/(?:tipo\s*c|type-?c|c)\s*(?:a|to|-)\s*lightning/i', $textoAnalisis)) {
                $specsMapeadas[87] = 'C a Lightning';
            } elseif (preg_match('/(?:tipo\s*c|type-?c|c)\s*(?:a|to|-)\s*(?:tipo\s*c|type-?c|c)/i', $textoAnalisis)) {
                $specsMapeadas[87] = 'C a C';
            }
        }

        // 2. Potencia de Carga (85)
        if (empty($specsMapeadas[85])) {
            $w = $this->extractWatts($textoAnalisis);
            if ($w) $specsMapeadas[85] = $w;
        }

        // 3. Velocidad de Lectura / transferencia (86)
        if (empty($specsMapeadas[86]) && preg_match('/\b(480\s*mb(?:\/s|ps)?)\b/i', $textoAnalisis)) {
            $specsMapeadas[86] = '480 Mbps';
        }

        // 4. Longitud de cable (27)
        if (empty($specsMapeadas[27])) {
            if (preg_match('/\b(\d+(?:\.\d+)?)\s*(?:mts?|metros?)\b/i', $textoAnalisis, $lm)) {
                $specsMapeadas[27] = $lm[1] . ' MTS';
            }
        }

        return $specsMapeadas;
    }
}

