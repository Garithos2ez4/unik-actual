<?php

namespace App\Services\Falabella\Contracts;

use App\Models\Catalogo\Producto;

/**
 * Contrato para los mappers de categoría Falabella Express.
 * Cada categoría implementa esta interfaz para definir:
 * - El archivo Excel base a usar
 * - Las columnas que se deben llenar y con qué datos
 * - El prefijo del archivo descargado
 * - Los títulos sugeridos automáticamente por el sistema
 */
interface FalabellaCategoryMapper
{
    /** Ruta relativa al storage_path() del archivo .xlsx base */
    public function getTemplateFile(): string;

    /** Prefijo del nombre de archivo al descargar, ej: "LaptopTemplate-Express_" */
    public function getFilePrefix(): string;

    /** Fila donde empieza la escritura de datos (generalmente 4 o 5) */
    public function getStartRow(): int;

    /**
     * Genera sugerencias de títulos basadas en las specs del producto.
     * El sistema las propone; el usuario puede editarlas antes de descargar.
     *
     * @param Producto $producto
     * @param array    $d   Resultado de calcularDatos()
     * @return array  ['titulo1' => string, 'titulo2' => string, 'titulo3' => string]
     */
    public function sugerirTitulos(Producto $producto, array $d): array;

    /**
     * Devuelve las variaciones del producto con títulos personalizados.
     * Si $titulos está vacío, se usan los títulos sugeridos por defecto.
     *
     * @param Producto    $producto
     * @param callable    $buildSku  fn(string $codigo, string $modelo, ?object $user, int $variacion): string
     * @param object|null $user
     * @param array       $titulos   ['titulo1' => ..., 'titulo2' => ..., 'titulo3' => ...]  (opcional)
     */
    public function getVariaciones(Producto $producto, callable $buildSku, ?object $user, array $titulos = []): array;

    /**
     * Construye el array col => valor para una variación dada.
     *
     * @param array    $var      ['titulo' => ..., 'sku' => ...]
     * @param array    $d        Resultado de calcularDatos()
     * @param Producto $producto
     * @param array    $context  ['stockTotal', 'saleStart', 'saleEnd']
     */
    public function buildColumnData(array $var, array $d, Producto $producto, array $context): array;
}
