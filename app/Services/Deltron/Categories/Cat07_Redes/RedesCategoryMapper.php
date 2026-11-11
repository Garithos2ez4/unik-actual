<?php

namespace App\Services\Deltron\Categories\Cat07_Redes;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class RedesCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 7;
    public const CATEGORIA_NOMBRE = 'Redes';

    public const GRUPO_CAMARAS_SEGURIDAD = 58;
    public const GRUPO_ROUTER_WIFI       = 59;
    public const GRUPO_ZKTECO            = 60;
    public const GRUPO_SERVIDORES        = 61;
    public const GRUPO_SOFTWARE          = 62;
    public const GRUPO_SWITCH            = 63;
    public const GRUPO_PANEL_SOLAR       = 81;
    public const GRUPO_FOCO_SMART        = 91;

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
            self::GRUPO_CAMARAS_SEGURIDAD,
            self::GRUPO_ROUTER_WIFI,
            self::GRUPO_ZKTECO,
            self::GRUPO_SERVIDORES,
            self::GRUPO_SOFTWARE,
            self::GRUPO_SWITCH,
            self::GRUPO_PANEL_SOLAR,
            self::GRUPO_FOCO_SMART,
        ];
    }

    public function classifyEarly(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';

        // Software y Licencias
        $esHardwareOPeriferico = str_contains($titulo, 'AURICULAR') || str_contains($titulo, 'AUDIFONO') ||
            str_contains($titulo, 'AUDÍFONO') || str_contains($titulo, 'HEADSET') ||
            str_contains($titulo, 'TECLADO') || str_contains($titulo, 'MOUSE') ||
            str_contains($titulo, 'PARLANTE') || str_contains($titulo, 'MICROFONO') || str_contains($titulo, 'MICRÓFONO') ||
            str_contains($titulo, 'CONTROL') || str_contains($titulo, 'CABLE') ||
            str_contains($titulo, 'MONITOR') || str_contains($titulo, 'LAPTOP');

        if (!$esHardwareOPeriferico && (
            str_contains($catDeltron, 'SOFTWARE') || str_contains($catDeltron, 'LICENCIA') ||
            str_contains($subDeltron, 'SOFTWARE') || str_contains($subDeltron, 'LICENCIA') || str_contains($subDeltron, 'ESD') ||
            str_contains($titulo, 'LICENCIA') || str_contains($titulo, 'OFFICE') || str_contains($titulo, 'ANTIVIRUS') ||
            str_contains($titulo, 'MICROSOFT 365') || str_starts_with($name, 'WIN ') || str_starts_with($name, 'WIN11') ||
            preg_match('/\b(WINDOWS\s*(10|11|SERVER)|WIN\s*(10|11))\b/i', $titulo)
        )) {
            return self::GRUPO_SOFTWARE;
        }

        // Servidores y Almacenamiento en Red (NAS)
        $sku = strtoupper($context['item']['sku'] ?? '');
        $esServidorONas = (
            str_contains($catDeltron, 'SERVIDORES') || str_contains($subDeltron, 'SERVIDORES') ||
            str_contains($subDeltron, 'NAS') ||
            str_contains($titulo, 'SERVIDOR') || str_contains($titulo, 'SERVER') ||
            str_contains($titulo, 'NAS') ||
            str_starts_with($name, 'NAS ') || str_starts_with($name, 'SRV ') ||
            str_starts_with($sku, 'NAS') || str_starts_with($sku, 'SRV') ||
            str_contains($titulo, 'ASUSTOR') || str_contains($titulo, 'SYNOLOGY') || str_contains($titulo, 'QNAP')
        );

        if ($esServidorONas) {
            $esDiscoFisico = str_contains($catDeltron, 'DISCOS DUROS') || str_contains($catDeltron, 'DISCO SOLIDO') ||
                str_contains($subDeltron, 'DISCO DURO') || str_contains($subDeltron, 'SSD') ||
                str_starts_with($titulo, 'DISCO DURO') || str_starts_with($titulo, 'DISCO SOLIDO') ||
                str_starts_with($name, 'HD ') || str_starts_with($name, 'SSD ');

            if (!$esDiscoFisico) {
                return self::GRUPO_SERVIDORES;
            }
        }

        return null;
    }

    public function classify(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';

        // Smart Home / IoT
        if (str_contains($catDeltron, 'SMART HOME') || str_contains($subDeltron, 'SMART HOME')) {
            if (str_contains($titulo, 'CAMARA') || str_contains($titulo, 'CÁMARA')) return self::GRUPO_CAMARAS_SEGURIDAD;
            if (str_contains($titulo, 'FOCO') || str_contains($titulo, 'BOMBILLA')) return self::GRUPO_FOCO_SMART;
            return self::GRUPO_ROUTER_WIFI;
        }

        // Redes LAN / WAN / Inalámbricas
        if (str_contains($catDeltron, 'REDES') || str_contains($subDeltron, 'RED')) {
            if (str_contains($titulo, 'SWITCH') || str_contains($subDeltron, 'SWITCH')) return self::GRUPO_SWITCH;
            if (str_contains($titulo, 'CAMARA') || str_contains($titulo, 'CÁMARA') || str_contains($subDeltron, 'CAMARA')) return self::GRUPO_CAMARAS_SEGURIDAD;
            return self::GRUPO_ROUTER_WIFI;
        }

        return null;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        return [3, 9, 10, 11, 12, 13, 14, 17, 42, 48, 51, 52, 63, 72];
    }
}

