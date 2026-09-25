<?php

namespace App\Services\MercadoLibre\Mappers;

use App\Services\MercadoLibre\Contracts\MercadoLibreCategoryMapper;
use App\Models\Catalogo\Producto;

class MercadoLibreLaptopMapper implements MercadoLibreCategoryMapper
{
    public function getTemplateFile(): string
    {
        return 'ml/ml_template_laptops.xlsx';
    }

    public function getSheetName(): string
    {
        return 'Laptops';
    }

    public function getFilePrefix(): string
    {
        return 'ML_Laptops_';
    }

    public function getStartRow(): int
    {
        return 10;
    }

    public function buildRowsData(Producto $producto, array $d, array $context): array
    {
        // $d es el resultado de calcularDatos (precios, titulo referencial, etc.)
        // $context contiene 'stockTotal', 'sku', etc.

        // Características extraídas
        $specs = $d['specs'] ?? [];
        $marca = $producto->MarcaProducto->nombreMarca ?? '';
        $modelo = $producto->modelo ?? '';

        $titulo = $d['tituloDefecto'] ?? ($producto->nombreProducto . ' ' . $modelo);
        // Limitar título a 60 caracteres
        $titulo = mb_substr($titulo, 0, 60);
        
        $upc = !empty($producto->UPC) ? $producto->UPC : 'NO APLICA';
        
        // Resolver color
        $rawColor = $specs['Color'] ?? $specs['color'] ?? 'Negro';
        $mlColor = $this->resolveColorML($rawColor);
        
        $procesador = $specs['Procesador'] ?? $specs['procesador'] ?? '';
        $marcaProcesador = '';
        $lineaProcesador = $procesador;
        
        if (preg_match('/i[3579]/i', $procesador, $matches)) {
            $marcaProcesador = 'Intel';
            $lineaProcesador = 'Core ' . strtolower($matches[0]);
        } elseif (preg_match('/r[3579]/i', $procesador, $matches)) {
            $marcaProcesador = 'AMD';
            $lineaProcesador = 'Ryzen ' . substr($matches[0], 1);
        } elseif (stripos($procesador, 'Core') !== false || stripos($procesador, 'Intel') !== false) {
            $marcaProcesador = 'Intel';
        } elseif (stripos($procesador, 'Ryzen') !== false || stripos($procesador, 'AMD') !== false) {
            $marcaProcesador = 'AMD';
        } elseif (stripos($procesador, 'Apple') !== false || preg_match('/M[123]/i', $procesador)) {
            $marcaProcesador = 'Apple';
        }
        
        $pantallaRaw = $specs['Pulgadas'] ?? $specs['Tamaño de pantalla'] ?? '';
        $pantallaNum = preg_replace('/[^0-9.]/', '', $pantallaRaw);
        
        // Obtener fotos
        $fotosUrls = $this->getFotosUrls($producto);

        // Retornamos 1 fila por defecto para MercadoLibre Laptops
        return [
            [
                'B' => $titulo, // Título
                'D' => 'Nuevo', // Condición
                'E' => $upc, // Código universal de producto
                'F' => $mlColor, // Varía por: Nombre comercial del color
                'G' => $fotosUrls, // Fotos
                'H' => $context['sku'] ?? $producto->codigoProducto, // SKU
                'I' => $context['stockTotal'] ?? 0, // Stock
                'J' => $d['precioMax'] ?? 0, // Precio
                'K' => 'S/', // Moneda
                'U' => $marca, // Marca
                'V' => $modelo, // Modelo
                'W' => $marcaProcesador, // Marca del procesador
                'X' => $lineaProcesador, // Línea del procesador
                'Y' => 'A4', // Modelo del procesador (Por defecto porque la plantilla de ML a veces solo da A4 o 8250U)
                'Z' => $pantallaNum, // Tamaño de la pantalla
            ]
        ];
    }
    
    private function resolveColorML(string $rawColor): string
    {
        $validColors = [
            'Coral claro', 'Coral', 'Naranja claro', 'Naranja oscuro', 'Caqui',
            'Verde lima', 'Verde musgo', 'Chocolate', 'Suela', 'Marrón claro',
            'Marrón oscuro', 'Dorado', 'Rojo', 'Gris', 'Rosa', 'Turquesa',
            'Terracota', 'Azul marino', 'Índigo', 'Bordó', 'Rosa chicle',
            'Naranja', 'Beige', 'Nude', 'Marrón', 'Amarillo', 'Crema', 'Ocre',
            'Dorado oscuro', 'Verde', 'Verde claro', 'Verde oscuro', 'Celeste',
            'Agua', 'Azul petróleo', 'Azul', 'Azul claro', 'Azul acero',
            'Azul oscuro', 'Violeta', 'Lavanda', 'Lila', 'Fucsia', 'Rosa claro',
            'Rosa pálido', 'Violeta oscuro', 'Negro', 'Gris oscuro', 'Plateado',
            'Blanco', 'Cian'
        ];

        $lowerRaw = mb_strtolower(trim($rawColor));
        
        // Mapeos comunes si viene algo distinto
        if (str_contains($lowerRaw, 'black')) return 'Negro';
        if (str_contains($lowerRaw, 'silver')) return 'Plateado';
        if (str_contains($lowerRaw, 'grey') || str_contains($lowerRaw, 'gray')) return 'Gris';
        if (str_contains($lowerRaw, 'white')) return 'Blanco';
        if (str_contains($lowerRaw, 'blue')) return 'Azul';
        if (str_contains($lowerRaw, 'red')) return 'Rojo';

        foreach ($validColors as $vc) {
            if (mb_strtolower($vc) === $lowerRaw) {
                return $vc;
            }
        }
        
        // Si contiene alguna de las palabras clave, intentamos un match
        foreach ($validColors as $vc) {
            if (str_contains($lowerRaw, mb_strtolower($vc))) {
                return $vc;
            }
        }

        // Si no hay match y no sabemos qué es, devolvemos Negro por defecto para que no de error
        return 'Negro';
    }
    
    private function getFotosUrls(Producto $producto): string
    {
        $fotos = [];
        if (!empty($producto->imagenProducto1)) {
            $fotos[] = asset('storage/' . $producto->imagenProducto1);
        }
        if (!empty($producto->imagenProducto2)) {
            $fotos[] = asset('storage/' . $producto->imagenProducto2);
        }
        if (!empty($producto->imagenProducto3)) {
            $fotos[] = asset('storage/' . $producto->imagenProducto3);
        }
        if (!empty($producto->imagenProducto4)) {
            $fotos[] = asset('storage/' . $producto->imagenProducto4);
        }

        return implode(', ', $fotos);
    }
}
