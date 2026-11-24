<?php

namespace App\Services\Deltron;

use App\Models\Catalogo\Producto;
use App\Services\Deltron\Categories\Cat01_Laptops\LaptopsCategoryMapper;
use App\Services\Deltron\Categories\Cat02_Computadoras\ComputadorasCategoryMapper;
use App\Services\Deltron\Categories\Cat03_Monitores\MonitoresCategoryMapper;
use App\Services\Deltron\Categories\Cat04_Perifericos\PerifericosCategoryMapper;
use App\Services\Deltron\Categories\Cat05_Belleza\BellezaCategoryMapper;
use App\Services\Deltron\Categories\Cat06_Impresoras\ImpresorasCategoryMapper;
use App\Services\Deltron\Categories\Cat07_Redes\RedesCategoryMapper;
use App\Services\Deltron\Categories\Cat08_ComponentesPc\ComponentesPcCategoryMapper;
use App\Services\Deltron\Categories\Cat09_Smartphones\SmartphonesCategoryMapper;
use App\Services\Deltron\Categories\Cat10_Cables\CablesCategoryMapper;
use App\Services\Deltron\Categories\Cat11_RepuestosLaptop\RepuestosLaptopCategoryMapper;
use App\Services\Deltron\Categories\Cat12_RepuestosImpresoraTinta\RepuestosImpresoraTintaCategoryMapper;
use App\Services\Deltron\Categories\Cat13_RepuestosImpresoraDeLaser\RepuestosImpresoraDeLaserCategoryMapper;
use App\Services\Deltron\Categories\Cat14_SuministrosConsumibles\SuministrosConsumiblesCategoryMapper;
use App\Services\Deltron\Categories\Cat15_RepuestosScanner\RepuestosScannerCategoryMapper;
use App\Services\Deltron\Categories\Cat16_InsumosHerramientasImpresoras\InsumosHerramientasImpresorasCategoryMapper;
use App\Services\Deltron\Categories\Cat17_RepuestosImpresoraLaser\RepuestosImpresoraLaserCategoryMapper;
use App\Services\Deltron\Contracts\DeltronCategoryClassifierInterface;

class DeltronClassifierService
{
    /** @var DeltronCategoryClassifierInterface[] */
    protected array $classifiers = [];

    public function __construct()
    {
        // Registro de los 17 clasificadores/mappers modulares por categoría
        // Ordenados estratégicamente para evitar colisiones entre categorías específicas y generales
        $this->classifiers = [
            new LaptopsCategoryMapper(),                       // Cat 1
            new ComputadorasCategoryMapper(),                  // Cat 2
            new ComponentesPcCategoryMapper(),                 // Cat 8
            new MonitoresCategoryMapper(),                     // Cat 3
            new ImpresorasCategoryMapper(),                    // Cat 6
            new RedesCategoryMapper(),                         // Cat 7
            new SmartphonesCategoryMapper(),                   // Cat 9
            new PerifericosCategoryMapper(),                   // Cat 4
            new CablesCategoryMapper(),                        // Cat 10
            new BellezaCategoryMapper(),                       // Cat 5
            new RepuestosLaptopCategoryMapper(),               // Cat 11
            new RepuestosImpresoraTintaCategoryMapper(),       // Cat 12
            new RepuestosImpresoraDeLaserCategoryMapper(),     // Cat 13
            new SuministrosConsumiblesCategoryMapper(),        // Cat 14
            new RepuestosScannerCategoryMapper(),              // Cat 15
            new InsumosHerramientasImpresorasCategoryMapper(), // Cat 16
            new RepuestosImpresoraLaserCategoryMapper(),       // Cat 17
        ];
    }

    /**
     * Retorna todos los clasificadores registrados.
     *
     * @return DeltronCategoryClassifierInterface[]
     */
    public function getClassifiers(): array
    {
        return $this->classifiers;
    }

    /**
     * Resuelve el clasificador/normalizador modular para un idGrupo dado.
     */
    public function resolveClassifierForGrupo(?int $idGrupo): ?DeltronCategoryClassifierInterface
    {
        if (!$idGrupo) {
            return null;
        }

        foreach ($this->classifiers as $classifier) {
            if ($classifier->supportsGrupo($idGrupo)) {
                return $classifier;
            }
        }

        return null;
    }

    /**
     * Resuelve el clasificador para un idCategoria dado.
     */
    public function resolveClassifierForCategoria(?int $idCategoria): ?DeltronCategoryClassifierInterface
    {
        if (!$idCategoria) {
            return null;
        }

        foreach ($this->classifiers as $classifier) {
            if ($classifier->getCategoriaId() === $idCategoria) {
                return $classifier;
            }
        }

        return null;
    }

    /**
     * Clasifica un item de Deltron en el grupo/categoria correspondiente de Unike Store
     * cruzando category_name, subcategory_name y titulo comercial a través de los 17 módulos hijos.
     */
    public function clasificarGrupoSugerido(array $item, $grupos): array
    {
        $catDeltron = DeltronTextHelper::normalizarParaBusqueda($item['category_name'] ?? '');
        $subDeltron = DeltronTextHelper::normalizarParaBusqueda($item['subcategory_name'] ?? '');
        $titulo = DeltronTextHelper::normalizarParaBusqueda(DeltronTextHelper::obtenerTituloComercialDeltron($item));
        $name = DeltronTextHelper::normalizarParaBusqueda($item['name'] ?? '');

        $context = [
            'catDeltron' => $catDeltron,
            'subDeltron' => $subDeltron,
            'titulo'     => $titulo,
            'name'       => $name,
            'item'       => $item,
        ];

        $idGrupoSugerido = null;

        // 1. Reglas de prioridad temprana de los 17 módulos hijos
        foreach ($this->classifiers as $classifier) {
            $earlyMatch = $classifier->classifyEarly($context);
            if ($earlyMatch !== null) {
                $idGrupoSugerido = $earlyMatch;
                break;
            }
        }

        // 2. Clasificación principal de las 17 categorías
        if ($idGrupoSugerido === null) {
            foreach ($this->classifiers as $classifier) {
                $matchedGrupo = $classifier->classify($context);
                if ($matchedGrupo !== null) {
                    $idGrupoSugerido = $matchedGrupo;
                    break;
                }
            }
        }

        // Si no coincidió con ninguna categoría, no asignar categoría por defecto (dejar en blanco para asignación manual)
        if ($idGrupoSugerido === null) {
            return [
                'idGrupo' => null,
                'nombreGrupo' => null,
                'idCategoria' => null,
                'nombreCategoria' => null
            ];
        }

        $grupoObj = $grupos->get($idGrupoSugerido);
        if (!$grupoObj) {
            $grupoObj = \App\Models\Catalogo\GrupoProducto::with('CategoriaProducto')->find($idGrupoSugerido);
            if ($grupoObj && method_exists($grupos, 'put')) {
                $grupos->put($idGrupoSugerido, $grupoObj);
            }
        }
        $nombreGrupo = $grupoObj ? $grupoObj->nombreGrupo : 'Perifericos';
        $idCategoria = $grupoObj ? $grupoObj->idCategoria : 4;
        $nombreCat = ($grupoObj && $grupoObj->CategoriaProducto) ? $grupoObj->CategoriaProducto->nombreCategoria : 'Perifericos';

        return [
            'idGrupo' => $idGrupoSugerido,
            'nombreGrupo' => $nombreGrupo,
            'idCategoria' => $idCategoria,
            'nombreCategoria' => $nombreCat
        ];
    }

    /**
     * Empareja la marca de Deltron con las marcas de Unike Store.
     */
    public function emparejarMarca(?string $deltronBrand, $marcas): int
    {
        if (empty($deltronBrand)) return 1;
        $brandUpper = strtoupper(trim($deltronBrand));

        foreach ($marcas as $m) {
            if (strtoupper(trim($m->nombreMarca)) === $brandUpper) {
                return (int) $m->idMarca;
            }
        }

        $palabras = preg_split('/[\s\-\/]+/', $brandUpper);
        foreach ($palabras as $p) {
            if (strlen($p) < 2) continue;
            foreach ($marcas as $m) {
                $mUpper = strtoupper(trim($m->nombreMarca));
                if ($mUpper === $p || str_contains($brandUpper, $mUpper)) {
                    return (int) $m->idMarca;
                }
            }
        }

        return 1;
    }

    protected static array $codigosReservadosSesion = [];

    public static function limpiarCodigosReservados(): void
    {
        self::$codigosReservadosSesion = [];
    }

    /**
     * Genera el siguiente codigo de producto correlativo de 10 caracteres para un grupo,
     * reutilizando huecos/codigos huerfanos si existen o avanzando secuencialmente.
     * Regla: 3 caracteres del Grupo + 3 caracteres de la Categoría (o el prefijo existente del grupo) + 4 dígitos correlativos.
     */
    public function generarSiguienteCodigoProducto(int $idGrupo, ?string $nombreGrupo = null, ?string $nombreCategoria = null): string
    {
        if (empty($nombreGrupo) || empty($nombreCategoria)) {
            $grupoObj = \App\Models\Catalogo\GrupoProducto::with('CategoriaProducto')->find($idGrupo);
            if ($grupoObj) {
                $nombreGrupo = $nombreGrupo ?: $grupoObj->nombreGrupo;
                $nombreCategoria = $nombreCategoria ?: ($grupoObj->CategoriaProducto ? $grupoObj->CategoriaProducto->nombreCategoria : '');
            }
        }

        // Limpiar caracteres alfanuméricos manteniendo mayúsculas
        $cleanGrupo = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', (string) $nombreGrupo)));
        $cleanCat   = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', (string) $nombreCategoria)));

        $prefGrupo = substr($cleanGrupo, 0, 3);
        $prefCat   = substr($cleanCat, 0, 3);

        if (strlen($prefGrupo) < 3) {
            $prefGrupo = str_pad($prefGrupo, 3, 'X');
        }
        if (strlen($prefCat) < 3) {
            $extraGrupo = substr($cleanGrupo, 3, 3 - strlen($prefCat));
            $prefCat = str_pad($prefCat . $extraGrupo, 3, 'X');
        }

        $defaultPrefix = substr($prefGrupo . $prefCat, 0, 6);

        // Obtener los códigos existentes en el grupo para determinar el prefijo en uso
        $codigosGrupo = Producto::where('idGrupo', $idGrupo)
            ->whereNotNull('codigoProducto')
            ->where('codigoProducto', '!=', '')
            ->pluck('codigoProducto');

        // Descartar prefijos corruptos conocidos (ej. M00000 o letra única seguida de ceros)
        $codigosValidos = $codigosGrupo->filter(function ($code) {
            return strlen($code) >= 10 && !preg_match('/^[A-Z]0{4,5}/', $code);
        });

        $prefix = $codigosValidos->isNotEmpty()
            ? substr($codigosValidos->last(), 0, 6)
            : $defaultPrefix;

        // Buscar todos los números usados con este prefijo a nivel global en Producto
        $numerosUsados = Producto::where('codigoProducto', 'like', $prefix . '%')
            ->pluck('codigoProducto')
            ->map(function ($code) use ($prefix) {
                $sufijo = substr($code, strlen($prefix));
                return is_numeric($sufijo) ? (int) $sufijo : null;
            })
            ->filter(fn ($n) => $n !== null && $n > 0)
            ->all();

        $usadosMap = array_flip($numerosUsados);

        // Encontrar el primer número correlativo disponible (rellenando huecos huérfanos)
        $siguienteNum = 1;
        while (true) {
            $candidatoCodigo = $prefix . str_pad($siguienteNum, 4, '0', STR_PAD_LEFT);
            if (!isset($usadosMap[$siguienteNum]) && !isset(self::$codigosReservadosSesion[$candidatoCodigo])) {
                self::$codigosReservadosSesion[$candidatoCodigo] = true;
                return $candidatoCodigo;
            }
            $siguienteNum++;
        }
    }

    /**
     * Detecta si un producto de Deltron corresponde a una variante.
     */
    public static function detectarVarianteDeltron(array $item): ?array
    {
        $sku = strtoupper(trim($item['sku'] ?? ''));
        $name = strtoupper(trim($item['name'] ?? ''));
        $pn = strtoupper(trim($item['part_number'] ?? ''));

        $colorMap = [
            ['ROSE GOLD', '#b76e79', 'Rose Gold'],
            ['ROSEGOLD', '#b76e79', 'Rose Gold'],
            ['RSG', '#b76e79', 'Rose Gold'],
            ['PINK', '#e83e8c', 'Rosa'],
            ['ROSA', '#e83e8c', 'Rosa'],
            ['PNK', '#e83e8c', 'Rosa'],
            ['ROS', '#e83e8c', 'Rosa'],
            ['WHITE', '#ffffff', 'Blanco'],
            ['BLANCO', '#ffffff', 'Blanco'],
            ['WHT', '#ffffff', 'Blanco'],
            ['BLA', '#ffffff', 'Blanco'],
            ['BLACK', '#212529', 'Negro'],
            ['NEGRO', '#212529', 'Negro'],
            ['BLK', '#212529', 'Negro'],
            ['BLCK', '#212529', 'Negro'],
            ['GREY', '#6c757d', 'Gris'],
            ['GRAY', '#6c757d', 'Gris'],
            ['GRIS', '#6c757d', 'Gris'],
            ['GRY', '#6c757d', 'Gris'],
            ['BLUE', '#0d6efd', 'Azul'],
            ['AZUL', '#0d6efd', 'Azul'],
            ['BLU', '#0d6efd', 'Azul'],
            ['RED', '#dc3545', 'Rojo'],
            ['ROJO', '#dc3545', 'Rojo'],
            ['GREEN', '#198754', 'Verde'],
            ['VERDE', '#198754', 'Verde'],
            ['GRN', '#198754', 'Verde'],
            ['ORANGE', '#fd7e14', 'Naranja'],
            ['NARANJA', '#fd7e14', 'Naranja'],
            ['ORG', '#fd7e14', 'Naranja'],
            ['PURPLE', '#6f42c1', 'Morado'],
            ['MORADO', '#6f42c1', 'Morado'],
            ['LILAC', '#c8a2c8', 'Lila'],
            ['LILA', '#c8a2c8', 'Lila'],
            ['YELLOW', '#ffc107', 'Amarillo'],
            ['AMARILLO', '#ffc107', 'Amarillo'],
            ['YEL', '#ffc107', 'Amarillo'],
            ['SILVER', '#adb5bd', 'Plata'],
            ['PLATA', '#adb5bd', 'Plata'],
            ['SLV', '#adb5bd', 'Plata'],
            ['GOLD', '#d4af37', 'Dorado'],
            ['DORADO', '#d4af37', 'Dorado'],
            ['BEIGE', '#f5f5dc', 'Beige'],
        ];

        $shortSkuSuffixes = [
            'RSG' => ['#b76e79', 'Rose Gold'],
            'WHT' => ['#ffffff', 'Blanco'],
            'BLK' => ['#212529', 'Negro'],
            'GRY' => ['#6c757d', 'Gris'],
            'BLU' => ['#0d6efd', 'Azul'],
            'PNK' => ['#e83e8c', 'Rosa'],
            'RED' => ['#dc3545', 'Rojo'],
            'ORG' => ['#fd7e14', 'Naranja'],
            'NE'  => ['#212529', 'Negro'],
            'NG'  => ['#212529', 'Negro'],
            'RS'  => ['#e83e8c', 'Rosa'],
            'WH'  => ['#ffffff', 'Blanco'],
            'BL'  => ['#0d6efd', 'Azul'],
            'AZ'  => ['#0d6efd', 'Azul'],
            'RD'  => ['#dc3545', 'Rojo'],
            'GR'  => ['#198754', 'Verde'],
            'W'   => ['#ffffff', 'Blanco'],
            'N'   => ['#212529', 'Negro'],
        ];

        $tituloComercial = strtoupper(trim(DeltronTextHelper::obtenerTituloComercialDeltron($item)));
        $words = preg_split('/[\s,\-\/]+/', $name);
        $lastWord = end($words);
        foreach ($colorMap as [$token, $hex, $label]) {
            if ($lastWord === $token || preg_match('/\bCOLOR\s+' . preg_quote($token, '/') . '\b/i', $tituloComercial)) {
                return ['tipo' => 'COLOR', 'valor' => $label, 'hex' => $hex, 'token' => $token];
            }
        }

        if (!empty($pn)) {
            $cleanPn = preg_replace('/[^A-Z0-9]/', '', $pn);
            $pos = strpos($sku, $cleanPn);
            if ($pos !== false) {
                $suffix = substr($sku, $pos + strlen($cleanPn));
                if (isset($shortSkuSuffixes[$suffix])) {
                    [$hex, $label] = $shortSkuSuffixes[$suffix];
                    return ['tipo' => 'COLOR', 'valor' => $label, 'hex' => $hex, 'token' => $suffix];
                }
            }
        }

        // En el fallback ciego por sufijo de SKU, no usar sufijos de 1 sola letra (ej. 'W' o 'N')
        // porque forman parte de códigos de modelo/región (ej. Samsung ...GW / ...WW o TE-4064N)
        foreach ($shortSkuSuffixes as $suf => [$hex, $label]) {
            if (strlen($suf) >= 2 && str_ends_with($sku, $suf)) {
                return ['tipo' => 'COLOR', 'valor' => $label, 'hex' => $hex, 'token' => $suf];
            }
        }

        return null;
    }

    /**
     * Extrae el modelo base limpio, part number base y el titulo comercial del contenedor padre.
     */
    public static function extraerModeloBaseYNombrePadre(array $item, ?array $variante): array
    {
        $sku = strtoupper(trim($item['sku'] ?? ''));
        $pn = strtoupper(trim($item['part_number'] ?? $sku));
        $titulo = DeltronTextHelper::obtenerTituloComercialDeltron($item);

        $basePn = $pn;
        $token = $variante['token'] ?? null;

        if ($token) {
            if (str_ends_with($basePn, $token)) {
                $basePn = substr($basePn, 0, -strlen($token));
            }
        }
        $basePn = trim($basePn, " -_");
        if (empty($basePn)) {
            $basePn = $pn;
        }

        $nombrePadre = $titulo;
        if ($token) {
            $nombrePadre = preg_replace('/\bCOLOR\s+' . preg_quote($token, '/') . '\b/i', '', $nombrePadre);
            $nombrePadre = preg_replace('/\b' . preg_quote($token, '/') . '\b/i', '', $nombrePadre);
            $nombrePadre = preg_replace('/(\d+)' . preg_quote($token, '/') . '(?=[\s,]|$)/i', '$1', $nombrePadre);
            if (!empty($variante['valor'])) {
                $nombrePadre = preg_replace('/\bCOLOR\s+' . preg_quote($variante['valor'], '/') . '\b/i', '', $nombrePadre);
                $nombrePadre = preg_replace('/\b' . preg_quote($variante['valor'], '/') . '\b/i', '', $nombrePadre);
            }
        }

        $nombrePadre = preg_replace('/\s*,\s*,\s*/', ', ', $nombrePadre);
        $nombrePadre = preg_replace('/\s+/', ' ', $nombrePadre);
        $nombrePadre = trim($nombrePadre, " ,-");

        return [
            'part_number_base' => $basePn,
            'nombre_padre' => $nombrePadre,
            'modelo_base' => $basePn
        ];
    }
}
