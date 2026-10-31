<?php

namespace App\Services\Deltron\Classifiers;

use App\Models\Catalogo\GrupoProducto;
use App\Services\Deltron\Contracts\DeltronCategoryClassifierInterface;
use App\Services\Deltron\DeltronTextHelper;
use Illuminate\Support\Facades\Log;

/**
 * Clasificador y normalizador modular de Deltron para la Categoría 2: COMPUTADORAS.
 *
 * Cubre:
 * - PC Gamer (ID 7)
 * - PC Empresarial / Workstation (ID 8)
 * - PC Básica (ID 9)
 * - PC All in One (AIO) (ID 10)
 * - Mini PC / Barebones / NUC (Grupo resuelto dinámicamente o auto-creado en BD)
 */
class ComputadorasClassifier implements DeltronCategoryClassifierInterface
{
    public const CATEGORIA_ID = 2; // Computadoras

    public const GRUPO_PC_GAMER       = 7;
    public const GRUPO_PC_EMPRESARIAL = 8;
    public const GRUPO_PC_BASICA      = 9;
    public const GRUPO_PC_ALL_IN_ONE  = 10;

    /** @var int|null Cache en memoria del ID del grupo Mini PC */
    protected static ?int $miniPcGroupId = null;

    public function getCategoriaId(): int
    {
        return self::CATEGORIA_ID;
    }

    /**
     * Resuelve o auto-crea de forma segura el grupo "Mini PC" en la tabla GrupoProducto bajo idCategoria = 2.
     */
    public static function obtenerOCrearGrupoMiniPc(): int
    {
        if (self::$miniPcGroupId !== null) {
            return self::$miniPcGroupId;
        }

        // 1. Buscar grupo existente por nombre o slug
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

        // 2. Si no existe, crear de forma segura con el siguiente correlativo ID
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
            return self::GRUPO_PC_EMPRESARIAL; // Fallback seguro
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

    public function supportsGrupo(int $idGrupo): bool
    {
        return in_array($idGrupo, $this->getGruposIds(), true);
    }

    /**
     * Prioridad temprana:
     * 1. All-in-One (AIO) antes de PC estándar
     * 2. Mini PC / Barebones / NUC antes de componentes o periféricos
     */
    public function classifyEarly(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';
        $sku        = strtoupper($context['item']['sku'] ?? '');

        // 1. ALL IN ONE (AIO) -> Grupo 10
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

    /**
     * Clasificación principal para Computadoras Desktop / Workstation.
     */
    public function classify(array $context): ?int
    {
        $catDeltron = $context['catDeltron'] ?? '';
        $subDeltron = $context['subDeltron'] ?? '';
        $titulo     = $context['titulo'] ?? '';
        $name       = $context['name'] ?? '';
        $sku        = strtoupper($context['item']['sku'] ?? '');

        // Excluir Laptops/Portátiles para que sean procesadas por LaptopsClassifier
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

        // 1. PC Gamer (ID 7)
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

        // 2. PC Básica (ID 9)
        if (
            str_contains($fuente, 'BASICA') ||
            str_contains($fuente, 'BÁSICA') ||
            str_contains($fuente, 'CELERON') ||
            str_contains($fuente, 'PENTIUM') ||
            str_contains($fuente, 'ATHLON')
        ) {
            return self::GRUPO_PC_BASICA;
        }

        // 3. PC Empresarial / Workstation (ID 8) por defecto
        return self::GRUPO_PC_EMPRESARIAL;
    }

    /**
     * IDs de características ERP / Falabella para computadoras y Mini PCs.
     */
    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array
    {
        // 4 Procesador, 90 Núcleos, 5 RAM, 6 Almacenamiento, 7 Tarjeta de Video,
        // 15 SO, 14 Color, 91 Segmento, 10 Peso, 11 Alto, 12 Ancho, 13 Largo,
        // 17 Puertos, 26 Conexión, 50 WiFi, 53 Bluetooth, 55 Potencia/Fuente
        $base = [4, 90, 5, 6, 7, 15, 14, 91, 10, 11, 12, 13, 17, 26, 50, 53, 55];

        if ($idGrupo === self::GRUPO_PC_ALL_IN_ONE) {
            $base[] = 2; // Pulgadas de pantalla
            $base[] = 3; // Resolución de pantalla
            $base[] = 1; // Hz de pantalla
        }

        return array_values(array_unique($base));
    }

    /**
     * Enriquecimiento y normalización de especificaciones técnicas para computadoras y Mini PCs.
     */
    public function enrichAndNormalizeSpecs(
        int $idGrupo,
        array $specsMapeadas,
        array $rawSpecs,
        string $textoCompleto,
        array $sugerenciasPorChar = []
    ): array {
        $enriched = [];
        $allowed = array_flip($this->getAllowedCaracteristicasForGrupo($idGrupo));

        // Copiar especificaciones existentes válidas
        foreach ($specsMapeadas as $idCaract => $val) {
            $idInt = (int) $idCaract;
            if (isset($allowed[$idInt]) && $val !== null && $val !== '') {
                $enriched[$idInt] = is_string($val) ? trim($val) : $val;
            }
        }

        $specsTexto = strtoupper($textoCompleto . ' ' . implode(' ', $rawSpecs));

        // 1. PROCESADOR (ID 4)
        if (empty($enriched[4])) {
            $proc = $this->extraerProcesador($specsTexto);
            if ($proc) {
                $enriched[4] = $proc;
            }
        }

        // 2. NÚCLEOS DE PROCESADOR (ID 90)
        if (empty($enriched[90])) {
            if (preg_match('/(\d+)\s*(CORE|CORES|NUCLEOS|NÚCLEOS|C\/)/i', $specsTexto, $m)) {
                $enriched[90] = $m[1] . ' Núcleos';
            } elseif (preg_match('/\/(\d+)C\//i', $specsTexto, $m)) {
                $enriched[90] = $m[1] . ' Núcleos';
            }
        }

        // 3. MEMORIA RAM (ID 5)
        if (empty($enriched[5])) {
            $ram = $this->extraerMemoriaRam($specsTexto);
            if ($ram) {
                $enriched[5] = $ram;
            } elseif (str_contains($titulo, 'BAREBONE') || str_contains($titulo, 'KIT BAREBONE')) {
                $enriched[5] = 'Soporta hasta 64GB DDR5 (No incluida)';
            }
        }

        // 4. ALMACENAMIENTO (ID 6)
        if (empty($enriched[6])) {
            $alm = $this->extraerAlmacenamiento($specsTexto);
            if ($alm) {
                $enriched[6] = $alm;
            } elseif (str_contains($titulo, 'BAREBONE') || str_contains($titulo, 'KIT BAREBONE')) {
                $enriched[6] = 'Soporta SSD M.2 NVMe (No incluido)';
            }
        }

        // 5. TARJETA DE VIDEO / GRÁFICOS (ID 7)
        if (empty($enriched[7])) {
            $gpu = $this->extraerGraficos($specsTexto);
            if ($gpu) {
                $enriched[7] = $gpu;
            }
        }

        // 6. SISTEMA OPERATIVO (ID 15)
        if (empty($enriched[15])) {
            $so = $this->extraerSistemaOperativo($specsTexto);
            if ($so) {
                $enriched[15] = $so;
            } elseif (str_contains($titulo, 'BAREBONE')) {
                $enriched[15] = 'Compatible con Windows 11 / Linux';
            }
        }

        // 7. PUERTOS Y CONECTIVIDAD (ID 17)
        if (empty($enriched[17])) {
            $puertos = $this->extraerPuertos($specsTexto);
            if ($puertos) {
                $enriched[17] = $puertos;
            }
        }

        // 8. COLOR (ID 14)
        if (empty($enriched[14])) {
            if (str_contains($specsTexto, 'BLANCO') || str_contains($specsTexto, 'WHITE')) {
                $enriched[14] = 'Blanco';
            } elseif (str_contains($specsTexto, 'PLATA') || str_contains($specsTexto, 'SILVER') || str_contains($specsTexto, 'GRIS') || str_contains($specsTexto, 'GREY')) {
                $enriched[14] = 'Gris';
            } else {
                $enriched[14] = 'Negro';
            }
        }

        // 9. SEGMENTO (ID 91)
        if (empty($enriched[91])) {
            if ($idGrupo === self::GRUPO_PC_GAMER) {
                $enriched[91] = 'Gamer';
            } elseif ($idGrupo === self::GRUPO_PC_BASICA) {
                $enriched[91] = 'Hogar / Oficina';
            } else {
                $enriched[91] = 'Empresarial';
            }
        }

        // 10. DIMENSIONES Y PESO (11 Alto, 12 Ancho, 13 Largo, 10 Peso)
        $this->normalizarDimensionesYPeso($enriched, $specsTexto, $idGrupo);

        return $enriched;
    }

    protected function extraerProcesador(string $texto): ?string
    {
        if (preg_match('/(CORE\s+ULTRA\s+\d+\s+\w+|CORE\s+I[3579]-?\w+|RYZEN\s+[3579]\s+\w+|INTEL\s+PROCESSOR\s+N\d+|INTEL\s+N\d+|CELERON\s+\w+|PENTIUM\s+\w+)/i', $texto, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/(CORE\s+5\s+\w+|CORE\s+7\s+\w+|CORE\s+3\s+\w+)/i', $texto, $m)) {
            return 'Intel ' . trim($m[1]);
        }
        return null;
    }

    protected function extraerMemoriaRam(string $texto): ?string
    {
        if (preg_match('/(\d+GB)\s*(DDR[45]|LPDDR[45]|SO-DIMM)?/i', $texto, $m)) {
            $tipo = !empty($m[2]) ? ' ' . strtoupper($m[2]) : '';
            return strtoupper($m[1]) . $tipo;
        }
        return null;
    }

    protected function extraerAlmacenamiento(string $texto): ?string
    {
        if (preg_match('/(\d+\s*(?:TB|GB))\s*(SSD(?:\s*M\.2|\s*NVME)?|HDD)/i', $texto, $m)) {
            $tipo = !empty($m[2]) ? ' ' . strtoupper($m[2]) : ' SSD';
            return str_replace(' ', '', strtoupper($m[1])) . $tipo;
        }
        return null;
    }

    protected function extraerGraficos(string $texto): ?string
    {
        if (preg_match('/(RTX\s*\d{4}(?:\s*TI)?|GTX\s*\d{4}(?:\s*TI)?|RADEON\s*RX\s*\d{4}|ARC\s*GPU|INTEL\s*ARC|INTEL\s*UHD|RADEON\s*GRAPHICS)/i', $texto, $m)) {
            return strtoupper(trim($m[1]));
        }
        return 'Gráficos Integrados';
    }

    protected function extraerSistemaOperativo(string $texto): ?string
    {
        if (str_contains($texto, 'WINDOWS 11 PRO') || str_contains($texto, 'WIN 11 PRO')) {
            return 'Windows 11 Pro';
        }
        if (str_contains($texto, 'WINDOWS 11 HOME') || str_contains($texto, 'WIN 11 HOME') || str_contains($texto, 'WINDOWS 11')) {
            return 'Windows 11 Home';
        }
        if (str_contains($texto, 'WINDOWS 10 PRO') || str_contains($texto, 'WIN 10 PRO')) {
            return 'Windows 10 Pro';
        }
        if (str_contains($texto, 'FREEDOS') || str_contains($texto, 'FREE DOS') || str_contains($texto, 'NO INCLUYE SO')) {
            return 'FreeDOS / Sin Sistema';
        }
        if (str_contains($texto, 'LINUX') || str_contains($texto, 'UBUNTU')) {
            return 'Linux';
        }
        return null;
    }

    protected function extraerPuertos(string $texto): ?string
    {
        $puertos = [];
        if (str_contains($texto, 'HDMI')) $puertos[] = 'HDMI';
        if (str_contains($texto, 'DP') || str_contains($texto, 'DISPLAYPORT')) $puertos[] = 'DisplayPort';
        if (str_contains($texto, 'TB4') || str_contains($texto, 'THUNDERBOLT')) $puertos[] = 'Thunderbolt 4';
        if (str_contains($texto, 'TYPE-C') || str_contains($texto, 'USB-C')) $puertos[] = 'USB-C';
        if (str_contains($texto, 'USB 3')) $puertos[] = 'USB 3.2';
        if (str_contains($texto, 'RJ45') || str_contains($texto, 'ETHERNET') || str_contains($texto, 'LAN')) $puertos[] = 'RJ-45 Ethernet';
        if (str_contains($texto, 'WIFI') || str_contains($texto, 'WI-FI')) $puertos[] = 'WiFi';
        if (str_contains($texto, 'BT') || str_contains($texto, 'BLUETOOTH')) $puertos[] = 'Bluetooth';

        return !empty($puertos) ? implode(', ', $puertos) : 'HDMI, USB 3.0, RJ-45, Audio';
    }

    protected function normalizarDimensionesYPeso(array &$enriched, string $texto, int $idGrupo): void
    {
        $miniPcId = self::obtenerOCrearGrupoMiniPc();

        // Valores por defecto sensatos si no vienen en la ficha Deltron
        if ($idGrupo === $miniPcId) {
            $enriched[11] = $enriched[11] ?? '4.5';   // Alto cm
            $enriched[12] = $enriched[12] ?? '12.0';  // Ancho cm
            $enriched[13] = $enriched[13] ?? '11.5';  // Largo cm
            $enriched[10] = $enriched[10] ?? '0.85';  // Peso kg
        } elseif ($idGrupo === self::GRUPO_PC_ALL_IN_ONE) {
            $enriched[11] = $enriched[11] ?? '42.0';
            $enriched[12] = $enriched[12] ?? '54.0';
            $enriched[13] = $enriched[13] ?? '18.0';
            $enriched[10] = $enriched[10] ?? '5.5';
        } else {
            $enriched[11] = $enriched[11] ?? '35.0';
            $enriched[12] = $enriched[12] ?? '18.0';
            $enriched[13] = $enriched[13] ?? '40.0';
            $enriched[10] = $enriched[10] ?? '6.5';
        }
    }
}

