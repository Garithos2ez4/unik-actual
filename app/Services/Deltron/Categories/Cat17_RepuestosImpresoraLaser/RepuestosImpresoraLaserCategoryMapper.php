<?php

namespace App\Services\Deltron\Categories\Cat17_RepuestosImpresoraLaser;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class RepuestosImpresoraLaserCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 17;
    public const CATEGORIA_NOMBRE = 'Repuestos de Impresora Laser';

    public const GRUPO_CAJA_TONER_RESIDUAL = 197;
    public const GRUPO_CUCHILLA_LIMPIEZA   = 198;
    public const GRUPO_KIT_MANTENIMIENTO   = 199;
    public const GRUPO_DRUM_OPC            = 200;
    public const GRUPO_RODILLO_FUSOR       = 201;
    public const GRUPO_RODILLO_PRESION     = 202;
    public const GRUPO_RODILLO_TERMICO     = 203;
    public const GRUPO_RODILLO_MAGNETICO   = 204;
    public const GRUPO_RODILLO_CARGA_PCR   = 205;
    public const GRUPO_RODILLO_PICKUP      = 206;
    public const GRUPO_TEFLON              = 207;
    public const GRUPO_UNIDAD_LASER        = 208;
    public const GRUPO_PANEL_CONTROL       = 209;
    public const GRUPO_MOTOR               = 210;

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
        return range(197, 210);
    }

    public function classify(array $context): ?int
    {
        $titulo = $context['titulo'] ?? '';

        if (str_contains($titulo, 'DRUM') || str_contains($titulo, 'TAMBOR') || str_contains($titulo, 'OPC')) {
            return self::GRUPO_DRUM_OPC;
        }
        if (str_contains($titulo, 'FUSOR') || str_contains($titulo, 'FUSER')) {
            return self::GRUPO_RODILLO_FUSOR;
        }
        if (str_contains($titulo, 'CUCHILLA')) {
            return self::GRUPO_CUCHILLA_LIMPIEZA;
        }
        if (str_contains($titulo, 'TEFLON') || str_contains($titulo, 'TEFLÓN')) {
            return self::GRUPO_TEFLON;
        }

        return null;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        return [51, 52, 62, 63, 74];
    }
}

