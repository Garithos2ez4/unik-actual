<?php

namespace App\Services\Deltron\Categories\Cat12_RepuestosImpresoraTinta;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class RepuestosImpresoraTintaCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 12;
    public const CATEGORIA_NOMBRE = 'Repuestos de Impresora de Tinta';

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
        return range(120, 154);
    }

    public function classify(array $context): ?int
    {
        $titulo = $context['titulo'] ?? '';

        if (str_contains($titulo, 'CABEZAL') && (str_contains($titulo, 'EPSON') || str_contains($titulo, 'CANON') || str_contains($titulo, 'HP'))) {
            return 124; // Cabezal de Impresoras
        }
        if (str_contains($titulo, 'CAJA DE MANTENIMIENTO') || str_contains($titulo, 'MAINTENANCE BOX')) {
            return 125; // Caja de Mantenimiento
        }
        if (str_contains($titulo, 'DAMPER')) {
            return 148; // Damper de tinta
        }
        if (str_contains($titulo, 'RODILLO') || str_contains($titulo, 'PICKUP ROLLER')) {
            return 141; // Rodillos
        }

        return null;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        return [10, 11, 12, 13, 14, 51, 52, 55, 62, 63, 74];
    }
}

