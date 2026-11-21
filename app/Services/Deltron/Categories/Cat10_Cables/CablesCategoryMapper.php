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

    public function classifyEarly(array $context): ?int
    {
        $titulo = $context['titulo'] ?? '';
        $name   = $context['name'] ?? '';
        $fuente = $titulo . ' ' . $name;

        if (
            str_contains($fuente, 'IDEASHARE') ||
            str_contains($fuente, 'TRANSFERENCIA DE MEDIOS') ||
            str_contains($fuente, 'CABLE DE DATOS')
        ) {
            return self::GRUPO_CABLES_DATOS;
        }

        return null;
    }

    public function classify(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';
        $sku        = strtoupper($context['item']['sku'] ?? '');

        // Candado: nunca clasificar periféricos (teclados, mouses, audio, webcams, etc.) como cables
        // solo porque su título diga "con cable USB" o "cableado".
        $esPeriferico = (
            str_contains($catDeltron, 'TECLADO') ||
            str_contains($catDeltron, 'MOUSE') ||
            str_contains($catDeltron, 'AUDIO') ||
            str_contains($catDeltron, 'VIDEOCONFERENCIA') ||
            str_contains($subDeltron, 'TECLADO') ||
            str_contains($subDeltron, 'MOUSE') ||
            str_contains($titulo, 'TECLADO') ||
            str_contains($titulo, 'KEYBOARD') ||
            str_contains($titulo, 'MOUSE') ||
            str_contains($titulo, 'RATON') ||
            str_contains($titulo, 'RATÓN') ||
            str_contains($titulo, 'AURICULAR') ||
            str_contains($titulo, 'AUDIFONO') ||
            str_contains($titulo, 'AUDÍFONO') ||
            str_contains($titulo, 'HEADSET') ||
            str_contains($titulo, 'PARLANTE') ||
            str_contains($titulo, 'SPEAKER') ||
            str_contains($titulo, 'MICROFONO') ||
            str_contains($titulo, 'MICRÓFONO') ||
            str_contains($titulo, 'WEBCAM') ||
            str_contains($titulo, 'GAMEPAD') ||
            str_contains($titulo, 'JOYSTICK') ||
            str_starts_with($sku, 'KB') ||
            str_starts_with($sku, 'MS') ||
            str_starts_with($name, 'KB ') ||
            str_starts_with($name, 'MS ')
        );

        if ($esPeriferico) {
            return null;
        }

        // Ignorar la frase "CON CABLE" al evaluar si el producto es un cable
        $tituloSinConCable = trim(preg_replace('/\bCON\s+CABLE(?:\s+USB(?:\s+\d+(?:\.\d+)?)?)?\b/iu', ' ', $titulo) ?? $titulo);

        if (str_contains($catDeltron, 'CABLEADO') || str_contains($tituloSinConCable, 'CABLE') || str_contains($tituloSinConCable, 'PATCH CORD')) {
            if (str_contains($tituloSinConCable, 'PODER')) return self::GRUPO_CABLE_PODER;
            if (str_contains($tituloSinConCable, 'AUDIO') || str_contains($tituloSinConCable, 'VIDEO') || str_contains($tituloSinConCable, 'HDMI')) return self::GRUPO_CABLE_AUDIO_VIDEO;
            return self::GRUPO_CABLES_DATOS;
        }

        if (str_contains($tituloSinConCable, 'ADAPTADOR') || str_contains($tituloSinConCable, 'HUB') || str_contains($tituloSinConCable, 'MULTIPUERTO')) {
            if (str_contains($tituloSinConCable, 'RED') || str_contains($tituloSinConCable, 'ETHERNET') || str_contains($tituloSinConCable, 'RJ45')) return self::GRUPO_ADAPTADOR_RED;
            if (str_contains($tituloSinConCable, 'HDMI') || str_contains($tituloSinConCable, 'VGA') || str_contains($tituloSinConCable, 'DISPLAYPORT')) return self::GRUPO_ADAPTADOR_VIDEO;
            if (str_contains($tituloSinConCable, 'AUDIO') || str_contains($tituloSinConCable, 'JACK')) return self::GRUPO_ADAPTADOR_AUDIO;
            if (str_contains($tituloSinConCable, 'HUB') || str_contains($tituloSinConCable, 'MULTIPUERTO')) return self::GRUPO_MULTIPUERTOS;
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

