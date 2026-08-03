<?php

namespace App\Services\Falabella\Mappers;

use App\Models\Catalogo\Producto;
use App\Services\Falabella\Contracts\FalabellaCategoryMapper;

/**
 * Mapper para Procesadores (idGrupoProducto = 49)
 *
 * Columnas del template ProcesadorTemplate-Express.xlsx:
 *   A  = Nombre
 *   B  = Marca
 *   C  = Descripción
 *   D  = Categoría primaria
 *   E  = SKU del vendedor
 *   F  = Código de barras
 *   G  = Variación
 *   H  = QuantityFalabella (Stock)
 *   I  = PriceFalabella (Precio Normal)
 *   J  = SalePriceFalabella (Precio Venta)
 *   K  = SaleStartDateFalabella
 *   L  = SaleEndDateFalabella
 *   M  = AnoFabricacion
 *   N  = Segmento
 *   O  = MarcaProcesador
 *   P  = ProcesadorEspecificoTxt
 *   Q  = CompatibleCon
 *   R  = ConectividadConexion
 *   S  = Dimensiones
 *   T  = Condición del Producto
 *   U  = Ancho del paquete
 *   V  = Largo del paquete
 *   W  = Alto del paquete
 *   X  = Peso del paquete
 */
class ProcesadorMapper implements FalabellaCategoryMapper
{
    const CATEGORIA = '761 - Electrónica / Computación / Componentes informáticos / Procesadores de computadores';

    public function getTemplateFile(): string
    {
        return 'falabella/ProcesadorTemplate-Express.xlsx';
    }

    public function getFilePrefix(): string
    {
        return 'ProcesadorTemplate-Express_';
    }

    public function getStartRow(): int
    {
        return 5;
    }

    public function sugerirTitulos(Producto $producto, array $d): array
    {
        $base     = $producto->nombreProducto;
        $marca    = $d['marca'] ?? '';
        $modelo   = $producto->modelo ?? '';
        $caract   = $d['caractMap'];

        // Título 1: Original
        $titulo1 = $base;
        if (!preg_match('/' . preg_quote($marca, '/') . '/i', $titulo1) && !empty($marca)) {
            $titulo1 = $marca . ' ' . $titulo1;
        }
        if (!preg_match('/' . preg_quote($modelo, '/') . '/i', $titulo1) && !empty($modelo)) {
            $titulo1 .= ' ' . $modelo;
        }

        // Título 2: Gamer
        $titulo2 = $titulo1 . ' Gamer';

        // Título 3: Para PC
        $titulo3 = $titulo1 . ' Para PC';

        return [
            'titulo1' => $this->ajustarLongitudFalabella($titulo1),
            'titulo2' => $this->ajustarLongitudFalabella($titulo2),
            'titulo3' => $this->ajustarLongitudFalabella($titulo3),
        ];
    }

    public function getVariaciones(Producto $producto, callable $buildSku, ?object $user, array $titulos = []): array
    {
        $variaciones = [];
        
        $t1 = !empty($titulos['titulo1']) ? $titulos['titulo1'] : $producto->nombreProducto;
        $variaciones[] = ['titulo' => $t1, 'sku' => $buildSku($producto->codigoProducto, $producto->modelo ?? '', $user, 1), 'variacion' => 1];

        if (!empty($titulos['titulo2'])) {
            $variaciones[] = ['titulo' => $titulos['titulo2'], 'sku' => $buildSku($producto->codigoProducto, $producto->modelo ?? '', $user, 2), 'variacion' => 2];
        }

        if (!empty($titulos['titulo3'])) {
            $variaciones[] = ['titulo' => $titulos['titulo3'], 'sku' => $buildSku($producto->codigoProducto, $producto->modelo ?? '', $user, 3), 'variacion' => 3];
        }

        return $variaciones;
    }

    public function buildColumnData(array $var, array $d, Producto $producto, array $context): array
    {
        $marcaProcesadorInput = ($d['caractMap']['Procesador'] ?? '') . ' ' . $var['titulo'] . ' ' . ($d['marca'] ?? '');
        $marcaProcesador = $this->resolveMarcaProcesador($marcaProcesadorInput);
        
        $procesadorEspecifico = $producto->modelo ?? '';
        
        // Limpiamos prefijos redundantes del modelo si el usuario lo guardó como "i9-14900K"
        $procesadorEspecifico = preg_replace('/^(i[3579]|ryzen\s?\d)[\-\s]?/i', '', $procesadorEspecifico);
        
        $compatibleInput = ($d['caractMap']['SO compatibles'] ?? '') . ' ' . ($d['caractMap']['Compatible con'] ?? '') . ' ' . ($d['caractMap']['Compatibilidad'] ?? '') . ' ' . $var['titulo'];
        $compatible = $this->resolveCompatibleCon($compatibleInput);

        return [
            'A' => $var['titulo'],
            'B' => $d['marca'],
            'C' => $producto->descripcionProducto,
            'D' => self::CATEGORIA,
            'E' => $var['sku'],
            'F' => substr($d['upcFbk'], 0, 13) . ($var['variacion'] ?? 1),
            'G' => '...',
            'H' => $context['stockTotal'],
            'I' => $d['precioNormalFmt'],
            'J' => $d['precioFmt'],
            'K' => $context['saleStart'],
            'L' => $context['saleEnd'],
            'M' => '2026',
            'N' => $d['caractMap']['Segmento'] ?? 'Computación',
            'O' => $marcaProcesador,
            'P' => $procesadorEspecifico,
            'Q' => $compatible,
            'R' => $d['caractMap']['Conectividad'] ?? '',
            'S' => $d['dim'],
            'T' => 'Nuevo',
            'U' => max(5, (float)$d['anchoCm']),
            'V' => max(5, (float)$d['largoCm']),
            'W' => max(5, (float)$d['altoCm']),
            'X' => $d['pesoCm'],
        ];
    }

    private function resolveMarcaProcesador(string $texto): string
    {
        $texto = str_replace(['®', '™'], '', $texto); // Limpiamos marcas registradas

        if (preg_match('/Core\s*i9/i', $texto)) return 'Intel Core i9';
        if (preg_match('/Core\s*i7/i', $texto)) return 'Intel Core i7';
        if (preg_match('/Core\s*i5/i', $texto)) return 'Intel Core i5';
        if (preg_match('/Core\s*i3/i', $texto)) return 'Intel Core i3';
        if (preg_match('/Core\s*Ultra\s*9/i', $texto)) return 'Intel Core Ultra 9';
        if (preg_match('/Core\s*Ultra\s*7/i', $texto)) return 'Intel Core Ultra 7';
        if (preg_match('/Core\s*Ultra\s*5/i', $texto)) return 'Intel Core Ultra 5';

        if (preg_match('/Ryzen\s*AI\s*9/i', $texto)) return 'AMD Ryzen AI9';
        if (preg_match('/Ryzen\s*AI\s*7/i', $texto)) return 'AMD Ryzen AI7';
        if (preg_match('/Ryzen\s*AI\s*5/i', $texto)) return 'AMD Ryzen AI5';
        if (preg_match('/Ryzen\s*9/i', $texto)) return 'AMD Ryzen 9';
        if (preg_match('/Ryzen\s*7/i', $texto)) return 'AMD Ryzen 7';
        if (preg_match('/Ryzen\s*5/i', $texto)) return 'Amd Ryzen 5';
        if (preg_match('/Ryzen\s*3/i', $texto)) return 'AMD Ryzen 3';

        $opciones = [
            'AMD Athlon', 'AMD EPYC', 'AMD Ryzen',
            'Apple Chip A19 pro', 'Apple Chip A19', 'Apple M1', 'Apple M2', 'Apple M3', 'Apple M4',
            'Samsung Exynos', 'Huawei Kirin',
            'Intel Celeron', 'Intel Pentium', 'Intel Core',
            'MediaTek Dimensity', 'Mediatek helio', 'Mediatek',
            'NVIDIA Tegra',
            'Qualcomm Snapdragon', 'Qualcomm'
        ];

        foreach ($opciones as $opcion) {
            if (stripos($texto, $opcion) !== false) {
                return $opcion;
            }
        }
        
        // Fallbacks comunes
        if (stripos($texto, 'Intel') !== false) return 'Intel Core';
        if (stripos($texto, 'AMD') !== false) return 'AMD Ryzen';

        return '';
    }

    private function resolveCompatibleCon(string $texto): string
    {
        $opciones = [
            'Adobe Creative Cloud', 'Adobe Photoshop', 'Alexa', 'Amazon Fire OS',
            'Android Auto', 'Android', 'Apple CarPlay', 'Apple HomeKit', 'Apple',
            'Brother', 'ChromeOS', 'Chrome', 'Discord', 'Epson',
            'Google Assistant', 'Google Home', 'GoPro', 'HarmonyOS', 'Huawei',
            'Insta360', 'iOS', 'iPad', 'iPhone', 'Kodak', 'Linux', 'Mac OS',
            'Microsoft Office', 'Microsoft Windows', 'Motorola', 'Nintendo',
            'PlayStation', 'Polaroid', 'Samsung', 'SmartThings', 'Steam',
            'Tizen', 'TTLock App', 'Tuya Smart', 'Universal', 'Windows',
            'Xbox', 'Xiaomi', 'Zoom', 'No aplica'
        ];

        foreach ($opciones as $opcion) {
            if (stripos($texto, $opcion) !== false) {
                return $opcion;
            }
        }

        return 'Universal';
    }

    private function ajustarLongitudFalabella(string $titulo, int $minimo = 30): string
    {
        if (strlen($titulo) < $minimo) {
            $titulo .= ' ' . trim(str_repeat('Modelo ', ceil(($minimo - strlen($titulo)) / 7)));
        }
        return trim($titulo);
    }
}
