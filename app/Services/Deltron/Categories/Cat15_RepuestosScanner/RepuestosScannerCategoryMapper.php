<?php

namespace App\Services\Deltron\Categories\Cat15_RepuestosScanner;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class RepuestosScannerCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 15;
    public const CATEGORIA_NOMBRE = 'Repuestos de Scanner / Escanner';

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
        return range(182, 185);
    }

    public function classify(array $context): ?int
    {
        $titulo = $context['titulo'] ?? '';

        if (str_contains($titulo, 'RODILLO') && (str_contains($titulo, 'ESCANER') || str_contains($titulo, 'SCANNER'))) {
            return 185; // Kit Rodillo
        }

        return null;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        return [51, 52, 63, 74];
    }
}

