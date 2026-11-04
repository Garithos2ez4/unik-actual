<?php

namespace App\Services\Deltron\Categories\Cat14_SuministrosConsumibles;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class SuministrosConsumiblesCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 14;
    public const CATEGORIA_NOMBRE = 'Suministros / Consumibles';

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
        return array_merge(range(155, 175), [181, 211, 214]);
    }

    public function classify(array $context): ?int
    {
        $titulo = $context['titulo'] ?? '';
        $sub = $context['subDeltron'] ?? '';

        if (str_contains($sub, 'TONER') || str_contains($titulo, 'TONER')) {
            return 173; // Toner Original
        }
        if (str_contains($sub, 'TINTAS') || str_contains($sub, 'BOTELLAS') || str_contains($titulo, 'TINTA') || str_contains($titulo, 'BOTELLA')) {
            return 156; // Botella de Tinta Original
        }
        if (str_contains($titulo, 'CARTUCHO')) {
            return 159; // Cartucho Original de Tinta
        }
        if (str_contains($titulo, 'CINTA')) {
            return 162; // Cinta Matricial
        }
        if (str_contains($titulo, 'PAPEL FOTOGRAFICO') || str_contains($titulo, 'PAPEL FOTOGRÁFICO')) {
            return 171; // Papel Fotográfico
        }

        return null;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        return [14, 40, 51, 52, 61, 62, 63, 73, 74, 82];
    }
}

