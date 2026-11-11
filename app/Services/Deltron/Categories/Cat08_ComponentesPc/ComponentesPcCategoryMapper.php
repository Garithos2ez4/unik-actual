<?php

namespace App\Services\Deltron\Categories\Cat08_ComponentesPc;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class ComponentesPcCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 8;
    public const CATEGORIA_NOMBRE = 'Componentes PC';

    public const GRUPO_PROCESADORES    = 49;
    public const GRUPO_PLACAS_MADRE    = 50;
    public const GRUPO_MEMORIAS_RAM    = 51;
    public const GRUPO_DISCOS_DURO     = 52;
    public const GRUPO_FUENTE_PODER    = 53;
    public const GRUPO_CASE            = 54;
    public const GRUPO_TARJETAS_VIDEO  = 55;
    public const GRUPO_COOLERS         = 56;
    public const GRUPO_SOPORTE_DISCO   = 57;
    public const GRUPO_FUNDAS_HDD      = 80;
    public const GRUPO_DISCO_SOLIDO    = 94;

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
            self::GRUPO_PROCESADORES,
            self::GRUPO_PLACAS_MADRE,
            self::GRUPO_MEMORIAS_RAM,
            self::GRUPO_DISCOS_DURO,
            self::GRUPO_FUENTE_PODER,
            self::GRUPO_CASE,
            self::GRUPO_TARJETAS_VIDEO,
            self::GRUPO_COOLERS,
            self::GRUPO_SOPORTE_DISCO,
            self::GRUPO_FUNDAS_HDD,
            self::GRUPO_DISCO_SOLIDO,
        ];
    }

    public function classifyEarly(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $sku        = strtoupper($context['item']['sku'] ?? '');

        // Cases & Gabinetes PC (Prioridad temprana sobre categorías generales)
        if (
            !str_contains($catDeltron, 'NOTEBOOK') &&
            !str_contains($subDeltron, 'NOTEBOOK') &&
            !str_starts_with($titulo, 'NB ') &&
            (
                str_contains($catDeltron, 'CASES (CAJAS)') ||
                str_contains($catDeltron, 'CASES, FUENTE') ||
                str_contains($catDeltron, 'CASES') ||
                str_contains($subDeltron, 'CASES') ||
                str_starts_with($titulo, 'CASE ') ||
                str_starts_with($titulo, 'CASE DEL TIPO') ||
                str_contains($titulo, 'OPEN-FRAME') ||
                str_contains($titulo, 'GABINETE') ||
                str_contains($titulo, 'MID-TOWER') ||
                str_contains($titulo, 'MINI-TOWER') ||
                str_contains($titulo, 'FULL-TOWER') ||
                str_starts_with($sku, 'CS')
            )
        ) {
            if (
                str_contains($catDeltron, 'CASES, FUENTE') ||
                (str_contains($subDeltron, 'FUENTE') && !str_contains($subDeltron, 'SIN FUENTE') && !str_contains($subDeltron, 'S/FUENTE')) ||
                str_contains($titulo, 'FUENTE DE PODER') ||
                str_contains($titulo, 'POWER SUPPLY')
            ) {
                return self::GRUPO_FUENTE_PODER;
            }
            return self::GRUPO_CASE;
        }

        // Coolers
        if (
            str_contains($catDeltron, 'FAN COOLER') ||
            str_contains($titulo, 'COOLER PARA CPU') ||
            str_contains($titulo, 'COOLER PARA PROCESADOR') ||
            str_contains($subDeltron, 'COOLER LIQUIDO') ||
            str_contains($subDeltron, 'FAN COOLER')
        ) {
            return self::GRUPO_COOLERS;
        }

        return null;
    }

    public function classify(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';

        // 1. Procesadores (CPU)
        if (
            str_contains($catDeltron, 'CPU') || str_contains($catDeltron, 'MICROPROCESADOR') ||
            str_contains($subDeltron, 'CPU AMD') || str_contains($subDeltron, 'CPU INTEL') ||
            str_starts_with($name, 'CPAM') || str_starts_with($name, 'CPIN') ||
            str_contains($titulo, 'PROCESADOR AMD') || str_contains($titulo, 'PROCESADOR INTEL')
        ) {
            return self::GRUPO_PROCESADORES;
        }

        // 2. Tarjetas de Video
        if (
            str_contains($catDeltron, 'TARJETAS DE VIDEO') || str_contains($catDeltron, 'DISPOSITIVOS DE VIDEO') ||
            str_contains($subDeltron, 'VIDEO') || str_contains($titulo, 'TARJETA DE VIDEO') ||
            str_starts_with($name, 'VGA ') || str_starts_with($name, 'VD')
        ) {
            return self::GRUPO_TARJETAS_VIDEO;
        }

        // 3. Motherboards / Placas Madre
        if (str_contains($catDeltron, 'MOTHERBOARD') || str_contains($subDeltron, 'MB SOCKET') || str_contains($catDeltron, 'PLACA')) {
            return self::GRUPO_PLACAS_MADRE;
        }

        // 4. Memorias RAM
        if (str_contains($catDeltron, 'MEMORIAS RAM') || str_contains($subDeltron, 'MEM DDR')) {
            return self::GRUPO_MEMORIAS_RAM;
        }

        // 5. SSD / Disco Sólido
        $sku = strtoupper($context['item']['sku'] ?? '');
        $esNasOServidor = str_starts_with($sku, 'NAS') || str_starts_with($sku, 'SRV') ||
            str_contains($titulo, 'SERVIDOR') || str_contains($titulo, 'SERVER') ||
            (str_contains($titulo, 'NAS') && !str_contains($titulo, 'DISCO') && !str_contains($titulo, 'HDD') && !str_contains($titulo, 'SSD')) ||
            str_contains($subDeltron, 'NAS') || str_contains($subDeltron, 'SERVIDORES') ||
            str_contains($titulo, 'ASUSTOR') || str_contains($titulo, 'SYNOLOGY') || str_contains($titulo, 'QNAP');

        if (
            !$esNasOServidor &&
            !str_contains($catDeltron, 'NOTEBOOK') && !str_contains($catDeltron, 'PORTATIL') && !str_contains($catDeltron, 'COMPUTADORA') &&
            !str_starts_with($name, 'NB ') && !str_starts_with($titulo, 'NB ') &&
            (str_contains($catDeltron, 'DISCO SOLIDO') || str_contains($catDeltron, 'UNIDADES DE DISCO SOLIDO') ||
             (str_contains($subDeltron, 'SSD') && !str_contains($subDeltron, 'NOTEBOOK')) ||
             (str_contains($catDeltron, 'STORAGE') && !str_contains($subDeltron, 'NAS')) ||
             str_starts_with($titulo, 'SSD ') || str_starts_with($name, 'SSD '))
        ) {
            return self::GRUPO_DISCO_SOLIDO;
        }

        // 6. Discos Duros (HDD)
        if (str_contains($catDeltron, 'DISCOS DUROS') || str_contains($subDeltron, 'DISCO DURO')) {
            return self::GRUPO_DISCOS_DURO;
        }

        // 7. Cases & Fuentes
        $sku = strtoupper($context['item']['sku'] ?? '');
        if (
            str_contains($catDeltron, 'CASES (CAJAS)') ||
            str_contains($catDeltron, 'CASES, FUENTE') ||
            str_contains($catDeltron, 'CASES') ||
            str_contains($subDeltron, 'CASES') ||
            str_starts_with($titulo, 'CASE ') ||
            str_starts_with($titulo, 'CASE DEL TIPO') ||
            str_contains($titulo, 'OPEN-FRAME') ||
            str_contains($titulo, 'GABINETE') ||
            str_contains($titulo, 'MID-TOWER') ||
            str_contains($titulo, 'MINI-TOWER') ||
            str_contains($titulo, 'FULL-TOWER') ||
            str_starts_with($sku, 'CS')
        ) {
            if (
                str_contains($catDeltron, 'CASES, FUENTE') ||
                (str_contains($subDeltron, 'FUENTE') && !str_contains($subDeltron, 'SIN FUENTE') && !str_contains($subDeltron, 'S/FUENTE')) ||
                str_contains($titulo, 'FUENTE DE PODER') ||
                str_contains($titulo, 'POWER SUPPLY')
            ) {
                return self::GRUPO_FUENTE_PODER;
            }
            return self::GRUPO_CASE;
        }

        return null;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        $base = [10, 11, 12, 13, 14, 51, 52, 53, 63];

        return match ($idGrupo) {
            self::GRUPO_PROCESADORES   => [4, 9, 10, 11, 12, 13, 37, 48, 51, 52, 63, 90, 91],
            self::GRUPO_PLACAS_MADRE   => [4, 9, 17, 46, 51, 52, 63, 76, 77],
            self::GRUPO_MEMORIAS_RAM   => [5, 46, 49, 50, 51, 52, 80, 81],
            self::GRUPO_DISCOS_DURO,
            self::GRUPO_DISCO_SOLIDO   => [6, 45, 46, 47, 48, 49, 50, 51, 52],
            self::GRUPO_FUENTE_PODER   => [1, 41, 42, 43, 44, 51, 52],
            self::GRUPO_CASE           => [17, 51, 52, 69, 70, 71, 76, 77, 78],
            self::GRUPO_TARJETAS_VIDEO => [7, 17, 42, 51, 52, 83, 84],
            self::GRUPO_COOLERS        => [10, 14, 42, 46, 51, 52, 69, 78],
            default                    => $base,
        };
    }

    public function enrichAndNormalizeSpecs(
        int $idGrupo,
        array $specsMapeadas,
        array $rawSpecs,
        string $textoCompleto,
        array $sugerenciasPorChar = []
    ): array {
        $textoAnalisis = $textoCompleto . "\n" . implode("\n", array_map(
            fn ($k, $v) => "{$k}: {$v}",
            array_keys($rawSpecs),
            array_values($rawSpecs)
        ));

        // 1. CASES / GABINETES (ID 54)
        if ($idGrupo === self::GRUPO_CASE) {
            $specsMapeadas = $this->enrichCaseSpecs($specsMapeadas, $rawSpecs, $textoAnalisis);
        }

        // 2. FUENTES DE PODER (ID 53)
        if ($idGrupo === self::GRUPO_FUENTE_PODER) {
            if (empty($specsMapeadas[42])) {
                $w = $this->extractWatts($textoAnalisis);
                if ($w) $specsMapeadas[42] = $w;
            }
            if (empty($specsMapeadas[41])) {
                if (preg_match('/(80\s*PLUS(?:\s*(?:BRONZE|GOLD|PLATINUM|WHITE|TITANIUM))?)/i', $textoAnalisis, $mPlus)) {
                    $specsMapeadas[41] = ucwords(strtolower(trim($mPlus[1])));
                }
            }
        }

        // 3. PROCESADORES (ID 49)
        if ($idGrupo === self::GRUPO_PROCESADORES) {
            if (empty($specsMapeadas[4])) {
                if (preg_match('/(RYZEN\s+[3579]\s+\d{4}[A-Z]*|CORE\s+I[3579]-?\d{4,5}[A-Z]*|CORE\s+ULTRA\s+[579]\s+\d{3}[A-Z]*)/i', $textoAnalisis, $mCpu)) {
                    $specsMapeadas[4] = ucwords(strtolower(trim($mCpu[1])));
                }
            }
            if (empty($specsMapeadas[90])) {
                if (preg_match('/\b(\d{1,2})\s*(?:NÚCLEOS|NUCLEOS|CORES)\b/i', $textoAnalisis, $mC)) {
                    $specsMapeadas[90] = $mC[1];
                }
            }
        }

        // 4. MEMORIAS RAM (ID 51)
        if ($idGrupo === self::GRUPO_MEMORIAS_RAM) {
            if (empty($specsMapeadas[5])) {
                $r = $this->extractMemoriaRam($textoAnalisis);
                if ($r) $specsMapeadas[5] = $r;
            }
            if (empty($specsMapeadas[80])) {
                if (preg_match('/\b(DDR5|DDR4|DDR3)\b/i', $textoAnalisis, $mDdr)) {
                    $specsMapeadas[80] = strtoupper($mDdr[1]);
                }
            }
            if (empty($specsMapeadas[81])) {
                if (preg_match('/\b(\d{4})\s*MHZ\b/i', $textoAnalisis, $mMhz)) {
                    $specsMapeadas[81] = $mMhz[1] . ' MHz';
                }
            }
            if (empty($specsMapeadas[49])) {
                $specsMapeadas[49] = preg_match('/\b(SO-?DIMM|NOTEBOOK|LAPTOP)\b/i', $textoAnalisis) ? 'SODIMM' : 'DIMM';
            }
        }

        // 5. DISCOS SSD / HDD (ID 94 / 52)
        if ($idGrupo === self::GRUPO_DISCO_SOLIDO || $idGrupo === self::GRUPO_DISCOS_DURO) {
            if (empty($specsMapeadas[6]) && empty($specsMapeadas[45])) {
                $alm = $this->extractAlmacenamiento($textoAnalisis);
                if ($alm) {
                    $specsMapeadas[6] = $alm;
                    $specsMapeadas[45] = $alm;
                }
            }
            if (empty($specsMapeadas[46])) {
                if (preg_match('/\b(NVME|PCIE|M\.2)\b/i', $textoAnalisis)) {
                    $specsMapeadas[46] = 'PCIe NVMe M.2';
                } elseif (preg_match('/\bSATA\b/i', $textoAnalisis)) {
                    $specsMapeadas[46] = 'SATA III';
                }
            }
        }

        // 6. TARJETAS DE VIDEO (ID 55)
        if ($idGrupo === self::GRUPO_TARJETAS_VIDEO) {
            if (empty($specsMapeadas[83])) {
                if (preg_match('/\b(\d{1,2})\s*GB\b/i', $textoAnalisis, $mVram)) {
                    $specsMapeadas[83] = $mVram[1] . ' GB';
                }
            }
            if (empty($specsMapeadas[84])) {
                if (preg_match('/\b(RTX|GTX|GEFORCE|NVIDIA)\b/i', $textoAnalisis)) {
                    $specsMapeadas[84] = 'NVIDIA';
                } elseif (preg_match('/\b(RADEON|RX|AMD)\b/i', $textoAnalisis)) {
                    $specsMapeadas[84] = 'AMD';
                }
            }
        }

        return $specsMapeadas;
    }

    protected function enrichCaseSpecs(array $specs, array $rawSpecs, string $textoAnalisis): array
    {
        // Factor de forma (77) y Formato de placa (76)
        $factorForma = $rawSpecs['FACTOR DE FORMA'] ?? null;
        if (!$factorForma && preg_match('/\b(MID\s+TOWER|FULL\s+TOWER|MINI\s+TOWER|MICRO\s+TOWER)\b/i', $textoAnalisis, $mff)) {
            $factorForma = $mff[1];
        }

        if ($factorForma) {
            $valTower = ucwords(strtolower(trim($factorForma)));
            $specs[77] = $valTower;
            $specs[76] = $valTower;
        }

        if (empty($specs[76])) {
            $mbs = $rawSpecs['MOTHERBOARDS SOPORTADAS (FF)'] ?? ($rawSpecs['MOTHERBOARDS SOPORTADAS'] ?? null);
            if ($mbs) {
                $specs[76] = trim($mbs);
            }
        }

        // Fuente de poder (70)
        $valF = strtoupper(trim((string)($specs[70] ?? '')));
        if ($valF === 'NO' || stripos($textoAnalisis, 'SIN FUENTE') !== false || ($rawSpecs['INCLUYE FUENTE DE PODER'] ?? '') === 'NO') {
            $specs[70] = 'Sin Fuente';
        } elseif ($valF === 'SI' || $valF === 'SÍ' || stripos($textoAnalisis, 'CON FUENTE') !== false || ($rawSpecs['INCLUYE FUENTE DE PODER'] ?? '') === 'SI' || preg_match('/\b\d{3}W\b/i', $textoAnalisis)) {
            $specs[70] = 'Incluye';
        } else {
            $specs[70] = 'Sin Fuente';
        }

        // Vidrio lateral (71)
        if (empty($specs[71])) {
            if (stripos($textoAnalisis, 'VIDRIO TEMPLADO') !== false || stripos($textoAnalisis, 'TEMPERED GLASS') !== false) {
                $specs[71] = 'Incluye';
            }
        }

        // Cooler (78)
        if (empty($specs[78])) {
            if (
                stripos($textoAnalisis, 'VENTILADORES ARGB INCLUIDOS: SI') !== false ||
                stripos($textoAnalisis, 'PRE INSTALADO') !== false ||
                stripos($textoAnalisis, 'COOLERS RGB') !== false ||
                stripos($textoAnalisis, 'COOLER') !== false
            ) {
                $specs[78] = 'Si';
            }
        }

        // Led (69)
        if (empty($specs[69])) {
            if (stripos($textoAnalisis, 'ARGB') !== false || stripos($textoAnalisis, 'RGB') !== false) {
                $specs[69] = 'RGB';
            }
        }

        // Dimensiones paquete (51)
        if (empty($specs[51]) && !empty($rawSpecs['EMPAQUE (ALTO X ANCHO X LARGO)'])) {
            $specs[51] = strtolower(trim($rawSpecs['EMPAQUE (ALTO X ANCHO X LARGO)']));
        }

        // Peso paquete (52)
        if (empty($specs[52]) && !empty($rawSpecs['PESO BRUTO'])) {
            if (preg_match('/([\d\.]+)/', $rawSpecs['PESO BRUTO'], $mpb)) {
                $specs[52] = $mpb[1];
            }
        }

        return $specs;
    }
}
