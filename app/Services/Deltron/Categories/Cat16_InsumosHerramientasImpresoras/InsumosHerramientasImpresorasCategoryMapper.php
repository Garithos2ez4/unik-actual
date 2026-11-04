<?php

namespace App\Services\Deltron\Categories\Cat16_InsumosHerramientasImpresoras;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class InsumosHerramientasImpresorasCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 16;
    public const CATEGORIA_NOMBRE = 'Insumos / Herramientas de Impresoras';

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
        return array_merge(range(187, 196), [212]);
    }

    public function classify(array $context): ?int
    {
        $titulo = $context['titulo'] ?? '';

        if (str_contains($titulo, 'LIMPIADOR') || str_contains($titulo, 'WASHING') || str_contains($titulo, 'LIQUIDO DE LIMPIEZA')) {
            return 189; // Liquido de Limpieza de Cabezal
        }
        if (str_contains($titulo, 'RESET') || str_contains($titulo, 'RESETTEADOR')) {
            return 193; // Reseteador
        }

        return null;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        return [51, 52, 61, 63, 74, 88];
    }
}

