<?php

namespace App\Services\Falabella\Mappers;

use App\Models\Producto;
use App\Services\Falabella\Contracts\FalabellaCategoryMapper;

/**
 * Mapper para Teclados (idGrupoProducto = 24)
 *
 * Columnas del template TecladoCreationTemplate-Express.xlsx:
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
 *   N  = ConectividadConexion
 *   O  = Segmento
 *   P  = Autonomia
 *   Q  = Color
 *   R  = Dimensiones
 *   S  = Condición del Producto
 *   T  = Ancho del paquete
 *   U  = Largo del paquete
 *   V  = Alto del paquete
 *   W  = Peso del paquete
 */
class TecladoMapper implements FalabellaCategoryMapper
{
    const CATEGORIA = '1442 - Electrónica / Computación / Dispositivos de entrada / Teclados para computadores';

    public function getTemplateFile(): string
    {
        return 'falabella/TecladoCreationTemplate-Express.xlsx';
    }

    public function getFilePrefix(): string
    {
        return 'TecladoTemplate-Express_';
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
        $desc     = $producto->descripcionProducto ?? '';

        // 1. Mapeo de características clave para Teclados
        $color      = $caract['Color'] ?? '';
        $idioma     = $caract['Idioma'] ?? '';
        $tipo       = $caract['Tipo de teclado'] ?? '';
        $conexion   = $caract['Conexión'] ?? $caract['Cableado'] ?? '';
        $segmento   = $caract['Segmento'] ?? '';
        $iluminacion = $caract['Iluminación'] ?? $caract['Retroiluminación'] ?? '';

        // Piscina de texto para rastreo
        $textoCompleto = "$base $desc $conexion $tipo $segmento $idioma $iluminacion";

        // 2. Limpieza y extracción de palabras clave SEO

        // Extraer tipo de Conexión
        $conStr = '';
        if (preg_match('/(inal[áa]mbrico|wireless)/i', $textoCompleto)) {
            $conStr = 'Inalámbrico';
        } elseif (preg_match('/(bluetooth|bt)/i', $textoCompleto)) {
            $conStr = 'Bluetooth';
        } elseif (preg_match('/(usb|cableado|cable)/i', $textoCompleto)) {
            $conStr = 'USB';
        }

        // Extraer Idioma (Crucial para teclados)
        $idiomaStr = '';
        if (preg_match('/(espa[ñn]ol|latino)/i', $textoCompleto)) {
            $idiomaStr = 'Español';
        } elseif (preg_match('/(ingl[ée]s|english)/i', $textoCompleto)) {
            $idiomaStr = 'Inglés';
        }

        // Extraer Tecnología de teclas
        $tipoStr = '';
        if (preg_match('/(mec[áa]nico)/i', $textoCompleto)) {
            $tipoStr = 'Mecánico';
        } elseif (preg_match('/(membrana)/i', $textoCompleto)) {
            $tipoStr = 'Membrana';
        }

        // Extraer Enfoque Gamer
        $gamerStr = preg_match('/(gamer|gaming|rgb|led)/i', $textoCompleto) ? 'Gamer' : '';

        // 3. Construcción de Títulos SEO

        $titulo1 = $base;

        // Título 2: Enfoque Funcional / Completo (Ej: Teclado Inalámbrico Teros TE-4070S Membrana Español Blanco)
        $t2_parts = array_filter([
            'Teclado',
            $gamerStr,
            $conStr,
            $marca,
            $modelo,
            $tipoStr,
            $idiomaStr,
            $color
        ]);
        $titulo2 = implode(' ', $t2_parts);

        // Título 3: Enfoque Directo / Práctico (Ej: Teclado Teros TE-4070S Inalámbrico Español Blanco)
        $t3_parts = array_filter([
            'Teclado',
            $marca,
            $modelo,
            $conStr,
            $idiomaStr,
            $color
        ]);
        $titulo3 = implode(' ', $t3_parts);

        // Fallback si no hay modelo registrado
        if (empty($modelo)) {
            $baseCorta = implode(' ', array_slice(explode(' ', $base), 0, 4));
            $titulo2 = trim("$baseCorta $tipoStr $conStr $idiomaStr");
            $titulo3 = trim("$baseCorta $conStr $idiomaStr $color");
        }

        // Envolvemos todo en el filtro de longitud (Mínimo 20 caracteres)
        return [
            'titulo1' => $this->ajustarLongitudFalabella($titulo1, 20),
            'titulo2' => $this->ajustarLongitudFalabella($titulo2, 20),
            'titulo3' => $this->ajustarLongitudFalabella($titulo3, 20),
        ];
    }

    /**
     * Asegura que el título cumpla estrictamente con la regla de Falabella.
     */
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
            'E' => $var['sku'],
            'F' => substr($d['upcFbk'], 0, 13) . ($var['variacion'] ?? 1),
            'G' => '...',
            'H' => $context['stockTotal'],
            'I' => $d['precioNormalFmt'],
            'J' => $d['precioFmt'],
            'K' => $context['saleStart'],
            'L' => $context['saleEnd'],
            'M' => '2026',
            'N' => $this->resolveConectividad($d['caractMap']),
            'O' => $d['caractMap']['Segmento'] ?? 'Computación',
            'P' => $d['caractMap']['Autonomia'] ?? '',
            'Q' => $d['caractMap']['Color'] ?? '',
            'R' => $d['dim'],
            'S' => 'Nuevo',
            'T' => max(5, (float)$d['anchoCm']),
            'U' => max(5, (float)$d['largoCm']),
            'V' => max(5, (float)$d['altoCm']),
            'W' => $d['pesoCm'],
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
}
