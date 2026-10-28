<?php

namespace App\Services\Deltron;

class DeltronTextHelper
{
    /**
     * Limpia emojis y caracteres UTF-8 de 4 bytes para total compatibilidad con bases de datos en HostGator (utf8_general_ci / utf8mb3).
     */
    public static function stripEmojis(?string $string): ?string
    {
        if ($string === null) {
            return null;
        }
        return preg_replace('/[\xF0-\xF7][\x80-\xBF]{3}/', '', $string);
    }

    /**
     * Limpia el texto de descripcion de Deltron eliminando su separador interno [@@@]
     * y convirtiendo saltos de linea simples para presentacion legible.
     */
    public static function cleanDeltronText(?string $string): string
    {
        if ($string === null || trim($string) === '') {
            return '';
        }
        $clean = self::stripEmojis($string);
        // Deltron usa [@@@] para separar el titular del cuerpo de la descripcion
        $clean = str_replace(["\r\n", "\r"], "\n", $clean);
        $clean = str_replace(['[@@@]', '<br>', '<br/>', '<br />', '&nbsp;'], ["\n\n", "\n", "\n", "\n", ' '], $clean);
        $clean = strip_tags($clean);
        $clean = preg_replace("/[ \t]+/", ' ', $clean);
        $clean = preg_replace("/\n{3,}/", "\n\n", $clean);
        return trim($clean);
    }

    /**
     * Convierte la tabla HTML de especificaciones tecnicas (specs_all_html) de Deltron
     * en texto plano estructurado con categorias y vinetas para catalogo de productos.
     */
    public static function parseSpecsHtmlToText(?string $html): string
    {
        if (empty($html) || trim($html) === '') {
            return '';
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $tables = $xpath->query('//table[not(.//table)]');
        if ($tables->length === 0) {
            $tables = $xpath->query('//table');
        }

        $output = [];
        $currentSection = '';

        foreach ($tables as $table) {
            $rows = $xpath->query('./tr | ./tbody/tr', $table);
            foreach ($rows as $row) {
                $cols = $xpath->query('./td | ./th', $row);
                if ($cols->length === 0) continue;

                $cellTexts = [];
                foreach ($cols as $col) {
                    $t = trim(preg_replace('/\s+/', ' ', $col->textContent));
                    if ($t !== '') {
                        $cellTexts[] = $t;
                    }
                }

                if (empty($cellTexts)) continue;

                if (count($cellTexts) === 1) {
                    $val = $cellTexts[0];
                    if (strlen($val) < 60 && !str_contains($val, ';')) {
                        $output[] = "\n[" . strtoupper($val) . "]";
                    } else {
                        $output[] = "- $val";
                    }
                } elseif (count($cellTexts) === 2) {
                    $k = rtrim($cellTexts[0], ':');
                    $v = $cellTexts[1];
                    $output[] = "- $k: $v";
                } elseif (count($cellTexts) === 3) {
                    $section = $cellTexts[0];
                    $k = rtrim($cellTexts[1], ':');
                    $v = $cellTexts[2];
                    if ($section !== $currentSection) {
                        $currentSection = $section;
                        $output[] = "\n[" . strtoupper($section) . "]";
                    }
                    $output[] = "- $k: $v";
                } else {
                    $output[] = "- " . implode(' - ', $cellTexts);
                }
            }
        }

        $res = trim(implode("\n", $output));
        return self::stripEmojis($res);
    }

    /**
     * Arma la descripcion comercial de un producto de Deltron tal como se ve en su web:
     * texto de la pestana Descripcion (description_large, o description_small si no hay)
     * seguido de la pestana Especificaciones convertida a texto plano.
     */
    public static function construirDescripcionProducto(array $item): string
    {
        $descLarga = self::cleanDeltronText($item['description_large'] ?? '');
        $descCorta = self::cleanDeltronText($item['description_small'] ?? '');
        $base = $descLarga !== '' ? $descLarga : $descCorta;

        $specsHtml = $item['specs']['specs_all_html'] ?? ($item['specs_all_html'] ?? ($item['specs_html'] ?? ''));
        $specsTexto = self::parseSpecsHtmlToText(is_string($specsHtml) ? self::stripEmojis($specsHtml) : '');

        if ($base !== '' && $specsTexto !== '') {
            return $base . "\n\n" . $specsTexto;
        }
        return $base !== '' ? $base : $specsTexto;
    }

    /**
     * Recorta un texto largo en un salto de linea para no cortar una especificacion a la mitad.
     */
    public static function recortarTextoEnLinea(string $texto, int $max = 5000): string
    {
        if (strlen($texto) <= $max) {
            return $texto;
        }
        $corte = substr($texto, 0, $max - 3);
        $ultimoSalto = strrpos($corte, "\n");
        if ($ultimoSalto !== false && $ultimoSalto > ($max * 0.6)) {
            $corte = substr($corte, 0, $ultimoSalto);
        }
        return rtrim($corte) . '...';
    }

    /**
     * Extrae el atributo modelo de Deltron desde el payload/array del producto (top-level o desde tabla de specs).
     */
    public static function extraerModeloDesdeItem(array $item): ?string
    {
        if (!empty($item['modelo_real']) && $item['modelo_real'] !== '-') {
            return trim($item['modelo_real']);
        }
        if (!empty($item['modelo']) && $item['modelo'] !== '-') {
            return trim($item['modelo']);
        }
        if (!empty($item['model']) && $item['model'] !== '-') {
            return trim($item['model']);
        }

        $specsHtml = $item['specs']['specs_all_html'] 
            ?? ($item['specs']['specs_html'] 
            ?? ($item['specs_all_html'] 
            ?? ($item['specs_html'] ?? '')));

        if (is_string($specsHtml) && trim($specsHtml) !== '') {
            return self::extraerModeloRealDesdeSpecs($specsHtml);
        }

        return null;
    }

    /**
     * Extrae el modelo verdadero del fabricante desde la tabla de especificaciones tecnicas (specs_html) de Deltron.
     */
    public static function extraerModeloRealDesdeSpecs(?string $html): ?string
    {
        if (empty($html) || trim($html) === '') {
            return null;
        }

        // 1. Buscar en filas de tabla HTML (tr) que contengan celda MODELO
        if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $html, $rowMatches)) {
            foreach ($rowMatches[1] as $row) {
                if (preg_match('/<(?:td|th)[^>]*>\s*MODELO\s*:?\s*<\/(?:td|th)>/i', $row)) {
                    if (preg_match_all('/<(?:td|th)[^>]*>(.*?)<\/(?:td|th)>/is', $row, $cellMatches)) {
                        $cells = array_map(function($c) { return trim(strip_tags($c)); }, $cellMatches[1]);
                        $lastValid = null;
                        foreach ($cells as $cell) {
                            $cUpper = strtoupper($cell);
                            if (!empty($cell) && $cUpper !== 'MODELO' && $cUpper !== 'MODELO:' && $cUpper !== '-' && $cUpper !== 'N/A') {
                                $lastValid = $cell;
                            }
                        }
                        if ($lastValid && strlen($lastValid) <= 100) {
                            return self::stripEmojis($lastValid);
                        }
                    }
                }
            }
        }

        // 2. Fallback de texto plano
        if (preg_match('/(?:^|\n|\r)\s*MODELO\s*:\s*([^\r\n<]+)/i', strip_tags($html), $m)) {
            $val = trim($m[1]);
            $vUpper = strtoupper($val);
            if (!empty($val) && $vUpper !== 'MODELO' && $vUpper !== '-' && $vUpper !== 'N/A' && strlen($val) <= 100) {
                return self::stripEmojis($val);
            }
        }

        return null;
    }

    /**
     * Obtiene el titulo comercial limpio a partir de description_small o el segmento antes de [@@@]
     */
    public static function obtenerTituloComercialDeltron(array $item): string
    {
        // 1. Probar description_small
        $descSmall = trim(self::stripEmojis($item['description_small'] ?? ''));
        $descSmall = strip_tags(str_replace(['&nbsp;', '&amp;', '&quot;'], [' ', '&', '"'], $descSmall));
        $descSmall = preg_replace('/[ \t]+/', ' ', $descSmall);
        if (!empty($descSmall) && mb_strlen($descSmall) > 5) {
            return trim($descSmall);
        }

        // 2. Probar segmento antes de [@@@] en description_large
        $descLarge = $item['description_large'] ?? '';
        if (!empty($descLarge) && str_contains($descLarge, '[@@@]')) {
            $parts = explode('[@@@]', $descLarge);
            $t = trim(self::stripEmojis($parts[0] ?? ''));
            $t = strip_tags(str_replace(['&nbsp;', '&amp;', '&quot;'], [' ', '&', '"'], $t));
            $t = preg_replace('/[ \t]+/', ' ', $t);
            if (!empty($t) && mb_strlen($t) > 5) {
                return trim($t);
            }
        }

        // 3. Fallback al nombre de facturacion
        $name = trim(self::stripEmojis($item['name'] ?? ''));
        return !empty($name) ? $name : 'Producto Deltron';
    }

    /**
     * Normaliza los textos de garantia de Deltron a los valores estandar de Unike Store
     */
    public static function normalizarGarantiaDeltron(?string $warranty): string
    {
        if (empty($warranty) || trim($warranty) === '') {
            return '12 meses';
        }

        $wUpper = strtoupper(trim(self::stripEmojis($warranty)));

        if (str_contains($wUpper, '36 MESES') || str_contains($wUpper, '3 ANOS') || str_contains($wUpper, '3 AÃ‘OS') || str_contains($wUpper, '3 A')) {
            return '36 meses';
        }
        if (str_contains($wUpper, '24 MESES') || str_contains($wUpper, '2 ANOS') || str_contains($wUpper, '2 AÃ‘OS')) {
            return '24 meses';
        }
        if (str_contains($wUpper, '12 MESES') || str_contains($wUpper, '1 ANO') || str_contains($wUpper, '1 AÃ‘O')) {
            return '12 meses';
        }
        if (str_contains($wUpper, '6 MESES')) {
            return '6 meses';
        }
        if (str_contains($wUpper, '3 MESES')) {
            return '3 meses';
        }
        if (str_contains($wUpper, 'NO APLICA') || str_contains($wUpper, 'NO TIENE')) {
            return 'No tiene';
        }

        return '12 meses';
    }

    /**
     * Devuelve la URL CDN oficial de Deltron para la imagen principal de un producto segun su SKU.
     */
    public static function obtenerUrlCdnDeltron(string $sku): ?string
    {
        $skuClean = strtolower(trim($sku));
        if (strlen($skuClean) < 4) {
            return null;
        }
        $p1 = substr($skuClean, 0, 2);
        $p2 = substr($skuClean, 2, 2);
        return "https://imagenes.deltron.com.pe/images/productos/on-line/items/large/{$p1}/{$p2}/{$skuClean}.jpg";
    }

    /**
     * Obtiene la ruta física destino para guardar las imágenes de productos,
     * adaptándose automáticamente a entorno local (storage/productos) o producción (public_html/images/productos).
     */
    public static function getDestinationPathProductos(): string
    {
        if (app()->environment('local')) {
            $localPath = public_path('storage/productos');
            if (!is_dir($localPath)) {
                @mkdir($localPath, 0755, true);
            }
            return $localPath;
        }

        $baseDir = dirname(base_path());
        $prodPath = $baseDir . '/public_html/images/productos';
        if (is_dir($prodPath) || is_dir($baseDir . '/public_html/images')) {
            if (!is_dir($prodPath)) {
                @mkdir($prodPath, 0755, true);
            }
            return $prodPath;
        }

        $fallback = public_path('storage/productos');
        if (!is_dir($fallback)) {
            @mkdir($fallback, 0755, true);
        }
        return $fallback;
    }
}
