<?php

namespace App\Services\Deltron\Categories\Cat06_Impresoras;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class ImpresorasCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 6;
    public const CATEGORIA_NOMBRE = 'Impresoras';

    public const GRUPO_IMPRESORA_TERMICA   = 40;
    public const GRUPO_IMPRESORA_LASER     = 41;
    public const GRUPO_IMPRESORA_TINTA     = 42;
    public const GRUPO_IMPRESORA_MATRICIAL = 43;
    public const GRUPO_TINTAS              = 44;
    public const GRUPO_CARTUCHOS           = 45;
    public const GRUPO_ALMOHADILLAS        = 46;
    public const GRUPO_CAJA_MANTENIMIENTO  = 47;
    public const GRUPO_CHIP_IMPRESORA      = 48;
    public const GRUPO_BOMBA_SUCCION       = 75;
    public const GRUPO_RESETEADORES        = 77;
    public const GRUPO_PAPELERIA           = 78;
    public const GRUPO_CABEZALES           = 79;
    public const GRUPO_CINTA_MATRICIAL     = 83;
    public const GRUPO_TONER               = 84;
    public const GRUPO_CABLE_FLEX          = 86;
    public const GRUPO_OTROS_SUMINISTROS   = 88;
    public const GRUPO_FUENTE_IMPRESORA    = 90;
    public const GRUPO_RODILLOS            = 93;
    public const GRUPO_ESCANER             = 97;
    public const GRUPO_BANDEJAS            = 98;

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
            self::GRUPO_IMPRESORA_TERMICA,
            self::GRUPO_IMPRESORA_LASER,
            self::GRUPO_IMPRESORA_TINTA,
            self::GRUPO_IMPRESORA_MATRICIAL,
            self::GRUPO_TINTAS,
            self::GRUPO_CARTUCHOS,
            self::GRUPO_ALMOHADILLAS,
            self::GRUPO_CAJA_MANTENIMIENTO,
            self::GRUPO_CHIP_IMPRESORA,
            self::GRUPO_BOMBA_SUCCION,
            self::GRUPO_RESETEADORES,
            self::GRUPO_PAPELERIA,
            self::GRUPO_CABEZALES,
            self::GRUPO_CINTA_MATRICIAL,
            self::GRUPO_TONER,
            self::GRUPO_CABLE_FLEX,
            self::GRUPO_OTROS_SUMINISTROS,
            self::GRUPO_FUENTE_IMPRESORA,
            self::GRUPO_RODILLOS,
            self::GRUPO_ESCANER,
            self::GRUPO_BANDEJAS,
        ];
    }

    public function classifyEarly(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';

        // Escáneres
        if (
            str_contains($catDeltron, 'ESCANER') ||
            str_contains($subDeltron, 'ESCANER') ||
            str_contains($titulo, 'ESCANER') ||
            str_contains($titulo, 'ESCÁNER') ||
            str_contains($titulo, 'SCANNER') ||
            str_contains($subDeltron, 'SCAN/COD/BARRAS')
        ) {
            return self::GRUPO_ESCANER;
        }

        return null;
    }

    public function classify(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';

        // Impresoras físicas
        if (str_contains($catDeltron, 'IMPRESORA') || str_contains($subDeltron, 'IMPRESORA') || str_contains($titulo, 'PRINTER')) {
            if (str_contains($titulo, 'LASER') || str_contains($subDeltron, 'LASER')) return self::GRUPO_IMPRESORA_LASER;
            if (str_contains($titulo, 'TERMICA') || str_contains($titulo, 'TERM') || str_contains($subDeltron, 'TERMICA')) return self::GRUPO_IMPRESORA_TERMICA;
            if (str_contains($titulo, 'MATRICIAL')) return self::GRUPO_IMPRESORA_MATRICIAL;
            return self::GRUPO_IMPRESORA_TINTA;
        }

        // Suministros directos
        if (str_contains($catDeltron, 'SUMINISTROS') || str_contains($subDeltron, 'SUMINIST')) {
            if (str_contains($subDeltron, 'TONER') || str_contains($titulo, 'TONER')) return self::GRUPO_TONER;
            if (str_contains($subDeltron, 'TINTAS') || str_contains($subDeltron, 'BOTELLAS') || str_contains($titulo, 'TINTA') || str_contains($titulo, 'BOTELLA')) return self::GRUPO_TINTAS;
            if (str_contains($titulo, 'CINTA')) return self::GRUPO_CINTA_MATRICIAL;
            if (str_contains($titulo, 'CARTUCHO')) return self::GRUPO_CARTUCHOS;
            return self::GRUPO_TINTAS;
        }

        return null;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        // Impresoras completas
        if (in_array($idGrupo, [self::GRUPO_IMPRESORA_TERMICA, self::GRUPO_IMPRESORA_LASER, self::GRUPO_IMPRESORA_TINTA, self::GRUPO_IMPRESORA_MATRICIAL], true)) {
            return [
                9, 10, 11, 12, 13, 14, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 51, 52, 63,
            ];
        }

        // Tintas y consumibles
        return [14, 40, 51, 52, 61, 62, 63, 73, 74, 82, 84];
    }

    public function enrichAndNormalizeSpecs(
        int $idGrupo,
        array $specsMapeadas,
        array $rawSpecs,
        string $textoCompleto,
        array $sugerenciasPorChar = []
    ): array {
        $textoAnalisis = $textoCompleto . ' ' . implode(' ', $rawSpecs);

        // 1. Tecnología de impresión (31)
        if (empty($specsMapeadas[31])) {
            $specsMapeadas[31] = match ($idGrupo) {
                self::GRUPO_IMPRESORA_LASER     => 'Láser',
                self::GRUPO_IMPRESORA_TERMICA   => 'Térmica',
                self::GRUPO_IMPRESORA_MATRICIAL => 'Matricial',
                default                         => 'Inyección de tinta',
            };
        }

        // 2. Conectividad (9)
        if (empty($specsMapeadas[9])) {
            $con = [];
            if (stripos($textoAnalisis, 'WIFI') !== false || stripos($textoAnalisis, 'WI-FI') !== false) $con[] = 'Wi-Fi';
            if (stripos($textoAnalisis, 'ETHERNET') !== false || stripos($textoAnalisis, 'RJ45') !== false || stripos($textoAnalisis, 'RED') !== false) $con[] = 'Ethernet';
            if (stripos($textoAnalisis, 'BLUETOOTH') !== false) $con[] = 'Bluetooth';
            $con[] = 'USB';
            $specsMapeadas[9] = implode(', ', array_unique($con));
        }

        // 3. Funciones (33)
        if (empty($specsMapeadas[33])) {
            if (preg_match('/\b(MULTIFUNCIONAL|ALL IN ONE|AIO|COPIADORA|ESCANER)\b/i', $textoAnalisis)) {
                $specsMapeadas[33] = 'Imprime, Escanea, Copia';
            } else {
                $specsMapeadas[33] = 'Impresión';
            }
        }

        // 4. Color de tinta o consumible (14)
        if (empty($specsMapeadas[14])) {
            if (preg_match('/\b(CYAN|CIAN)\b/i', $textoAnalisis)) $specsMapeadas[14] = 'Cyan';
            elseif (preg_match('/\b(MAGENTA)\b/i', $textoAnalisis)) $specsMapeadas[14] = 'Magenta';
            elseif (preg_match('/\b(YELLOW|AMARILLO)\b/i', $textoAnalisis)) $specsMapeadas[14] = 'Amarillo';
            elseif (preg_match('/\b(BLACK|NEGRO)\b/i', $textoAnalisis)) $specsMapeadas[14] = 'Negro';
        }

        return $specsMapeadas;
    }
}

