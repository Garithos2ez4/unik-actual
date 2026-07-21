<?php

namespace App\Services\Falabella\Mappers;

use App\Models\Producto;
use App\Services\Falabella\Contracts\FalabellaCategoryMapper;

/**
 * Mapper para Impresoras (idCategoria = 6)
 *
 * Columnas del template impresoraTemplate-Express.xlsx:
 *   A  = Nombre del producto
 *   B  = Marca
 *   C  = Descripción
 *   D  = Categoría Falabella
 *   E  = SKU Seller
 *   F  = UPC (código de barras)
 *   G  = Variaciones (opcional)
 *   H  = Stock
 *   I  = Precio normal
 *   J  = Precio oferta
 *   K  = Fecha inicio oferta
 *   L  = Fecha fin oferta
 *   M  = Año de lanzamiento
 *   N  = Resolución de impresión (ej: "600 x 600 dpi")
 *   O  = Tipo de impresora (ej: "Inkjet", "Láser", "Multifuncional")
 *   P  = Número de puertos USB (opcional)
 *   Q  = Compatibilidad (opcional)
 *   R  = Conectividad (opcional, ej: "USB,Wi-Fi")
 *   S  = Dimensiones embalado "alto x ancho x largo"
 *   T  = Tipo de conexión (opcional, ej: "USB")
 *   U  = Estado ("Nuevo")
 *   V  = Ancho embalado (cm)
 *   W  = Largo embalado (cm)
 *   X  = Alto embalado (cm)
 *   Y  = Peso embalado (kg)
 */
class ImpresoraMapper implements FalabellaCategoryMapper
{
    const CATEGORIA = '417 - Electrónica / Computación / Impresoras y Escáneres / Impresoras';

    public function getTemplateFile(): string
    {
        return 'falabella/impresoraTemplate-Express.xlsx';
    }

    public function getFilePrefix(): string
    {
        return 'ImpresoraTemplate-Express_';
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

        // Traemos la descripción completa para cazar palabras clave
        $desc     = $producto->descripcionProducto ?? '';

        // 1. Mapeo de características (¡Actualizado con tus nuevos campos del desplegable!)
        $conectividad = $caract['Conectividad'] ?? '';
        $tecnologia   = $caract['Tecnología de imp.'] ?? $caract['Tipo de impresora'] ?? '';
        $funciones    = $caract['Funciones'] ?? $caract['Función'] ?? '';
        $velocidad    = $caract['Velocidad de imp.'] ?? '';
        // Agregamos "Tamaño de impresión" como alternativa a "Tamaños de hoja"
        $tamanos      = $caract['Tamaño de impresión'] ?? $caract['Tamaños de hoja'] ?? '';
        // Agregamos el nuevo campo "Tipo de tinta"
        $tipoTinta    = $caract['Tipo de tinta'] ?? '';

        // Metemos TODOS los datos a la "piscina de texto" gigante para buscar coincidencias
        $textoCompleto = $base . ' ' . $desc . ' ' . $tecnologia . ' ' . $funciones . ' ' . $tamanos . ' ' . $tipoTinta . ' ' . $velocidad;

        // 2. Extracción de palabras clave SEO de Alto Valor

        // Conectividad (Buscamos Wi-Fi y LAN)
        $conStr = [];
        if (preg_match('/(wi-fi|wireless|inalámbrica|inalambrica)/i', $textoCompleto)) $conStr[] = 'Wi-Fi';
        if (preg_match('/(lan|ethernet|rj45)/i', $textoCompleto)) $conStr[] = 'LAN';
        $conectividadFinal = implode(' ', $conStr);

        // ¿Es Multifuncional?
        $esMulti = false;
        if (preg_match('/(escáner|escaner|copia|multifunci)/i', $textoCompleto)) {
            $esMulti = true;
        }

        // Extracción de Tecnología Especial (Prioridad Alta)
        $tipoEspecial = '';
        if (preg_match('/(sublimaci[oó]n)/i', $textoCompleto)) {
            $tipoEspecial = 'Sublimación';
        } elseif (preg_match('/(sistema continuo|ecotank|megatank|smart tank)/i', $textoCompleto, $m)) {
            $tipoEspecial = ucwords(strtolower($m[1])); // Ej: "Ecotank" o "Sistema Continuo"
        }

        // Si no hay tecnología especial, buscamos las básicas
        $tipoStr = '';
        if ($tipoEspecial) {
            $tipoStr = $tipoEspecial;
        } elseif (preg_match('/(laser|láser)/i', $textoCompleto)) {
            $tipoStr = 'Láser';
        } elseif (preg_match('/(tinta|inkjet|precisioncore)/i', $textoCompleto)) {
            $tipoStr = 'Tinta';
        }

        // Extracción del Formato de Hoja (Buscamos A4 o A3 explícitamente en cualquier lado)
        $formatoStr = '';
        if (preg_match('/\b(A3\+|A3|A4)\b/i', $textoCompleto, $m)) {
            $formatoStr = strtoupper($m[1]);
        }

        // Extracción de Dúplex
        $duplexStr = preg_match('/(dúplex|duplex)/i', $textoCompleto) ? 'Dúplex' : '';

        // 3. Construcción de Títulos SEO

        $titulo1 = $base;

        // Título 2: Máxima descripción (Ej: Impresora Multifuncional Epson F170 Sublimación A4 Wi-Fi LAN)
        $t2_parts = array_filter([
            $esMulti ? 'Impresora Multifuncional' : 'Impresora',
            $marca,
            $modelo,
            $tipoStr,
            $formatoStr,
            $conectividadFinal
        ]);
        $titulo2 = implode(' ', $t2_parts);

        // Título 3: Intención de compra directa (Ej: Impresora Epson F170 A4 Sublimación Wi-Fi)
        $t3_parts = array_filter([
            'Impresora',
            $marca,
            $modelo,
            $formatoStr,
            $tipoStr,
            $duplexStr,
            str_contains($conectividadFinal, 'Wi-Fi') ? 'Wi-Fi' : '' // Mantenemos solo Wi-Fi para no saturar
        ]);
        $titulo3 = implode(' ', $t3_parts);

        // Fallback si no hay modelo registrado
        if (empty($modelo)) {
            $baseCorta = implode(' ', array_slice(explode(' ', $base), 0, 4));
            $titulo2 = "$baseCorta $tipoStr $formatoStr $conectividadFinal";
            $titulo3 = "$baseCorta $formatoStr $tipoStr $duplexStr";
        }

        // Envolvemos todo en nuestro filtro de longitud (mínimo 20 caracteres para impresoras)
        return [
            'titulo1' => $this->ajustarLongitudFalabella($titulo1, 20),
            'titulo2' => $this->ajustarLongitudFalabella($titulo2, 20),
            'titulo3' => $this->ajustarLongitudFalabella($titulo3, 20),
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
    /**
     * Asegura que el título cumpla estrictamente con la regla de Falabella.
     * Para Laptops: Mínimo 30, Máximo 100 caracteres.
     */
    private function ajustarLongitudFalabella(string $titulo, int $minimo = 30): string
    {
        // 1. Limpiar espacios dobles
        $titulo = trim(preg_replace('/\s+/', ' ', $titulo));

        // 2. Si supera los 100 caracteres, recortar sin romper palabras
        if (strlen($titulo) > 100) {
            $titulo = substr($titulo, 0, 100);
            $ultimoEspacio = strrpos($titulo, ' ');
            if ($ultimoEspacio !== false) {
                $titulo = substr($titulo, 0, $ultimoEspacio);
            }
        }

        // 3. Si tiene menos del mínimo exigido (ej. 30), agregar relleno comercial
        if (strlen($titulo) < $minimo) {
            $titulo .= ' Original Nuevo Garantizado';

            // Por si acaso el relleno nos hace pasar de 100
            if (strlen($titulo) > 100) {
                $titulo = substr($titulo, 0, 100);
            }
        }

        return trim($titulo);
    }
    public function buildColumnData(array $var, array $d, Producto $producto, array $context): array
    {
        $conectividad = implode(',', array_filter([
            $d['caractMap']['Conectividad'] ?? '',
            $d['caractMap']['WiFi'] ?? '',
            $d['caractMap']['Bluetooth'] ?? '',
        ]));

        $tipoImpresora = $this->resolveTipoImpresora($var['titulo'], $d['caractMap']);

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
            'N' => $d['caractMap']['Resolución de impresión'] ?? $d['caractMap']['Resolucion'] ?? '',
            'O' => $tipoImpresora,
            'R' => $conectividad ?: ($d['caractMap']['Tipo de conexión'] ?? ''),
            'S' => $d['dim'],
            'T' => $d['caractMap']['Tipo de conexión'] ?? 'USB',
            'U' => 'Nuevo',
            'V' => max(5, (float)$d['anchoCm']),
            'W' => max(5, (float)$d['largoCm']),
            'X' => max(5, (float)$d['altoCm']),
            'Y' => $d['pesoCm'],
        ];
    }

    private function resolveTipoImpresora(string $titulo, array $caractMap): string
    {
        $tituloLower = strtolower($titulo);
        $tipo = strtolower($caractMap['Tipo de impresora'] ?? $caractMap['Tipo'] ?? '');

        if (
            stripos($tituloLower, 'multifuncion') !== false || stripos($tituloLower, 'multifunción') !== false
            || stripos($tipo, 'multifuncion') !== false
        ) {
            return 'Multifuncional';
        }
        if (
            stripos($tituloLower, 'laser') !== false || stripos($tituloLower, 'láser') !== false
            || stripos($tipo, 'laser') !== false
        ) {
            return 'Láser';
        }
        if (
            stripos($tituloLower, 'inkjet') !== false || stripos($tituloLower, 'inyección') !== false
            || stripos($tipo, 'inkjet') !== false
        ) {
            return 'Inkjet';
        }
        if (stripos($tituloLower, 'termica') !== false || stripos($tituloLower, 'térmica') !== false) {
            return 'Impresora Térmica';
        }
        return 'Inkjet'; // fallback más común
    }
}
