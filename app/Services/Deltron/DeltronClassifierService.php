<?php

namespace App\Services\Deltron;

use App\Models\Catalogo\Producto;
use App\Services\Deltron\Classifiers\ComputadorasClassifier;
use App\Services\Deltron\Classifiers\LaptopsClassifier;
use App\Services\Deltron\Classifiers\PerifericosClassifier;
use App\Services\Deltron\Contracts\DeltronCategoryClassifierInterface;

class DeltronClassifierService
{
    /** @var DeltronCategoryClassifierInterface[] */
    protected array $classifiers = [];

    protected ComputadorasClassifier $computadorasClassifier;
    protected LaptopsClassifier $laptopsClassifier;
    protected PerifericosClassifier $perifericosClassifier;

    public function __construct()
    {
        $this->computadorasClassifier = new ComputadorasClassifier();
        $this->laptopsClassifier     = new LaptopsClassifier();
        $this->perifericosClassifier = new PerifericosClassifier();

        // Registro modular de clasificadores por categoría (estilo Falabella Mappers)
        // Computadoras y Laptops se ejecutan antes que Periféricos para evitar colisiones
        $this->classifiers = [
            $this->computadorasClassifier,
            $this->laptopsClassifier,
            $this->perifericosClassifier,
        ];
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
     * Clasifica un item de Deltron en el grupo/categoria correspondiente de Unike Store
     * cruzando category_name, subcategory_name y titulo comercial.
     */
    public function clasificarGrupoSugerido(array $item, $grupos): array
    {
        $catDeltron = strtoupper($item['category_name'] ?? '');
        $subDeltron = strtoupper($item['subcategory_name'] ?? '');
        $titulo = strtoupper(DeltronTextHelper::obtenerTituloComercialDeltron($item));
        $name = strtoupper($item['name'] ?? '');

        $context = [
            'catDeltron' => $catDeltron,
            'subDeltron' => $subDeltron,
            'titulo'     => $titulo,
            'name'       => $name,
            'item'       => $item,
        ];

        $idGrupoSugerido = null;

        // 1. Reglas de prioridad temprana de los clasificadores modulares (Mochilas/Maletas, Combos, Accesorios de Laptops)
        foreach ($this->classifiers as $classifier) {
            $earlyMatch = $classifier->classifyEarly($context);
            if ($earlyMatch !== null) {
                $idGrupoSugerido = $earlyMatch;
                break;
            }
        }

        // 2. Clasificación principal de categorías modulares (Laptops, Periféricos, etc.)
        if ($idGrupoSugerido === null) {
            foreach ($this->classifiers as $classifier) {
                $matchedGrupo = $classifier->classify($context);
                if ($matchedGrupo !== null) {
                    $idGrupoSugerido = $matchedGrupo;
                    break;
                }
            }
        }

        // 3. Reglas de compatibilidad para categorías pendientes de modularizar
        if ($idGrupoSugerido === null && (str_contains($catDeltron, 'FAN COOLER') || str_contains($titulo, 'COOLER PARA CPU') || str_contains($titulo, 'COOLER PARA PROCESADOR') || str_contains($subDeltron, 'COOLER LIQUIDO') || str_contains($subDeltron, 'FAN COOLER'))) {
            $idGrupoSugerido = 56; // Coolers (Componentes PC)
        }
        // 8. Deltron Category: CPUS (MICROPROCESADORES)
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'CPU') || str_contains($catDeltron, 'MICROPROCESADOR') || str_contains($subDeltron, 'CPU AMD') || str_contains($subDeltron, 'CPU INTEL') || str_starts_with($name, 'CPAM') || str_starts_with($name, 'CPIN') || str_contains($titulo, 'PROCESADOR AMD') || str_contains($titulo, 'PROCESADOR INTEL'))) {
            $idGrupoSugerido = 49; // Procesadores (Cat Componentes PC)
        }
        // 9. Deltron Category: TELEFONOS CELULARES
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'TELEFONOS CELULARES') || str_contains($catDeltron, 'SMARTPHONE') || str_contains($subDeltron, 'CELULAR') || str_contains($subDeltron, 'SMARTPHONE'))) {
            if (str_contains($catDeltron, 'ACCESORIO') || str_contains($subDeltron, 'ACCESORIO')) {
                if (str_contains($titulo, 'CARGADOR') || str_contains($name, 'ADAP')) $idGrupoSugerido = 67; // Cargadores
                elseif (str_contains($titulo, 'CASE') || str_contains($titulo, 'FUNDA') || str_contains($titulo, 'CARCASA')) $idGrupoSugerido = 66; // Carcasas
                elseif (str_contains($titulo, 'CABLE')) $idGrupoSugerido = 68; // Cables de datos
                else $idGrupoSugerido = 67; // Cargadores
            } else {
                $idGrupoSugerido = 64; // Celulares (Cat Smartphones)
            }
        }
        // 10. Deltron Category: TELEVISORES
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'TELEVISOR') || str_contains($subDeltron, 'TELEVISOR') || str_starts_with($name, 'TV '))) {
            $idGrupoSugerido = 16; // Televisores (Cat Monitores)
        }
        // 13. Deltron Category: TABLETS
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'TABLET') || str_contains($subDeltron, 'TABLET') || 
            str_contains($titulo, 'TABLET') || str_starts_with($name, 'TB ') || str_starts_with($name, 'TAB '))) {
            $idGrupoSugerido = 65; // Tablet (Cat 9 Smartphones)
        }
        // 14. Deltron Category: ESCANERES
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'ESCANER') || str_contains($subDeltron, 'ESCANER') || str_contains($titulo, 'ESCANER') || str_contains($titulo, 'ESCÁNER') || str_contains($titulo, 'SCANNER') || str_contains($subDeltron, 'SCAN/COD/BARRAS'))) {
            $idGrupoSugerido = 97; // ESCANER (Cat Impresoras)
        }
        // 15. Deltron Category: PROYECTORES
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'PROYECTOR') || str_contains($subDeltron, 'PROYECTOR') || str_contains($titulo, 'PROYECTOR') || str_starts_with($name, 'MMVDEP') || str_starts_with($name, 'PREP'))) {
            $idGrupoSugerido = 17; // Proyectores (Cat Monitores)
        }
        // 16. Deltron Category: IMPRESORAS Y SUMINISTROS
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'IMPRESORA') || str_contains($subDeltron, 'IMPRESORA') || str_contains($titulo, 'PRINTER'))) {
            if (str_contains($titulo, 'LASER') || str_contains($subDeltron, 'LASER')) $idGrupoSugerido = 41; // Impresora Laser
            elseif (str_contains($titulo, 'TERMICA') || str_contains($titulo, 'TERM') || str_contains($subDeltron, 'TERMICA')) $idGrupoSugerido = 40; // Impresora Termica
            elseif (str_contains($titulo, 'MATRICIAL')) $idGrupoSugerido = 43; // Impresora Matricial
            else $idGrupoSugerido = 42; // Impresora Tinta
        }
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'SUMINISTROS') || str_contains($subDeltron, 'SUMINIST') || str_contains($catDeltron, 'IMPRESION DE MANUALIDADES'))) {
            if (str_contains($subDeltron, 'TONER') || str_contains($titulo, 'TONER')) $idGrupoSugerido = 173; // Toner Original
            elseif (str_contains($subDeltron, 'TINTAS') || str_contains($subDeltron, 'BOTELLAS') || str_contains($titulo, 'TINTA') || str_contains($titulo, 'BOTELLA')) $idGrupoSugerido = 44; // Tintas
            elseif (str_contains($titulo, 'CINTA')) $idGrupoSugerido = 83; // Cinta para impresora matricial
            else $idGrupoSugerido = 44;
        }
        // 17. Deltron Category: MONITORES
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'MONITOR') || str_contains($subDeltron, 'MONITOR') || str_contains($titulo, 'MONITOR') || str_contains($catDeltron, 'DIGITAL SIGNAGE'))) {
            if (str_contains($catDeltron, 'RACK') || str_contains($subDeltron, 'RACK') || str_contains($titulo, 'RACK') || str_contains($titulo, 'SOPORTE')) $idGrupoSugerido = 19; // Soporte Rack
            elseif (str_contains($titulo, 'PORTATIL') || str_contains($titulo, 'PORTÁTIL')) $idGrupoSugerido = 82; // Monitor portátil
            elseif (str_contains($titulo, '4K') || str_contains($titulo, 'UHD')) $idGrupoSugerido = 13; // Monitor UHD 4K
            elseif (str_contains($titulo, 'QHD') || str_contains($titulo, '2K')) $idGrupoSugerido = 12; // Monitor QHD
            elseif (str_contains($titulo, 'CURVO')) $idGrupoSugerido = 14; // Monitor Curvo
            elseif (str_contains($titulo, 'ULTRAWIDE')) $idGrupoSugerido = 15; // Monitor UltraWide
            else $idGrupoSugerido = 11; // Monitor FHD
        }
        // 18. Deltron Category: TARJETAS DE VIDEO
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'TARJETAS DE VIDEO') || str_contains($catDeltron, 'DISPOSITIVOS DE VIDEO') ||
            str_contains($subDeltron, 'VIDEO') || str_contains($titulo, 'TARJETA DE VIDEO') || 
            str_starts_with($name, 'VGA ') || str_starts_with($name, 'VD'))) {
            $idGrupoSugerido = 55; // Tarjetas Graficas (Cat Componentes PC)
        }
        // 19. Deltron Category: MOTHERBOARDS
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'MOTHERBOARD') || str_contains($subDeltron, 'MB SOCKET') || str_contains($catDeltron, 'PLACA'))) {
            $idGrupoSugerido = 50; // Placas Madre
        }
        // 20. Deltron Category: MEMORIAS RAM
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'MEMORIAS RAM') || str_contains($subDeltron, 'MEM DDR'))) {
            $idGrupoSugerido = 51; // Memorias RAM
        }
        // 21. Deltron Category: DISCOS SOLIDOS (SSD)
        elseif ($idGrupoSugerido === null && !str_contains($catDeltron, 'NOTEBOOK') && !str_contains($catDeltron, 'PORTATIL') && !str_contains($catDeltron, 'COMPUTADORA') && !str_starts_with($name, 'NB ') && !str_starts_with($titulo, 'NB ') && (str_contains($catDeltron, 'DISCO SOLIDO') || str_contains($catDeltron, 'UNIDADES DE DISCO SOLIDO') || (str_contains($subDeltron, 'SSD') && !str_contains($subDeltron, 'NOTEBOOK')) || str_contains($catDeltron, 'STORAGE') || str_starts_with($titulo, 'SSD ') || str_starts_with($name, 'SSD '))) {
            $idGrupoSugerido = 94; // Disco Solido
        }
        // 22. Deltron Category: DISCOS DUROS INTERNOS / EXTERNOS
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'DISCOS DUROS') || str_contains($subDeltron, 'DISCO DURO'))) {
            $idGrupoSugerido = 52; // Discos Duro
        }
        // 23. Deltron Category: CASES & FUENTES
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'CASES (CAJAS)') || str_contains($catDeltron, 'CASES, FUENTE') || str_contains($catDeltron, 'CASES'))) {
            if (str_contains($catDeltron, 'CASES, FUENTE') || (str_contains($subDeltron, 'FUENTE') && !str_contains($subDeltron, 'SIN FUENTE'))) {
                $idGrupoSugerido = 53; // Fuente de poder
            } else {
                $idGrupoSugerido = 54; // Case
            }
        }
        // 26. Deltron Category: SOFTWARE / LICENCIAS
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'SOFTWARE') || str_contains($catDeltron, 'LICENCIA') || 
            str_contains($subDeltron, 'SOFTWARE') || str_contains($subDeltron, 'LICENCIA') || str_contains($subDeltron, 'ESD') || str_contains($subDeltron, 'OFFICE') ||
            str_contains($catDeltron, 'MICROSOFT') ||
            str_contains($titulo, 'WINDOWS') || str_contains($titulo, 'OFFICE') || str_contains($titulo, 'ANTIVIRUS') ||
            str_contains($titulo, 'LICENCIA') || str_contains($titulo, 'MICROSOFT 365') || str_contains($titulo, 'M365') ||
            str_contains($name, 'OFFICE') || str_contains($name, 'MICROSOFT 365') || str_contains($name, 'LIC') ||
            str_starts_with($name, 'WIN ') || str_starts_with($name, 'WIN11') || str_starts_with($name, 'MS'))) {
            $idGrupoSugerido = 62; // Software
        }
        // 27. Deltron Category: SERVIDORES
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'SERVIDORES') || str_contains($subDeltron, 'SERVIDORES'))) {
            $idGrupoSugerido = 61; // Servidores
        }
        // 28. Deltron Category: REDES LAN/WAN & INALAMBRICAS
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'REDES LAN/WAN') || str_contains($catDeltron, 'REDES INALAMBRICAS') || str_contains($subDeltron, 'RED'))) {
            if (str_contains($titulo, 'SWITCH')) $idGrupoSugerido = 63; // Switch
            elseif (str_contains($titulo, 'CAMARA') || str_contains($subDeltron, 'CAMARA')) $idGrupoSugerido = 58; // Camaras de seguridad
            else $idGrupoSugerido = 59; // Router WIFI
        }
        // 29. Deltron Category: CABLES / ADAPTADORES / MISCELANEOS
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'CABLEADO') || str_contains($titulo, 'CABLE') || str_contains($titulo, 'PATCH CORD'))) {
            $idGrupoSugerido = 68; // Cables de datos
        }
        elseif ($idGrupoSugerido === null && (str_contains($titulo, 'ADAPTADOR') || str_contains($titulo, 'HUB') || str_contains($titulo, 'MULTIPUERTO'))) {
            if (str_contains($titulo, 'RED') || str_contains($titulo, 'ETHERNET') || str_contains($titulo, 'RJ45')) $idGrupoSugerido = 72; // Adaptador de red
            elseif (str_contains($titulo, 'HDMI') || str_contains($titulo, 'VGA') || str_contains($titulo, 'DISPLAYPORT')) $idGrupoSugerido = 74; // Adaptador de video
            elseif (str_contains($titulo, 'HUB') || str_contains($titulo, 'MULTIPUERTO')) $idGrupoSugerido = 87; // Multipuertos
            else $idGrupoSugerido = 68; // Cables de datos
        }
        // 30. Deltron Category: MEMORIAS FLASH
        elseif ($idGrupoSugerido === null && (str_contains($catDeltron, 'MEMORIAS FLASH') || str_contains($subDeltron, 'MEM FLASH') || str_contains($name, 'USB DRIVE'))) {
            if (str_contains($subDeltron, 'SECURE DIGITAL') || str_contains($titulo, 'MICROSD') || str_contains($titulo, 'MEMORIA SD')) {
                $idGrupoSugerido = 39; // Memorias SD
            } else {
                $idGrupoSugerido = 38; // Memorias USB
            }
        }
        // 34. SMART HOME / IOT
        elseif ($idGrupoSugerido === null && str_contains($catDeltron, 'SMART HOME')) {
            if (str_contains($titulo, 'CAMARA')) $idGrupoSugerido = 58; // Camaras de seguridad
            elseif (str_contains($titulo, 'FOCO') || str_contains($titulo, 'BOMBILLA')) $idGrupoSugerido = 91; // Foco Smart
            else $idGrupoSugerido = 59; // Router WIFI
        }

        if ($idGrupoSugerido === null) {
            $idGrupoSugerido = PerifericosClassifier::GRUPO_MOUSES; // Default: Perifericos / Mouses (25)
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

        return 1; // Fallback generico
    }

    /**
     * Genera el siguiente codigo de producto correlativo de 10 caracteres para un grupo.
     */
    public function generarSiguienteCodigoProducto(int $idGrupo, string $nombreGrupo): string
    {
        $maxCode = Producto::where('idGrupo', $idGrupo)->max('codigoProducto');
        if ($maxCode && strlen($maxCode) >= 10) {
            $prefix = substr($maxCode, 0, 6);
            $num = (int) substr($maxCode, 6);
            return $prefix . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
        }

        $clean = strtoupper(preg_replace('/[^A-Z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $nombreGrupo)));
        $prefix = str_pad(substr($clean, 0, 6), 6, '0');
        return $prefix . '0001';
    }

    /**
     * Detecta si un producto de Deltron corresponde a una variante (principalmente color)
     * basandose en sufijos de SKU, tokens de Deltron y nombre/descripcion.
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

        // 1. Buscar token al final del nombre oficial de Deltron
        $words = preg_split('/[\s,\-\/]+/', $name);
        $lastWord = end($words);
        foreach ($colorMap as [$token, $hex, $label]) {
            if ($lastWord === $token) {
                return ['tipo' => 'COLOR', 'valor' => $label, 'hex' => $hex, 'token' => $token];
            }
        }

        // 2. Comparar SKU con part_number para aislar el sufijo
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

        // 3. Sufijo directo al final del SKU
        foreach ($shortSkuSuffixes as $suf => [$hex, $label]) {
            if (str_ends_with($sku, $suf)) {
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
            $nombrePadre = preg_replace('/\b' . preg_quote($token, '/') . '\b/i', '', $nombrePadre);
            $nombrePadre = preg_replace('/(\d+)' . preg_quote($token, '/') . '(?=[\s,]|$)/i', '$1', $nombrePadre);
            if (!empty($variante['valor'])) {
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
