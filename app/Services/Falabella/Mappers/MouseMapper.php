<?php

namespace App\Services\Falabella\Mappers;

use App\Models\Catalogo\Producto;
use App\Services\Falabella\Contracts\FalabellaCategoryMapper;

/**
 * Mapper para Mouses (idGrupoProducto = 25)
 *
 * Columnas del template MouseTemplate-Express_2026-08-05.xlsx:
 *   A  = Nombre
 *   B  = Marca
 *   C  = Descripción
 *   D  = Categoría primaria
 *   E  = ColorBasicoVariant
 *   F  = ColorVariant
 *   G  = SKU del vendedor
 *   H  = Código de barras
 *   I  = SKU Padre
 *   J  = QuantityFalabella (Stock)
 *   K  = PriceFalabella (Precio Normal)
 *   L  = SalePriceFalabella (Precio Venta)
 *   M  = SaleStartDateFalabella
 *   N  = SaleEndDateFalabella
 *   O  = AnoFabricacion
 *   P  = ConectividadConexion
 *   Q  = Segmento
 *   R  = ResolucionDpi
 *   S  = TipoDeSensor
 *   T  = Autonomia
 *   U  = Dimensiones
 *   V  = Condición del Producto
 *   W  = Ancho del paquete
 *   X  = Largo del paquete
 *   Y  = Alto del paquete
 *   Z  = Peso del paquete
 */
class MouseMapper implements FalabellaCategoryMapper
{
    const CATEGORIA = '2653 - Electrónica / Computación / Dispositivos de entrada / Mouse|dispositivos de punteros para computadores';

    public function getTemplateFile(): string
    {
        return 'falabella/MouseTemplate-Express_2026-08-05.xlsx';
    }

    public function getFilePrefix(): string
    {
        return 'MouseTemplate-Express_';
    }

    public function getStartRow(): int
    {
        return 5;
    }

    public function sugerirTitulos(Producto $producto, array $d): array
    {
        $base     = $producto->nombreProducto;
        $desc     = $producto->descripcionProducto ?? '';
        $caract   = $d['caractMap'];

        $color      = $caract['Color'] ?? '';
        $dpi        = $caract['DPI'] ?? $caract['Resolución'] ?? '';
        $sensor     = $caract['Sensor'] ?? '';
        $conexion   = $caract['Conexión'] ?? $caract['Cableado'] ?? '';
        $segmento   = $caract['Segmento'] ?? '';

        $textoCompleto = "$base $desc $conexion $sensor $segmento $dpi";

        $conStr = '';
        if (preg_match('/(inal[áa]mbrico|wireless)/i', $textoCompleto)) {
            $conStr = 'Inalámbrico';
        } elseif (preg_match('/(bluetooth|bt)/i', $textoCompleto)) {
            $conStr = 'Bluetooth';
        } elseif (preg_match('/(usb|cableado|cable)/i', $textoCompleto)) {
            $conStr = 'USB';
        }

        $segStr = '';
        if (preg_match('/(gamer|gaming)/i', $textoCompleto)) {
            $segStr = 'Gamer';
        }

        $t1 = $this->ajustarLongitudFalabella("$base $color $conStr $segStr");
        $t2 = $this->ajustarLongitudFalabella("Mouse $base $dpi $color $conStr $segStr");

        return [
            'titulo1' => $t1,
            'titulo2' => $t2 !== $t1 ? $t2 : null,
        ];
    }

    private function ajustarLongitudFalabella(string $titulo, int $minimo = 20): string
    {
        $titulo = trim(preg_replace('/\s+/', ' ', $titulo));

        if (strlen($titulo) > 100) {
            $titulo = substr($titulo, 0, 100);
            $ultimoEspacio = strrpos($titulo, ' ');
            if ($ultimoEspacio !== false) {
                $titulo = substr($titulo, 0, $ultimoEspacio);
            }
        }

        if (strlen($titulo) < $minimo) {
            $titulo .= ' Original Nuevo Garantizado';
            if (strlen($titulo) > 100) {
                $titulo = substr($titulo, 0, 100);
            }
        }

        return trim($titulo);
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
        return [
            'A' => $var['titulo'],
            'B' => $d['marca'],
            'C' => $producto->descripcionProducto,
            'D' => self::CATEGORIA,
            'E' => $this->resolveColor($d['caractMap']), // ColorBasicoVariant
            'F' => $this->resolveColor($d['caractMap']), // ColorVariant
            'G' => $var['sku'],
            'H' => substr($d['upcFbk'], 0, 13) . ($var['variacion'] ?? 1),
            'I' => '', // SKU Padre
            'J' => $context['stockTotal'],
            'K' => $d['precioNormalFmt'],
            'L' => $d['precioFmt'],
            'M' => $context['saleStart'],
            'N' => $context['saleEnd'],
            'O' => date('Y'), // AnoFabricacion
            'P' => $this->resolveConectividad($d['caractMap']),
            'Q' => $this->resolveSegmento($d['caractMap']),
            'R' => $d['caractMap']['DPI'] ?? $d['caractMap']['Resolución'] ?? '',
            'S' => $this->resolveSensor($d['caractMap']),
            'T' => $d['caractMap']['Autonomia'] ?? '',
            'U' => $d['dim'],
            'V' => 'Nuevo',
            'W' => max(5, (float)$d['anchoCm']),
            'X' => max(5, (float)$d['largoCm']),
            'Y' => max(5, (float)$d['altoCm']),
            'Z' => round(max(0.03, min(3.0, (float)($d['pesoCm'] ?: 0.2))), 2),
        ];
    }

    private function resolveConectividad(array $caract): string
    {
        $texto = strtolower(implode(' ', [
            $caract['Conexión'] ?? '',
            $caract['Cableado'] ?? '',
            $caract['Conectividad'] ?? '',
            $caract['Tipo de conexión'] ?? ''
        ]));

        if (preg_match('/(inal[áa]mbrico|wireless)/i', $texto)) {
            return 'Inalámbrico';
        }
        if (preg_match('/(bluetooth|bt)/i', $texto)) {
            return 'Bluetooth';
        }
        if (preg_match('/(usb|cableado|cable)/i', $texto)) {
            return 'USB';
        }

        return 'USB';
    }

    private function resolveSegmento(array $caract): string
    {
        $texto = strtolower(implode(' ', [
            $caract['Segmento'] ?? '',
            $caract['Tipo'] ?? ''
        ]));

        if (preg_match('/(gamer|gaming)/i', $texto)) {
            return 'Gamer';
        }
        if (preg_match('/(creador|contenido)/i', $texto)) {
            return 'Creador de Contenido';
        }
        if (preg_match('/(estudiant|educaci[oó]n)/i', $texto)) {
            return 'Estudiante';
        }
        if (preg_match('/(productividad|trabajo|oficina|empresarial)/i', $texto)) {
            return 'Productividad';
        }

        $raw = trim($caract['Segmento'] ?? '');
        $validos = ['Creador de Contenido', 'Estudiante', 'Gamer', 'Productividad'];
        foreach ($validos as $v) {
            if (strcasecmp($raw, $v) === 0) {
                return $v;
            }
        }

        return 'Productividad';
    }

    private function resolveSensor(array $caract): string
    {
        $texto = strtolower(implode(' ', [
            $caract['Sensor'] ?? '',
            $caract['Tecnología'] ?? ''
        ]));

        if (preg_match('/(bluetrack)/i', $texto)) {
            return 'BlueTrack';
        }
        if (preg_match('/(dual)/i', $texto)) {
            return 'Dual';
        }
        if (preg_match('/(infrarrojo|infrared)/i', $texto)) {
            return 'Infrarrojo';
        }
        if (preg_match('/([óo]ptico|optical)/i', $texto)) {
            return 'Óptico';
        }
        if (preg_match('/(l[áa]ser|laser)/i', $texto)) {
            return 'Láser';
        }

        return 'Óptico';
    }

    private function resolveColor(array $caract): string
    {
        $colorRaw = $caract['Color'] ?? '';
        $color = strtolower($colorRaw);
        
        $coloresValidos = [
            'amarillo' => 'Amarillo',
            'azul' => 'Azul',
            'beige' => 'Beige',
            'blanco' => 'Blanco',
            'burdeo' => 'Burdeo',
            'café' => 'Café',
            'cafe' => 'Café',
            'gris' => 'Gris',
            'plateado' => 'Plateado',
            'plata' => 'Plateado',
            'silver' => 'Plateado',
            'morado' => 'Morado',
            'naranjo' => 'Naranjo',
            'naranja' => 'Naranjo',
            'dorado' => 'Dorado',
            'gold' => 'Dorado',
            'negro' => 'Negro',
            'black' => 'Negro',
            'rojo' => 'Rojo',
            'rosado' => 'Rosado',
            'rosa' => 'Rosado',
            'verde' => 'Verde'
        ];

        foreach ($coloresValidos as $key => $valido) {
            if (str_contains($color, $key)) {
                return $valido;
            }
        }

        return $colorRaw ? ucfirst($colorRaw) : '';
    }
}
