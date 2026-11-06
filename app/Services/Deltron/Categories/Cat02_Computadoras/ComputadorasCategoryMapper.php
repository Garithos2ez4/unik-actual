<?php

namespace App\Services\Deltron\Categories\Cat02_Computadoras;

use App\Models\Catalogo\GrupoProducto;
use App\Services\Deltron\Categories\AbstractCategorySpecMapper;
use Illuminate\Support\Facades\Log;

class ComputadorasCategoryMapper extends AbstractCategorySpecMapper
{
    public const CATEGORIA_ID = 2;
    public const CATEGORIA_NOMBRE = 'Computadoras';

    public const GRUPO_PC_GAMER       = 7;
    public const GRUPO_PC_EMPRESARIAL = 8;
    public const GRUPO_PC_BASICA      = 9;
    public const GRUPO_PC_ALL_IN_ONE  = 10;
    public const GRUPO_MINI_PC        = 215;

    protected static ?int $miniPcGroupId = null;

    public function getCategoriaId(): int
    {
        return self::CATEGORIA_ID;
    }

    public function getCategoriaNombre(): string
    {
        return self::CATEGORIA_NOMBRE;
    }

    public static function obtenerOCrearGrupoMiniPc(): int
    {
        if (self::$miniPcGroupId !== null) {
            return self::$miniPcGroupId;
        }

        $grupo = GrupoProducto::where(function ($q) {
            $q->where('nombreGrupo', 'LIKE', '%Mini PC%')
                ->orWhere('nombreGrupo', 'LIKE', '%MiniPC%')
                ->orWhere('slugGrupo', 'LIKE', '%mini-pc%')
                ->orWhere('nombreGrupo', 'LIKE', '%Barebone%');
        })->first();

        if ($grupo) {
            self::$miniPcGroupId = (int) $grupo->idGrupoProducto;
            return self::$miniPcGroupId;
        }

        try {
            $maxId = (int) (GrupoProducto::max('idGrupoProducto') ?? 0);
            $newId = $maxId + 1;
            $tipoRef = (int) (GrupoProducto::where('idCategoria', self::CATEGORIA_ID)->value('idTipoProducto') ?? 1);

            $nuevo = GrupoProducto::create([
                'idGrupoProducto' => $newId,
                'nombreGrupo'     => 'Mini PC',
                'idCategoria'     => self::CATEGORIA_ID,
                'idTipoProducto'  => $tipoRef,
                'imagenGrupo'     => 'grupos/IMGPROmini-pc.webp',
                'slugGrupo'       => 'mini-pc',
            ]);

            self::$miniPcGroupId = (int) $nuevo->idGrupoProducto;
            return self::$miniPcGroupId;
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear automáticamente el grupo Mini PC: ' . $e->getMessage());
            return self::GRUPO_MINI_PC;
        }
    }

    public function getGruposIds(): array
    {
        $miniPcId = self::obtenerOCrearGrupoMiniPc();
        return array_values(array_unique([
            self::GRUPO_PC_GAMER,
            self::GRUPO_PC_EMPRESARIAL,
            self::GRUPO_PC_BASICA,
            self::GRUPO_PC_ALL_IN_ONE,
            $miniPcId,
        ]));
    }

    public function classifyEarly(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';
        $sku        = strtoupper($context['item']['sku'] ?? '');

        // 1. ALL IN ONE (AIO) -> Grupo 10
        // Candado estricto: Las impresoras multifuncionales de tinta/láser ("Impresora All-in-one", "Multifuncional") NO son computadoras
        $esImpresora = (
            str_contains($catDeltron, 'IMPRESORA') ||
            str_contains($subDeltron, 'IMPRESORA') ||
            str_contains($subDeltron, 'TINTA') ||
            str_contains($subDeltron, 'MULTIFUNCIONAL') ||
            str_contains($titulo, 'IMPRESORA') ||
            str_contains($titulo, 'MULTIFUNCIONAL') ||
            str_contains($titulo, 'SMART TANK') ||
            str_contains($titulo, 'INK TANK') ||
            str_contains($titulo, 'ECOTANK')
        );

        if (!$esImpresora) {
            if (
                str_contains($catDeltron, 'ALL IN ONE') ||
                str_contains($catDeltron, 'ALL-IN-ONE') ||
                str_contains($subDeltron, 'ALL IN ONE') ||
                str_contains($subDeltron, 'ALL-IN-ONE') ||
                str_contains($subDeltron, 'AIO') ||
                str_contains($titulo, 'ALL IN ONE') ||
                str_contains($titulo, 'ALL-IN-ONE') ||
                str_contains($titulo, 'AIO ') ||
                str_starts_with($name, 'AIO ')
            ) {
                return self::GRUPO_PC_ALL_IN_ONE;
            }
        }

        // 2. BAREBONES / MINI PC / NUC
        $esMiniPc = (
            str_contains($catDeltron, 'BAREBONE') ||
            str_contains($catDeltron, 'MINI PC') ||
            str_contains($catDeltron, 'MINIPC') ||
            str_contains($subDeltron, 'BAREBONE') ||
            str_contains($subDeltron, 'MINI PC') ||
            str_contains($subDeltron, 'MINIPC') ||
            str_contains($subDeltron, 'NUC') ||
            str_contains($titulo, 'BAREBONE') ||
            str_contains($titulo, 'MINI PC') ||
            str_contains($titulo, 'MINIPC') ||
            str_contains($titulo, 'NUC ') ||
            str_contains($titulo, ' NUC') ||
            str_starts_with($titulo, 'NUC') ||
            str_starts_with($name, 'BB ') ||
            str_starts_with($name, 'BBAS') ||
            str_starts_with($name, 'BBMS') ||
            str_starts_with($name, 'BBADV') ||
            str_starts_with($sku, 'BBAS') ||
            str_starts_with($sku, 'BBMS') ||
            str_starts_with($sku, 'BBADV') ||
            str_starts_with($sku, 'BBLN') ||
            str_starts_with($sku, 'NUC')
        );

        if ($esMiniPc) {
            return self::obtenerOCrearGrupoMiniPc();
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

        if (
            str_contains($catDeltron, 'PORTATIL') ||
            str_contains($catDeltron, 'NOTEBOOK') ||
            str_contains($subDeltron, 'NOTEBOOK') ||
            str_starts_with($name, 'NB ') ||
            str_starts_with($titulo, 'NB ') ||
            str_starts_with($sku, 'NB')
        ) {
            return null;
        }

        $esComputadora = (
            str_contains($catDeltron, 'COMPUTADORAS DESKTOP') ||
            str_contains($catDeltron, 'COMPUTADORAS') ||
            str_contains($catDeltron, 'SISTEMAS') ||
            str_contains($subDeltron, 'DESKTOP') ||
            str_contains($subDeltron, 'WORKSTATION') ||
            str_contains($titulo, 'DESKTOP') ||
            str_contains($titulo, 'WORKSTATION') ||
            str_contains($titulo, 'COMPUTADORA') ||
            str_starts_with($name, 'WS ') ||
            str_starts_with($name, 'WKS ') ||
            str_starts_with($name, 'PC ') ||
            str_starts_with($sku, 'PC') ||
            str_starts_with($sku, 'WS')
        );

        if (!$esComputadora) {
            return null;
        }

        $fuente = $titulo . ' ' . $subDeltron . ' ' . $name;

        if (
            str_contains($fuente, 'GAMER') ||
            str_contains($fuente, 'GAMING') ||
            str_contains($fuente, 'RTX') ||
            str_contains($fuente, 'LEGION') ||
            str_contains($fuente, 'OMEN') ||
            str_contains($fuente, 'PREDATOR')
        ) {
            return self::GRUPO_PC_GAMER;
        }

        if (
            str_contains($fuente, 'BASICA') ||
            str_contains($fuente, 'BÁSICA') ||
            str_contains($fuente, 'CELERON') ||
            str_contains($fuente, 'PENTIUM') ||
            str_contains($fuente, 'ATHLON')
        ) {
            return self::GRUPO_PC_BASICA;
        }

        return self::GRUPO_PC_EMPRESARIAL;
    }

    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        $base = [4, 90, 5, 6, 7, 15, 14, 91, 10, 11, 12, 13, 17, 26, 50, 51, 52, 53, 55];
        if ($idGrupo === self::GRUPO_PC_ALL_IN_ONE) {
            $base[] = 1;  // Hz
            $base[] = 2;  // Pulgadas
            $base[] = 3;  // Resolucion
            $base[] = 21; // Panel
            $base[] = 67; // Micrófono
        }
        return array_values(array_unique($base));
    }

    public function enrichAndNormalizeSpecs(
        int $idGrupo,
        array $specsMapeadas,
        array $rawSpecs,
        string $textoCompleto,
        array $sugerenciasPorChar = []
    ): array {
        $allowed = array_flip($this->getAllowedCaracteristicasForGrupo($idGrupo));
        $enriched = [];

        foreach ($specsMapeadas as $idCaract => $val) {
            $idInt = (int) $idCaract;
            if (isset($allowed[$idInt]) && $val !== null && $val !== '') {
                $enriched[$idInt] = is_string($val) ? trim($val) : $val;
            }
        }

        $specsTexto = strtoupper($textoCompleto . ' ' . implode(' ', $rawSpecs));
        $esBarebone = str_contains($specsTexto, 'BAREBONE') || str_contains($specsTexto, 'KIT BAREBONE');

        // 1. Procesador (4)
        if (empty($enriched[4])) {
            $proc = $this->extraerProcesador($specsTexto);
            if ($proc) $enriched[4] = $proc;
        }

        // 2. Núcleos (90)
        if (empty($enriched[90])) {
            if (preg_match('/(\d{1,2})\s*(CORE|CORES|NUCLEOS|NÚCLEOS|C\/)/i', $specsTexto, $m)) {
                $enriched[90] = $m[1];
            } else {
                $enriched[90] = $this->inferirNucleosPorProcesador($enriched[4] ?? $specsTexto);
            }
        }

        // 3. RAM (5)
        if (empty($enriched[5])) {
            $ram = $this->extractMemoriaRam($specsTexto);
            if ($ram) {
                $enriched[5] = $ram;
            } elseif ($esBarebone) {
                $enriched[5] = 'Soporta hasta 64GB DDR5 (No incluida)';
            }
        }

        // 4. Almacenamiento (6)
        if (empty($enriched[6])) {
            $alm = $this->extractAlmacenamiento($specsTexto);
            if ($alm) {
                $enriched[6] = $alm;
            } elseif ($esBarebone) {
                $enriched[6] = 'Soporta SSD M.2 NVMe (No incluido)';
            }
        }

        // 5. Gráficos (7)
        if (empty($enriched[7])) {
            if (preg_match('/(RTX\s*\d{4}(?:\s*TI)?|GTX\s*\d{4}(?:\s*TI)?|RADEON\s*RX\s*\d{4}|ARC\s*GPU|INTEL\s*ARC|INTEL\s*UHD|RADEON\s*GRAPHICS)/i', $specsTexto, $m)) {
                $enriched[7] = strtoupper(trim($m[1]));
            } else {
                $enriched[7] = 'Gráficos Integrados';
            }
        }

        // 6. SO (15)
        if (empty($enriched[15])) {
            if (str_contains($specsTexto, 'WINDOWS 11 PRO') || str_contains($specsTexto, 'WIN 11 PRO')) {
                $enriched[15] = 'Windows 11 Pro';
            } elseif (str_contains($specsTexto, 'WINDOWS 11 HOME') || str_contains($specsTexto, 'WIN 11 HOME') || str_contains($specsTexto, 'WINDOWS 11')) {
                $enriched[15] = 'Windows 11 Home';
            } elseif ($esBarebone) {
                $enriched[15] = 'Compatible con Windows 11 / Linux';
            } else {
                $enriched[15] = 'FreeDOS';
            }
        }

        // 7. AIO Pantalla
        if ($idGrupo === self::GRUPO_PC_ALL_IN_ONE) {
            if (empty($enriched[2])) $enriched[2] = $this->extractPulgadas($specsTexto) ?? '23.8"';
            if (empty($enriched[3])) $enriched[3] = $this->extractResolucion($specsTexto) ?? '1920 x 1080';
            if (empty($enriched[1])) $enriched[1] = $this->extractHz($specsTexto) ?? '60 Hz';
            if (empty($enriched[21])) {
                if (preg_match('/\bOLED\b/i', $specsTexto)) $enriched[21] = 'OLED';
                elseif (preg_match('/\b(VA|WVA)\b/i', $specsTexto)) $enriched[21] = 'VA';
                elseif (preg_match('/\bTN\b/i', $specsTexto)) $enriched[21] = 'TN';
                else $enriched[21] = 'IPS';
            }
        }

        // 8. Puertos (17)
        if (empty($enriched[17])) {
            $puertos = [];
            if (str_contains($specsTexto, 'HDMI')) $puertos[] = 'HDMI';
            if (str_contains($specsTexto, 'DP') || str_contains($specsTexto, 'DISPLAYPORT')) $puertos[] = 'DisplayPort';
            if (str_contains($specsTexto, 'TYPE-C') || str_contains($specsTexto, 'USB-C')) $puertos[] = 'USB-C';
            if (str_contains($specsTexto, 'USB 3')) $puertos[] = 'USB 3.2';
            if (str_contains($specsTexto, 'RJ45') || str_contains($specsTexto, 'ETHERNET')) $puertos[] = 'RJ-45 Ethernet';
            $enriched[17] = !empty($puertos) ? implode(', ', $puertos) : 'HDMI, USB 3.0, RJ-45, Audio';
        }

        // 9. Color (14)
        if (empty($enriched[14])) {
            if (str_contains($specsTexto, 'BLANCO') || str_contains($specsTexto, 'WHITE')) $enriched[14] = 'Blanco';
            elseif (str_contains($specsTexto, 'PLATEADO') || str_contains($specsTexto, 'SILVER')) $enriched[14] = 'Plateado';
            elseif (str_contains($specsTexto, 'GRIS') || str_contains($specsTexto, 'GREY')) $enriched[14] = 'Gris';
            else $enriched[14] = 'Negro';
        }

        // 10. Segmento (91)
        if (empty($enriched[91])) {
            if ($idGrupo === self::GRUPO_PC_GAMER) $enriched[91] = 'Gamer';
            elseif ($idGrupo === self::GRUPO_PC_BASICA) $enriched[91] = 'Estudiante';
            else $enriched[91] = 'Productividad';
        }

        // 11. Dimensiones y peso
        $miniPcId = self::obtenerOCrearGrupoMiniPc();
        if ($idGrupo === $miniPcId) {
            $enriched[11] = $enriched[11] ?? '4.5 cm';
            $enriched[12] = $enriched[12] ?? '12 cm';
            $enriched[13] = $enriched[13] ?? '11.5 cm';
            $enriched[10] = $enriched[10] ?? '0.85';
        } elseif ($idGrupo === self::GRUPO_PC_ALL_IN_ONE) {
            $enriched[11] = $enriched[11] ?? '42 cm';
            $enriched[12] = $enriched[12] ?? '54 cm';
            $enriched[13] = $enriched[13] ?? '18 cm';
            $enriched[10] = $enriched[10] ?? '5.5';
        } else {
            $enriched[11] = $enriched[11] ?? '35 cm';
            $enriched[12] = $enriched[12] ?? '18 cm';
            $enriched[13] = $enriched[13] ?? '40 cm';
            $enriched[10] = $enriched[10] ?? '6.5';
        }

        return $enriched;
    }

    protected function inferirNucleosPorProcesador(string $procTexto): string
    {
        $u = strtoupper($procTexto);
        if (str_contains($u, '14900') || str_contains($u, '13900')) return '24';
        if (str_contains($u, '14700')) return '20';
        if (str_contains($u, '13700') || str_contains($u, '12700') || str_contains($u, 'ULTRA 7')) return '16';
        if (str_contains($u, '13600') || str_contains($u, 'ULTRA 5') || str_contains($u, '13500')) return '14';
        if (str_contains($u, '12500') || str_contains($u, '1340P') || str_contains($u, '1240P')) return '12';
        if (str_contains($u, '13400') || str_contains($u, '1335U') || str_contains($u, '1235U')) return '10';
        if (str_contains($u, '13420') || str_contains($u, '12450') || str_contains($u, 'RYZEN 7') || str_contains($u, 'I7')) return '8';
        if (str_contains($u, '12400') || str_contains($u, 'RYZEN 5') || str_contains($u, '1315U')) return '6';
        if (str_contains($u, 'RYZEN 3') || str_contains($u, 'I3') || str_contains($u, 'N100')) return '4';
        return '8';
    }

    protected function extraerProcesador(string $texto): ?string
    {
        if (preg_match('/(CORE\s+ULTRA\s+\d+\s+[A-Z0-9]+|CORE\s+I[3579]-?[A-Z0-9]+|RYZEN\s+[3579]\s+[A-Z0-9]+|INTEL\s+PROCESSOR\s+N\d+|INTEL\s+N\d+|CELERON\s+[A-Z0-9]+|PENTIUM\s+[A-Z0-9]+)/i', $texto, $m)) {
            $raw = trim(preg_replace('/\s+/', ' ', $m[1]));
            $raw = preg_replace_callback('/\b(CORE|ULTRA|RYZEN|INTEL|PROCESSOR|CELERON|PENTIUM)\b/i', fn ($w) => ucfirst(strtolower($w[1])), $raw);
            $raw = preg_replace_callback('/\bI([3579])\b/i', fn ($w) => 'i' . $w[1], $raw);
            if (stripos($raw, 'Core ') === 0) return 'Intel ' . $raw;
            if (stripos($raw, 'Ryzen ') === 0) return 'AMD ' . $raw;
            return $raw;
        }
        return null;
    }
}

