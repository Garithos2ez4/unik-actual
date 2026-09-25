<?php

namespace App\Services\MercadoLibre\Contracts;

use App\Models\Catalogo\Producto;

/**
 * Contrato para los mappers de categoría MercadoLibre.
 * Cada categoría implementa esta interfaz para definir:
 * - El archivo Excel base a usar
 * - La hoja de Excel a modificar
 * - La fila de inicio
 * - Las columnas que se deben llenar y con qué datos
 */
interface MercadoLibreCategoryMapper
{
    /** Ruta relativa al storage_path() del archivo .xlsx base */
    public function getTemplateFile(): string;

    /** Nombre de la hoja de Excel a modificar */
    public function getSheetName(): string;

    /** Prefijo del nombre de archivo al descargar */
    public function getFilePrefix(): string;

    /** Fila donde empieza la escritura de datos */
    public function getStartRow(): int;

    /**
     * Construye el array col => valor para una variación o producto dado.
     *
     * @param Producto $producto
     * @param array    $d        Resultado de calcularDatos()
     * @param array    $context  ['stockTotal', 'sku']
     * @return array[] Array de filas (cada fila es un array col => valor)
     */
    public function buildRowsData(Producto $producto, array $d, array $context): array;
}
