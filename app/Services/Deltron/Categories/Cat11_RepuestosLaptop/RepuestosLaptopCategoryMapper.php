<?php

namespace App\Services\Deltron\Categories\Cat11_RepuestosLaptop;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class RepuestosLaptopCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 11;
    public const CATEGORIA_NOMBRE = 'Repuestos de Laptop';

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
        return range(100, 119);
    }

    public function classify(array $context): ?int
    {
        $titulo = $context['titulo'] ?? '';
        $cat = $context['catDeltron'] ?? '';

        if (str_contains($cat, 'REPUESTO') && str_contains($cat, 'NOTEBOOK')) {
            if (str_contains($titulo, 'BATERIA') || str_contains($titulo, 'BATERÍA')) return 101;
            if (str_contains($titulo, 'PANTALLA') || str_contains($titulo, 'DISPLAY')) return 112;
            if (str_contains($titulo, 'TECLADO')) return 117;
            if (str_contains($titulo, 'CARGADOR')) return 106;
            if (str_contains($titulo, 'COOLER') || str_contains($titulo, 'VENTILADOR')) return 108;
            return 119;
        }

        return null;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        return [2, 3, 10, 11, 12, 13, 14, 16, 25, 42, 51, 52, 63, 74];
    }
}

