<?php

namespace App\Services\Deltron\Categories\Cat01_Laptops;

use App\Services\Deltron\Categories\AbstractCategorySpecMapper;

class LaptopsCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 1;
    public const CATEGORIA_NOMBRE = 'Laptops';

    public const GRUPO_LAPTOP_GAMER       = 1;
    public const GRUPO_LAPTOP_EMPRESARIAL = 2;
    public const GRUPO_LAPTOP_HOGAR       = 3;
    public const GRUPO_COOLER_LAPTOP      = 4;
    public const GRUPO_FUNDAS_LAPTOP      = 5;
    public const GRUPO_LAPIZ_TACTIL       = 6;
    public const GRUPO_CARGADORES_LAPTOP  = 69;
    public const GRUPO_REPUESTOS_LAPTOP   = 92;
    public const GRUPO_BATERIA_LAPTOP     = 101;

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
            self::GRUPO_LAPTOP_GAMER,
            self::GRUPO_LAPTOP_EMPRESARIAL,
            self::GRUPO_LAPTOP_HOGAR,
            self::GRUPO_COOLER_LAPTOP,
            self::GRUPO_FUNDAS_LAPTOP,
            self::GRUPO_LAPIZ_TACTIL,
            self::GRUPO_CARGADORES_LAPTOP,
            self::GRUPO_REPUESTOS_LAPTOP,
            self::GRUPO_BATERIA_LAPTOP,
        ];
    }

    public function classifyEarly(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';

        if (
            str_contains($catDeltron, 'NOTEBOOK , ACCESORIOS') ||
            str_contains($catDeltron, 'ACCESORIOS DE NOTEBOOK') ||
            str_contains($catDeltron, 'ACCESORIOS NOTEBOOK') ||
            str_contains($subDeltron, 'NOTEBOOK, ACCESORIOS') ||
            str_contains($subDeltron, 'ACCESORIOS NOTEBOOK')
        ) {
            if (str_contains($titulo, 'HUB') || str_contains($name, 'HUB') || str_contains($titulo, 'MULTIPUERTO') || str_contains($titulo, 'DOCKING')) {
                return 87; // Multipuertos (Cables)
            }
            if (str_contains($titulo, 'CARGADOR') || str_contains($titulo, 'ADAPTADOR') || str_contains($subDeltron, 'CARGADOR') || str_contains($name, 'ADAP')) {
                return self::GRUPO_CARGADORES_LAPTOP;
            }
            if (str_contains($titulo, 'COOLER') || str_contains($titulo, 'BASE') || str_contains($titulo, 'PAD DE ENFRIAMIENTO') || str_contains($titulo, 'STAND')) {
                return self::GRUPO_COOLER_LAPTOP;
            }
            if (str_contains($titulo, 'FUNDA') || str_contains($titulo, 'CASE') || str_contains($titulo, 'SLEEVE')) {
                return self::GRUPO_FUNDAS_LAPTOP;
            }
            if (str_contains($titulo, 'LAPIZ') || str_contains($titulo, 'STYLUS') || str_contains($titulo, 'PEN')) {
                return self::GRUPO_LAPIZ_TACTIL;
            }
            if (str_contains($titulo, 'BATERIA') || str_contains($titulo, 'BATERÍA')) {
                return self::GRUPO_BATERIA_LAPTOP;
            }
            return self::GRUPO_REPUESTOS_LAPTOP;
        }

        return null;
    }

    public function classify(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';
        $sku        = strtoupper($context['item']['sku'] ?? '');

        $esLaptop = (
            str_contains($catDeltron, 'NOTEBOOK') ||
            str_contains($catDeltron, 'PORTATIL') ||
            str_contains($catDeltron, 'LAPTOP') ||
            str_contains($subDeltron, 'NOTEBOOK') ||
            str_contains($subDeltron, 'PORTATIL') ||
            str_contains($subDeltron, 'LAPTOP') ||
            str_contains($titulo, 'LAPTOP') ||
            str_contains($titulo, 'NOTEBOOK') ||
            str_contains($titulo, 'MACBOOK') ||
            str_contains($titulo, 'CHROMEBOOK') ||
            str_starts_with($titulo, 'NB ') ||
            str_starts_with($name, 'NB ') ||
            str_starts_with($sku, 'NB') ||
            preg_match('/\b(ELITEBOOK|THINKPAD|PROBOOK|LATITUDE|EXPERTBOOK|IDEAPAD|VIVOBOOK|ZENBOOK|YOGA|ASPIRE|SWIFT|PAVILION|ENVY|SPECTRE|OMEN|VICTUS|LOQ|LEGION|TUF|ROG|PREDATOR|NITRO|VOSTRO|MACBOOK)\b/i', $titulo . ' ' . $name)
        );

        if (!$esLaptop) {
            return null;
        }

        $fuente = $titulo . ' ' . $subDeltron . ' ' . $name;

        if (
            str_contains($fuente, 'GAMER') ||
            str_contains($fuente, 'GAMING') ||
            str_contains($fuente, 'RTX') ||
            str_contains($fuente, 'LOQ') ||
            str_contains($fuente, 'VICTUS') ||
            str_contains($fuente, 'TUF') ||
            str_contains($fuente, 'ROG') ||
            str_contains($fuente, 'PREDATOR') ||
            str_contains($fuente, 'NITRO') ||
            str_contains($fuente, 'LEGION') ||
            str_contains($fuente, 'OMEN') ||
            str_contains($fuente, 'KATANA') ||
            str_contains($fuente, 'CYBORG') ||
            str_contains($fuente, 'SWORD') ||
            str_contains($fuente, 'RAIDER') ||
            str_contains($fuente, 'STRIX') ||
            str_contains($fuente, 'ZEPHYRUS') ||
            str_contains($fuente, 'HELIOS') ||
            preg_match('/\b(GTX\s*\d{4}|RTX\s*\d{4})\b/i', $fuente)
        ) {
            return self::GRUPO_LAPTOP_GAMER;
        }

        if (
            str_contains($fuente, 'THINKPAD') ||
            str_contains($fuente, 'PROBOOK') ||
            str_contains($fuente, 'LATITUDE') ||
            str_contains($fuente, 'EXPERTBOOK') ||
            str_contains($fuente, 'ELITEBOOK') ||
            str_contains($fuente, 'VOSTRO') ||
            str_contains($fuente, 'PRECISION') ||
            str_contains($fuente, 'ZBOOK') ||
            str_contains($fuente, 'THINKBOOK') ||
            str_contains($fuente, 'WORKSTATION') ||
            str_contains($fuente, 'EMPRESARIAL') ||
            str_contains($fuente, 'CORPORATIV')
        ) {
            return self::GRUPO_LAPTOP_EMPRESARIAL;
        }

        return self::GRUPO_LAPTOP_HOGAR;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        if (in_array($idGrupo, [self::GRUPO_LAPTOP_GAMER, self::GRUPO_LAPTOP_EMPRESARIAL, self::GRUPO_LAPTOP_HOGAR], true)) {
            return [
                1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 21, 25, 29, 37, 51, 52, 63, 67, 69, 90, 91,
            ];
        }

        return [9, 10, 11, 12, 13, 14, 26, 27, 42, 51, 52, 53, 63, 72, 85, 91, 101];
    }

    public function enrichAndNormalizeSpecs(
        int $idGrupo,
        array $specsMapeadas,
        array $rawSpecs,
        string $textoCompleto,
        array $sugerenciasPorChar = []
    ): array {
        if (!in_array($idGrupo, [self::GRUPO_LAPTOP_GAMER, self::GRUPO_LAPTOP_EMPRESARIAL, self::GRUPO_LAPTOP_HOGAR], true)) {
            return $this->enrichAccesorioLaptopSpecs($idGrupo, $specsMapeadas, $rawSpecs, $textoCompleto);
        }

        $textoAnalisis = $textoCompleto . "\n" . implode("\n", array_map(
            fn ($k, $v) => "{$k}: {$v}",
            array_keys($rawSpecs),
            array_values($rawSpecs)
        ));

        // 1. Procesador (4)
        $specsMapeadas[4] = $this->resolveProcesador($specsMapeadas[4] ?? ($rawSpecs['PROCESADOR'] ?? ''), $textoAnalisis);

        // 2. Núcleos (90)
        $specsMapeadas[90] = $this->resolveNucleos($specsMapeadas[90] ?? ($rawSpecs['NÚCLEOS'] ?? ($rawSpecs['NUMERO DE NUCLEOS'] ?? '')), $specsMapeadas[4], $textoAnalisis);

        // 3. Pulgadas (2)
        $specsMapeadas[2] = $this->resolvePulgadas($specsMapeadas[2] ?? ($rawSpecs['PULGADAS'] ?? ($rawSpecs['TAMAÑO DE PANTALLA'] ?? '')), $textoAnalisis);

        // 4. Memoria RAM (5)
        $specsMapeadas[5] = $this->resolveMemoriaRam($specsMapeadas[5] ?? ($rawSpecs['MEMORIA RAM'] ?? ($rawSpecs['RAM'] ?? '')), $textoAnalisis);

        // 5. Almacenamiento (6)
        $specsMapeadas[6] = $this->resolveAlmacenamiento($specsMapeadas[6] ?? ($rawSpecs['ALMACENAMIENTO'] ?? ($rawSpecs['DISCO DURO'] ?? '')), $textoAnalisis);

        // 6. Resolución (3)
        $specsMapeadas[3] = $this->resolveResolucion($specsMapeadas[3] ?? ($rawSpecs['RESOLUCIÓN'] ?? ($rawSpecs['RESOLUCION'] ?? '')), $textoAnalisis);

        // 7. Sistema Operativo (15)
        $specsMapeadas[15] = $this->resolveSistemaOperativo($specsMapeadas[15] ?? ($rawSpecs['SISTEMA OPERATIVO'] ?? ''), $textoAnalisis);

        // 8. Gráficos (7)
        $specsMapeadas[7] = $this->resolveGraficos($specsMapeadas[7] ?? ($rawSpecs['GRAFICOS'] ?? ($rawSpecs['TARJETA DE VIDEO'] ?? '')), $idGrupo, $specsMapeadas[4], $textoAnalisis);

        // 9. Hz (1)
        $specsMapeadas[1] = $this->resolveHz($specsMapeadas[1] ?? ($rawSpecs['TASA DE REFRESCO'] ?? ''), $idGrupo, $textoAnalisis);

        // 10. Color (14)
        $specsMapeadas[14] = $this->resolveColorLaptop($specsMapeadas[14] ?? ($rawSpecs['COLOR'] ?? ''), $textoAnalisis);

        // 11. Segmento (91)
        $specsMapeadas[91] = match ($idGrupo) {
            self::GRUPO_LAPTOP_GAMER       => 'Gamer',
            self::GRUPO_LAPTOP_EMPRESARIAL => 'Productividad',
            default                        => 'Estudiante',
        };

        // 12. Idioma (25)
        if (empty($specsMapeadas[25])) {
            $specsMapeadas[25] = (preg_match('/\b(INGLES|INGLÉS|ENGLISH|US\s+LAYOUT)\b/i', $textoAnalisis) && !preg_match('/\b(ESPAÑOL|ESPANOL|SPANISH|LATINO)\b/i', $textoAnalisis))
                ? 'Inglés' : 'Español';
        }

        // 13. Panel (21)
        if (empty($specsMapeadas[21])) {
            if (preg_match('/\bOLED\b/i', $textoAnalisis)) {
                $specsMapeadas[21] = 'OLED';
            } elseif (preg_match('/\b(WVA|VA)\b/i', $textoAnalisis)) {
                $specsMapeadas[21] = 'VA';
            } else {
                $specsMapeadas[21] = 'IPS';
            }
        }

        // 14. Batería (16)
        if (empty($specsMapeadas[16])) {
            if (preg_match('/\b(\d{1,2}\s*CELDAS?(?:\s*,\s*\d{2,3}\s*WH)?|\d{2,3}\s*WH)\b/i', $textoAnalisis, $mBat)) {
                $specsMapeadas[16] = ucwords(strtolower(trim($mBat[1])));
            } else {
                $specsMapeadas[16] = ($idGrupo === self::GRUPO_LAPTOP_GAMER) ? '4 Celdas 70WH' : '3 Celdas 41WH';
            }
        }

        // 15. Micrófono / Webcam (67)
        if (empty($specsMapeadas[67])) {
            $specsMapeadas[67] = 'Integrado con cámara web';
        }

        // 16. Led (69)
        if (empty($specsMapeadas[69])) {
            if ($idGrupo === self::GRUPO_LAPTOP_GAMER || preg_match('/\bRGB\b/i', $textoAnalisis)) {
                $specsMapeadas[69] = 'RGB';
            } elseif (preg_match('/\b(RETROILUMINAD|BACKLIT)\b/i', $textoAnalisis)) {
                $specsMapeadas[69] = 'Blanco';
            } else {
                $specsMapeadas[69] = 'Sin RGB';
            }
        }

        // 17. Dimensiones y peso
        return $this->ensureDimensionesYPesoLaptop($idGrupo, $specsMapeadas, $rawSpecs, $textoAnalisis);
    }

    protected function resolveProcesador(string $procActual, string $textoAnalisis): string
    {
        $proc = trim($procActual);
        if ($proc !== '' && !preg_match('/^(N\/A|VARIOS|NO)$/i', $proc)) {
            $proc = preg_replace('/(\d+(?:\.\d+)?\s*GHZ.*)$/i', '', $proc);
            $proc = preg_replace('/\s+HASTA.*$/i', '', $proc);
            $proc = preg_replace('/\s+CON TURBO.*$/i', '', $proc);
            $proc = trim($proc, " ,-");
            if (strlen($proc) >= 6) {
                return $proc;
            }
        }

        if (preg_match('/\b(INTEL\s+CORE\s+(?:ULTRA\s+[579]\s+\d{3}[A-Z]*|i[3579]-\d{4,5}[A-Z]*))\b/i', $textoAnalisis, $m)) {
            return ucwords(strtolower($m[1]));
        }
        if (preg_match('/\b(AMD\s+RYZEN\s+[3579]\s+\d{4}[A-Z]*)\b/i', $textoAnalisis, $m)) {
            return ucwords(strtolower($m[1]));
        }
        if (preg_match('/\b(APPLE\s+M[1234](?:\s+(?:PRO|MAX))?)\b/i', $textoAnalisis, $m)) {
            return ucwords(strtolower($m[1]));
        }

        return $proc !== '' ? $proc : 'Intel Core i5';
    }

    protected function resolveNucleos(string $coresActual, string $procesador, string $textoAnalisis): string
    {
        if (preg_match('/\b(\d{1,2})\b/', $coresActual, $m)) {
            return $m[1];
        }
        if (preg_match('/\b(\d{1,2})\s*(?:NÚCLEOS|NUCLEOS|CORES|C)\b/i', $textoAnalisis, $m)) {
            return $m[1];
        }

        $procUpper = strtoupper($procesador);
        if (str_contains($procUpper, '14900') || str_contains($procUpper, '13900')) return '24';
        if (str_contains($procUpper, '14700')) return '20';
        if (str_contains($procUpper, '13700') || str_contains($procUpper, '12700') || str_contains($procUpper, 'ULTRA 7')) return '16';
        if (str_contains($procUpper, '13600') || str_contains($procUpper, 'ULTRA 5')) return '14';
        if (str_contains($procUpper, '13500') || str_contains($procUpper, '12500')) return '12';
        if (str_contains($procUpper, '12450') || str_contains($procUpper, '13420') || str_contains($procUpper, '1235U') || str_contains($procUpper, '1335U')) return '10';
        if (str_contains($procUpper, 'RYZEN 7') || str_contains($procUpper, 'I7')) return '8';
        if (str_contains($procUpper, 'RYZEN 5') || str_contains($procUpper, '1215U')) return '6';
        if (str_contains($procUpper, 'RYZEN 3') || str_contains($procUpper, 'I3') || str_contains($procUpper, 'N100')) return '4';

        return '8';
    }

    protected function resolvePulgadas(string $pulgActual, string $textoAnalisis): string
    {
        $p = $this->extractPulgadas($pulgActual . ' ' . $textoAnalisis);
        return $p ?? '15.6"';
    }

    protected function resolveMemoriaRam(string $ramActual, string $textoAnalisis): string
    {
        $r = $this->extractMemoriaRam($ramActual . ' ' . $textoAnalisis);
        return $r ?? '16 GB';
    }

    protected function resolveAlmacenamiento(string $almActual, string $textoAnalisis): string
    {
        $a = $this->extractAlmacenamiento($almActual . ' ' . $textoAnalisis);
        return $a ?? '512 GB';
    }

    protected function resolveResolucion(string $resActual, string $textoAnalisis): string
    {
        $res = $this->extractResolucion($resActual . ' ' . $textoAnalisis);
        return $res ?? '1920 x 1080';
    }

    protected function resolveSistemaOperativo(string $soActual, string $textoAnalisis): string
    {
        $fuente = mb_strtolower($soActual . ' ' . $textoAnalisis);
        if (str_contains($fuente, 'mac') || str_contains($fuente, 'apple')) return 'Mac OS';
        if (str_contains($fuente, 'win 11 pro') || str_contains($fuente, 'windows 11 pro')) return 'Windows 11 Pro';
        if (str_contains($fuente, 'win 11') || str_contains($fuente, 'windows 11')) return 'Windows 11 Home';
        if (str_contains($fuente, 'win 10') || str_contains($fuente, 'windows 10')) return 'Windows 10';
        if (str_contains($fuente, 'free') || str_contains($fuente, 'dos') || str_contains($fuente, 'sin so')) return 'Free DOS';
        if (str_contains($fuente, 'linux') || str_contains($fuente, 'ubuntu')) return 'Linux';
        return 'Windows 11 Home';
    }

    protected function resolveGraficos(string $gpuActual, int $idGrupo, string $procesador, string $textoAnalisis): string
    {
        $fuente = $gpuActual . ' ' . $textoAnalisis;
        if (preg_match('/\b(RTX\s*\d{4}(?:\s*(?:TI|SUPER))?(?:\s*\d{1,2}\s*GB)?)\b/i', $fuente, $mGpu)) {
            return 'NVIDIA GeForce ' . strtoupper(trim($mGpu[1]));
        }
        if (preg_match('/\b(GTX\s*\d{4}(?:\s*(?:TI|SUPER))?(?:\s*\d{1,2}\s*GB)?)\b/i', $fuente, $mGpu)) {
            return 'NVIDIA GeForce ' . strtoupper(trim($mGpu[1]));
        }
        if (preg_match('/\b(RADEON\s*RX\s*\d{4}[A-Z]*(?:\s*\d{1,2}\s*GB)?)\b/i', $fuente, $mGpu)) {
            return 'AMD ' . ucwords(strtolower(trim($mGpu[1])));
        }
        if ($idGrupo === self::GRUPO_LAPTOP_GAMER) {
            return 'NVIDIA GeForce RTX 3050 6GB';
        }
        if (stripos($procesador, 'Ryzen') !== false || stripos($fuente, 'Radeon') !== false) {
            return 'AMD Radeon Graphics';
        }
        return 'Intel Iris Xe Graphics';
    }

    protected function resolveHz(string $hzActual, int $idGrupo, string $textoAnalisis): string
    {
        $hz = $this->extractHz($hzActual . ' ' . $textoAnalisis);
        if ($hz) return $hz;
        return ($idGrupo === self::GRUPO_LAPTOP_GAMER) ? '144 Hz' : '60 Hz';
    }

    protected function resolveColorLaptop(string $rawColor, string $textoAnalisis): string
    {
        $fuente = mb_strtolower($rawColor . ' ' . $textoAnalisis);
        if (str_contains($fuente, 'platead') || str_contains($fuente, 'silver') || str_contains($fuente, 'plata')) return 'Plateado';
        if (str_contains($fuente, 'negro') || str_contains($fuente, 'black')) return 'Negro';
        if (str_contains($fuente, 'azul') || str_contains($fuente, 'blue')) return 'Azul';
        if (str_contains($fuente, 'blanco') || str_contains($fuente, 'white')) return 'Blanco';
        if (str_contains($fuente, 'dorad') || str_contains($fuente, 'gold')) return 'Dorado';
        return 'Gris';
    }

    protected function ensureDimensionesYPesoLaptop(int $idGrupo, array $specs, array $rawSpecs, string $textoAnalisis): array
    {
        foreach ([11, 12, 13] as $idDim) {
            if (!empty($specs[$idDim])) {
                $val = (string) $specs[$idDim];
                if (preg_match('/([\d\.]+)\s*mm/i', $val, $mMm)) {
                    $specs[$idDim] = round(floatval($mMm[1]) / 10, 2) . ' cm';
                } elseif (preg_match('/([\d\.]+)/', $val, $mNum)) {
                    $num = floatval($mNum[1]);
                    if ($num > 70) $num = round($num / 10, 2);
                    $specs[$idDim] = $num . ' cm';
                }
            }
        }

        if (!empty($specs[10])) {
            $pesoVal = (string) $specs[10];
            if (preg_match('/([\d\.]+)\s*(g|gr|gramos)\b/i', $pesoVal, $mGr)) {
                $specs[10] = (string) round(floatval($mGr[1]) / 1000, 3);
            } elseif (preg_match('/([\d\.]+)/', $pesoVal, $mP)) {
                $numP = floatval($mP[1]);
                if ($numP > 100) $numP = round($numP / 1000, 3);
                if ($numP > 0) $specs[10] = (string) $numP;
            }
        }

        $defaults = match ($idGrupo) {
            self::GRUPO_LAPTOP_GAMER       => [11 => '2.5 cm', 12 => '26 cm', 13 => '36 cm', 10 => '2.3'],
            self::GRUPO_LAPTOP_EMPRESARIAL => [11 => '1.9 cm', 12 => '23 cm', 13 => '32.5 cm', 10 => '1.5'],
            default                        => [11 => '2 cm',   12 => '24 cm', 13 => '35.9 cm', 10 => '1.75'],
        };

        foreach ($defaults as $idCar => $defVal) {
            if (empty($specs[$idCar]) || trim((string) $specs[$idCar]) === '0') {
                $specs[$idCar] = $defVal;
            }
        }

        return $specs;
    }

    protected function enrichAccesorioLaptopSpecs(int $idGrupo, array $specs, array $rawSpecs, string $textoAnalisis): array
    {
        if (empty($specs[14])) $specs[14] = 'Negro';
        if (empty($specs[11])) $specs[11] = '4 cm';
        if (empty($specs[12])) $specs[12] = '26 cm';
        if (empty($specs[13])) $specs[13] = '36 cm';
        if (empty($specs[10])) $specs[10] = '0.4';

        if ($idGrupo === self::GRUPO_CARGADORES_LAPTOP && empty($specs[42])) {
            $w = $this->extractWatts($textoAnalisis);
            if ($w) $specs[42] = $w;
        }

        return $specs;
    }
}

