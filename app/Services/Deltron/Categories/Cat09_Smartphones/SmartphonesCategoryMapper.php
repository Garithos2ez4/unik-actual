<?php

namespace App\Services\Deltron\Categories\Cat09_Smartphones;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class SmartphonesCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 9;
    public const CATEGORIA_NOMBRE = 'Smartphones';

    public const GRUPO_CELULARES     = 64;
    public const GRUPO_TABLET        = 65;
    public const GRUPO_CARCASAS      = 66;
    public const GRUPO_CARGADORES    = 67;
    public const GRUPO_SMARTWATCH    = 76;
    public const GRUPO_ESTABILIZADOR = 96;

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
            self::GRUPO_CELULARES,
            self::GRUPO_TABLET,
            self::GRUPO_CARCASAS,
            self::GRUPO_CARGADORES,
            self::GRUPO_SMARTWATCH,
            self::GRUPO_ESTABILIZADOR,
        ];
    }

    public function classifyEarly(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';

        // Tablets
        if (
            str_contains($catDeltron, 'TABLET') || str_contains($subDeltron, 'TABLET') ||
            str_contains($titulo, 'TABLET') || str_starts_with($name, 'TB ') || str_starts_with($name, 'TAB ')
        ) {
            return self::GRUPO_TABLET;
        }

        // Smartwatch
        if (str_contains($catDeltron, 'SMARTWATCH') || str_contains($titulo, 'SMARTWATCH') || str_contains($titulo, 'RELOJ SMART')) {
            return self::GRUPO_SMARTWATCH;
        }

        return null;
    }

    public function classify(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';

        if (
            str_contains($catDeltron, 'TELEFONOS CELULARES') || str_contains($catDeltron, 'SMARTPHONE') ||
            str_contains($subDeltron, 'CELULAR') || str_contains($subDeltron, 'SMARTPHONE')
        ) {
            if (str_contains($catDeltron, 'ACCESORIO') || str_contains($subDeltron, 'ACCESORIO')) {
                if (str_contains($titulo, 'CARGADOR') || str_contains($name, 'ADAP')) return self::GRUPO_CARGADORES;
                if (str_contains($titulo, 'CASE') || str_contains($titulo, 'FUNDA') || str_contains($titulo, 'CARCASA')) return self::GRUPO_CARCASAS;
                return self::GRUPO_CARGADORES;
            }
            return self::GRUPO_CELULARES;
        }

        return null;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        if ($idGrupo === self::GRUPO_CELULARES || $idGrupo === self::GRUPO_TABLET) {
            return [2, 3, 4, 5, 6, 9, 10, 14, 15, 16, 17, 21, 45, 51, 52];
        }
        return [10, 14, 42, 51, 52, 63, 72];
    }

    public function enrichAndNormalizeSpecs(
        int $idGrupo,
        array $specsMapeadas,
        array $rawSpecs,
        string $textoCompleto,
        array $sugerenciasPorChar = []
    ): array {
        $textoAnalisis = $textoCompleto . ' ' . implode(' ', $rawSpecs);

        if ($idGrupo === self::GRUPO_CELULARES || $idGrupo === self::GRUPO_TABLET) {
            if (empty($specsMapeadas[5])) {
                $ram = $this->extractMemoriaRam($textoAnalisis);
                if ($ram) $specsMapeadas[5] = $ram;
            }
            if (empty($specsMapeadas[6])) {
                $alm = $this->extractAlmacenamiento($textoAnalisis);
                if ($alm) {
                    $specsMapeadas[6] = $alm;
                    $specsMapeadas[45] = $alm;
                }
            }
            if (empty($specsMapeadas[2])) {
                $p = $this->extractPulgadas($textoAnalisis);
                if ($p) $specsMapeadas[2] = $p;
            }
            if (empty($specsMapeadas[15])) {
                if (stripos($textoAnalisis, 'IPAD') !== false || stripos($textoAnalisis, 'IOS') !== false) {
                    $specsMapeadas[15] = 'iPadOS';
                } else {
                    $specsMapeadas[15] = 'Android';
                }
            }
        }

        return $specsMapeadas;
    }
}

