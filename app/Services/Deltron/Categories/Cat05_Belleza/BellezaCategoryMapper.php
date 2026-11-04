<?php

namespace App\Services\Deltron\Categories\Cat05_Belleza;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class BellezaCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 5;
    public const CATEGORIA_NOMBRE = 'Belleza';

    public const GRUPO_CREMA_PEINAR     = 20;
    public const GRUPO_TINTES           = 21;
    public const GRUPO_SHAMPOO          = 22;
    public const GRUPO_CUIDADO_PERSONAL = 23;

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
            self::GRUPO_CREMA_PEINAR,
            self::GRUPO_TINTES,
            self::GRUPO_SHAMPOO,
            self::GRUPO_CUIDADO_PERSONAL,
        ];
    }

    public function classify(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';

        if (str_contains($catDeltron, 'BELLEZA') || str_contains($catDeltron, 'CUIDADO PERSONAL') || str_contains($titulo, 'SHAMPOO') || str_contains($titulo, 'TINTE')) {
            if (str_contains($titulo, 'SHAMPOO')) return self::GRUPO_SHAMPOO;
            if (str_contains($titulo, 'TINTE')) return self::GRUPO_TINTES;
            if (str_contains($titulo, 'CREMA')) return self::GRUPO_CREMA_PEINAR;
            return self::GRUPO_CUIDADO_PERSONAL;
        }

        return null;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        return [10, 14, 51, 52, 61, 88];
    }
}

