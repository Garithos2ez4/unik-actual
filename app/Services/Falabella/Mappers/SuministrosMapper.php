<?php

namespace App\Services\Falabella\Mappers;

use App\Models\Catalogo\Producto;
use App\Services\Falabella\Contracts\FalabellaCategoryMapper;

/**
 * Mapper para Suministros (Tintas, Resets) (idCategoria = 155, 156, 157, 158, 159, 160, 169)
 *
 * Columnas del template SuministrosTemplate-Express_2026-07.xlsx:
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
 *   N  = CompatibleCon
 *   O  = TipoDeConsumible
 *   P  = Dimensiones
 *   Q  = Rendimiento
 *   R  = Condición del Producto
 *   S  = Ancho del paquete
 *   T  = Largo del paquete
 *   U  = Alto del paquete
 *   V  = Peso del paquete
 */
class SuministrosMapper implements FalabellaCategoryMapper
{
    const CATEGORIA = '79 - Electrónica / Computación / Periféricos de computador / Tintas|consumibles para impresora';

    public function getTemplateFile(): string
    {
        return 'falabella/SuministrosTemplate-Express_2026-07.xlsx';
    }

    public function getFilePrefix(): string
    {
        return 'SuministrosTemplate-Express_';
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

        // Compatibilidad y Tipo de consumible
        $compatible = $caract['Compatible con'] ?? $caract['Compatibilidad'] ?? '';
        $tipo = $this->resolveTipoConsumible($producto->nombreProducto, $caract['Tipo'] ?? '');
        $rendimiento = $caract['Rendimiento'] ?? '';

        // Título 1: Original
        $titulo1 = $base;
        if (!preg_match('/' . preg_quote($marca, '/') . '/i', $titulo1) && !empty($marca)) {
            $titulo1 = $marca . ' ' . $titulo1;
        }

        // Título 2: Con Compatibilidad
        $titulo2 = $titulo1;
        if (!empty($compatible) && stripos($titulo2, 'Para') === false) {
            $titulo2 .= ' Para ' . $compatible;
        }

        // Título 3: Con Rendimiento
        $titulo3 = $titulo1;
        if (!empty($rendimiento) && stripos($titulo3, $rendimiento) === false) {
            $titulo3 .= ' ' . $rendimiento;
        }

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
        $compatibleInput = ($d['caractMap']['Compatible con'] ?? '') . ' ' . ($d['caractMap']['Compatibilidad'] ?? '') . ' ' . $var['titulo'];
        $compatible = $this->resolveCompatibleCon($compatibleInput);
        $tipo = $this->resolveTipoConsumible($var['titulo'], $d['caractMap']['Tipo'] ?? '');
        $rendimiento = $d['caractMap']['Rendimiento'] ?? '';

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
            'N' => $compatible,
            'O' => $tipo,
            'P' => $d['dim'],
            'Q' => $rendimiento,
            'R' => 'Nuevo',
            'S' => max(5, (float)$d['anchoCm']),
            'T' => max(5, (float)$d['largoCm']),
            'U' => max(5, (float)$d['altoCm']),
            'V' => $d['pesoCm'],
        ];
    }

    private function resolveTipoConsumible(string $titulo, string $tipoBase): string
    {
        $texto = strtolower($titulo . ' ' . $tipoBase);
        if (strpos($texto, 'reset') !== false) {
            return 'Resin'; // Falabella no tiene "Reset", el usuario prefiere "Resin"
        }
        if (strpos($texto, 'toner') !== false || strpos($texto, 'tóner') !== false) {
            return 'Tóner';
        }
        if (strpos($texto, 'cartucho') !== false) {
            return 'Cartucho';
        }
        if (strpos($texto, 'cinta') !== false) {
            return 'Cinta para Impresora';
        }
        if (strpos($texto, 'tambor') !== false) {
            return 'Tambor de tinta';
        }
        return 'Tinta';
    }

    private function ajustarLongitudFalabella(string $titulo, int $minimo = 30): string
    {
        if (strlen($titulo) < $minimo) {
            $titulo .= ' ' . trim(str_repeat('Modelo ', ceil(($minimo - strlen($titulo)) / 7)));
        }
        return trim($titulo);
    }

    private function resolveCompatibleCon(string $texto): string
    {
        $opciones = [
            'Adobe Creative Cloud',
            'Adobe Photoshop',
            'Alexa',
            'Amazon Fire OS',
            'Android Auto',
            'Android',
            'Apple CarPlay',
            'Apple HomeKit',
            'Apple',
            'Brother',
            'ChromeOS',
            'Chrome',
            'Discord',
            'Epson',
            'Google Assistant',
            'Google Home',
            'GoPro',
            'HarmonyOS',
            'Huawei',
            'Insta360',
            'iOS',
            'iPad',
            'iPhone',
            'Kodak',
            'Linux',
            'Mac OS',
            'Microsoft Office',
            'Microsoft Windows',
            'Motorola',
            'Nintendo',
            'PlayStation',
            'Polaroid',
            'Samsung',
            'SmartThings',
            'Steam',
            'Tizen',
            'TTLock App',
            'Tuya Smart',
            'Universal',
            'Windows',
            'Xbox',
            'Xiaomi',
            'Zoom',
            'No aplica'
        ];

        foreach ($opciones as $opcion) {
            if (stripos($texto, $opcion) !== false) {
                return $opcion;
            }
        }

        return 'No aplica';
    }
}
