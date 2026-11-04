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
        if (
            str_contains($catDeltron, 'SOFTWARE') || str_contains($catDeltron, 'LICENCIA') ||
            str_contains($subDeltron, 'SOFTWARE') || str_contains($subDeltron, 'LICENCIA') || str_contains($subDeltron, 'ESD') ||
            str_contains($titulo, 'WINDOWS') || str_contains($titulo, 'OFFICE') || str_contains($titulo, 'ANTIVIRUS') ||
            str_contains($titulo, 'MICROSOFT 365') || str_starts_with($name, 'WIN ') || str_starts_with($name, 'WIN11')
        ) {
            return self::GRUPO_SOFTWARE;
        }

        // Servidores
        if (str_contains($catDeltron, 'SERVIDORES') || str_contains($subDeltron, 'SERVIDORES')) {
            return self::GRUPO_SERVIDORES;
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

