<?php

namespace App\Services\Deltron;

use App\Models\Catalogo\Producto;

class DeltronClassifierService
{
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

        $idGrupoSugerido = null;

        // 1. Deltron Category: TABLETS
        if (str_contains($catDeltron, 'TABLET') || str_contains($subDeltron, 'TABLET') || 
            str_contains($titulo, 'TABLET') || str_starts_with($name, 'TB ') || str_starts_with($name, 'TAB ')) {
            $idGrupoSugerido = 65; // Tablet (Cat 9 Smartphones)
        }
        // 2. Deltron Category: MOCHILAS / MALETAS
        elseif (str_contains($catDeltron, 'MOCHILA') || str_contains($subDeltron, 'MOCHILA') || 
            str_contains($titulo, 'BACKPACK') || str_contains($titulo, 'MOCHILA')) {
            $idGrupoSugerido = 35; // Mochilas
        }
        // 3. Deltron Category: IMPRESORAS Y SUMINISTROS
        elseif (str_contains($catDeltron, 'IMPRESORA') || str_contains($subDeltron, 'IMPRESORA') || str_contains($titulo, 'PRINTER')) {
            if (str_contains($titulo, 'LASER') || str_contains($subDeltron, 'LASER')) $idGrupoSugerido = 41; // Impresora Laser
            elseif (str_contains($titulo, 'TERMICA') || str_contains($titulo, 'TERM') || str_contains($subDeltron, 'TERMICA')) $idGrupoSugerido = 40; // Impresora Termica
            elseif (str_contains($titulo, 'MATRICIAL')) $idGrupoSugerido = 43; // Impresora Matricial
            else $idGrupoSugerido = 42; // Impresora Tinta
        }
        elseif (str_contains($catDeltron, 'SUMINISTROS') || str_contains($subDeltron, 'SUMINIST')) {
            if (str_contains($subDeltron, 'TONER') || str_contains($titulo, 'TONER')) $idGrupoSugerido = 173; // Toner Original
            elseif (str_contains($subDeltron, 'TINTAS') || str_contains($subDeltron, 'BOTELLAS') || str_contains($titulo, 'TINTA') || str_contains($titulo, 'BOTELLA')) $idGrupoSugerido = 44; // Tintas
            else $idGrupoSugerido = 44;
        }
        // 4. Deltron Category: COMPUTADORAS DESKTOP / WORKSTATION / AIO
        elseif (str_contains($catDeltron, 'COMPUTADORAS DESKTOP') || str_contains($titulo, 'AIO ') || 
            str_contains($subDeltron, 'AIO') || str_contains($titulo, 'ALL IN ONE') || str_contains($titulo, 'ALL-IN-ONE') ||
            str_starts_with($name, 'WS ') || str_starts_with($name, 'WKS ') || str_starts_with($name, 'PC ')) {
            if (str_contains($titulo, 'AIO') || str_contains($subDeltron, 'AIO') || str_contains($titulo, 'ALL-IN-ONE') || str_contains($titulo, 'ALL IN ONE')) {
                $idGrupoSugerido = 10; // PC All in One
            } elseif (str_contains($titulo, 'GAMER') || str_contains($subDeltron, 'GAMER')) {
                $idGrupoSugerido = 7; // PC Gamer
            } else {
                $idGrupoSugerido = 8; // PC Empresarial / Workstation
            }
        }
        // 5. Deltron Category: NOTEBOOKS / LAPTOPS
        elseif (str_contains($catDeltron, 'NOTEBOOK') || str_contains($catDeltron, 'PORTATIL') || str_contains($catDeltron, 'LAPTOP') || 
            str_contains($subDeltron, 'NOTEBOOK') || str_contains($titulo, 'LAPTOP') || str_contains($titulo, 'NOTEBOOK')) {
            if (str_contains($titulo, 'GAMER') || str_contains($subDeltron, 'GAMER') || str_contains($titulo, 'RTX') || 
                str_contains($titulo, 'LOQ') || str_contains($titulo, 'VICTUS') || str_contains($titulo, 'TUF')) {
                $idGrupoSugerido = 1; // Laptop Gamer
            } else {
                $idGrupoSugerido = 3; // Laptop Hogar
            }
        }
        // 6. Deltron Category: MONITORES
        elseif (str_contains($catDeltron, 'MONITOR') || str_contains($subDeltron, 'MONITOR') || str_contains($titulo, 'MONITOR')) {
            if (str_contains($titulo, '4K') || str_contains($titulo, 'UHD')) $idGrupoSugerido = 13; // Monitor UHD 4K
            elseif (str_contains($titulo, 'QHD') || str_contains($titulo, '2K')) $idGrupoSugerido = 12; // Monitor QHD
            elseif (str_contains($titulo, 'CURVO')) $idGrupoSugerido = 14; // Monitor Curvo
            else $idGrupoSugerido = 11; // Monitor FHD
        }
        // 7. Deltron Category: TARJETAS DE VIDEO
        elseif (str_contains($catDeltron, 'TARJETAS DE VIDEO') || str_contains($catDeltron, 'DISPOSITIVOS DE VIDEO') ||
            str_contains($subDeltron, 'VIDEO') || str_contains($titulo, 'TARJETA DE VIDEO') || 
            str_starts_with($name, 'VGA ') || str_starts_with($name, 'VD')) {
            $idGrupoSugerido = 55; // Tarjetas Graficas (Cat Componentes PC)
        }
        // 8. Deltron Category: MOTHERBOARDS
        elseif (str_contains($catDeltron, 'MOTHERBOARD') || str_contains($subDeltron, 'MB SOCKET') || str_contains($catDeltron, 'PLACA')) {
            $idGrupoSugerido = 50; // Placas Madre
        }
        // 9. Deltron Category: MEMORIAS RAM
        elseif (str_contains($catDeltron, 'MEMORIAS RAM') || str_contains($subDeltron, 'MEM DDR')) {
            $idGrupoSugerido = 51; // Memorias RAM
        }
        // 10. Deltron Category: DISCOS SOLIDOS (SSD)
        elseif (str_contains($catDeltron, 'DISCO SOLIDO') || str_contains($subDeltron, 'SSD')) {
            $idGrupoSugerido = 94; // Disco Solido
        }
        // 11. Deltron Category: DISCOS DUROS INTERNOS / EXTERNOS
        elseif (str_contains($catDeltron, 'DISCOS DUROS') || str_contains($subDeltron, 'DISCO DURO')) {
            $idGrupoSugerido = 52; // Discos Duro
        }
        // 12. Deltron Category: CASES & FUENTES
        elseif (str_contains($catDeltron, 'CASES (CAJAS)') || str_contains($catDeltron, 'CASES, FUENTE') || str_contains($catDeltron, 'CASES')) {
            if (str_contains($catDeltron, 'CASES, FUENTE') || (str_contains($subDeltron, 'FUENTE') && !str_contains($subDeltron, 'SIN FUENTE'))) {
                $idGrupoSugerido = 53; // Fuente de poder
            } else {
                $idGrupoSugerido = 54; // Case
            }
        }
        // 13. Deltron Category: FAN COOLER PARA CPU
        elseif (str_contains($catDeltron, 'FAN COOLER') || str_contains($titulo, 'COOLER PARA CPU') || str_contains($titulo, 'COOLER PARA PROCESADOR') || str_contains($subDeltron, 'COOLER LIQUIDO')) {
            $idGrupoSugerido = 56; // Coolers (Componentes PC)
        }
        // 14. Deltron Category: ACCESORIOS DE NOTEBOOK (Coolers, Fundas)
        elseif (str_contains($catDeltron, 'NOTEBOOK , ACCESORIOS') || str_contains($subDeltron, 'NOTEBOOK, ACCESORIOS')) {
            if (str_contains($titulo, 'COOLER') || str_contains($titulo, 'BASE')) {
                $idGrupoSugerido = 4; // Cooler Laptop
            } elseif (str_contains($titulo, 'FUNDA') || str_contains($titulo, 'CASE')) {
                $idGrupoSugerido = 5; // Fundas Laptop
            } else {
                $idGrupoSugerido = 4; // Cooler Laptop
            }
        }
        // 15. Deltron Category: UPS / ESTABILIZADORES
        elseif (str_contains($catDeltron, 'UPS') || str_contains($catDeltron, 'ESTABILIZADORES') || str_contains($subDeltron, 'UPS')) {
            if (str_contains($subDeltron, 'ESTABILIZADOR') || str_contains($titulo, 'ESTABILIZADOR') || str_contains($titulo, 'AVR')) {
                $idGrupoSugerido = 28; // Estabilizadores
            } else {
                $idGrupoSugerido = 29; // UPS
            }
        }
        // 16. Deltron Category: AUDIO
        elseif (str_contains($catDeltron, 'AUDIO') || str_contains($subDeltron, 'AUDIO') || 
            str_contains($titulo, 'AUDIFONO') || str_contains($titulo, 'HEADSET') || str_contains($titulo, 'AURICULAR') ||
            str_contains($titulo, 'TWS') || str_contains($name, 'EARB')) {
            if (str_contains($titulo, 'PARLANTE') || str_contains($titulo, 'SPEAKER') || str_contains($subDeltron, 'PARLANTE')) {
                $idGrupoSugerido = 27; // Parlantes
            } else {
                $idGrupoSugerido = 26; // Auriculares
            }
        }
        // 17. Deltron Category: SOFTWARE / LICENCIAS
        elseif (str_contains($catDeltron, 'SOFTWARE') || str_contains($catDeltron, 'LICENCIA') || 
            str_contains($subDeltron, 'SOFTWARE') || str_contains($subDeltron, 'LICENCIA') || str_contains($subDeltron, 'ESD') || str_contains($subDeltron, 'OFFICE') ||
            str_contains($titulo, 'WINDOWS') || str_contains($titulo, 'OFFICE') || str_contains($titulo, 'ANTIVIRUS') ||
            str_contains($titulo, 'LICENCIA') || str_contains($titulo, 'MICROSOFT 365') || str_contains($titulo, 'M365') ||
            str_contains($name, 'OFFICE') || str_contains($name, 'MICROSOFT 365') || str_contains($name, 'LIC') ||
            str_starts_with($name, 'WIN ') || str_starts_with($name, 'WIN11') || str_starts_with($name, 'MS')) {
            $idGrupoSugerido = 62; // Software
        }
        // 18. Deltron Category: SERVIDORES
        elseif (str_contains($catDeltron, 'SERVIDORES') || str_contains($subDeltron, 'SERVIDORES')) {
            $idGrupoSugerido = 61; // Servidores
        }
        // 19. Deltron Category: REDES LAN/WAN
        elseif (str_contains($catDeltron, 'REDES LAN/WAN') || str_contains($subDeltron, 'RED')) {
            if (str_contains($titulo, 'SWITCH')) $idGrupoSugerido = 63; // Switch
            else $idGrupoSugerido = 59; // Router WIFI
        }
        // 20. Deltron Category: MEMORIAS FLASH
        elseif (str_contains($catDeltron, 'MEMORIAS FLASH') || str_contains($subDeltron, 'MEM FLASH') || str_contains($name, 'USB DRIVE')) {
            if (str_contains($subDeltron, 'SECURE DIGITAL') || str_contains($titulo, 'MICROSD') || str_contains($titulo, 'MEMORIA SD')) {
                $idGrupoSugerido = 39; // Memorias SD
            } else {
                $idGrupoSugerido = 38; // Memorias USB
            }
        }
        // 21. Deltron Category: PROYECTORES
        elseif (str_contains($catDeltron, 'PROYECTOR') || str_contains($subDeltron, 'PROYECTOR') || str_contains($titulo, 'PROYECTOR')) {
            $idGrupoSugerido = 17; // Proyectores
        }
        // 22. Deltron Category: MOUSE
        elseif (str_contains($catDeltron, 'MOUSE') || str_contains($subDeltron, 'MOUSE') || str_contains($titulo, 'MOUSE') || str_contains($titulo, 'RATON')) {
            $idGrupoSugerido = 25; // Mouses
        }
        // 23. Deltron Category: TECLADOS
        elseif (str_contains($catDeltron, 'TECLADO') || str_contains($subDeltron, 'TECLADO') || str_contains($titulo, 'TECLADO') || str_contains($titulo, 'KEYBOARD')) {
            $idGrupoSugerido = 24; // Teclados
        }
        // 24. Deltron Category: PAD / MOUSEPAD
        elseif (str_contains($catDeltron, 'PAD') || str_contains($subDeltron, 'PAD') || str_contains($titulo, 'MOUSEPAD') || str_contains($titulo, 'ALFOMBR')) {
            $idGrupoSugerido = 31; // Alfombrillas
        }
        else {
            $idGrupoSugerido = 25; // Default: Perifericos / Mouses
        }

        $grupoObj = $grupos->get($idGrupoSugerido);
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
