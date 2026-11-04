<?php

namespace App\Services\Deltron\Categories\Cat13_RepuestosImpresoraDeLaser;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class RepuestosImpresoraDeLaserCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 13;
    public const CATEGORIA_NOMBRE = 'Repuestos de Impresora de Laser';

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
        return [];
    }

    public function classify(array $context): ?int
    {
        return null;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        return [51, 52, 62, 63, 74];
    }
}

