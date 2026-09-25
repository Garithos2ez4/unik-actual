<?php

namespace App\Services;

use App\Models\Catalogo\Producto;
use App\Models\Inventario\RegistroProducto;
use App\Services\MercadoLibre\MercadoLibreCategoryResolverService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class MercadoLibreTemplateService
{
    /**
     * Extrae datos básicos requeridos para rellenar la plantilla.
     */
    private function calcularDatos(Producto $producto): array
    {
        // Obtener stock
        $stockTotal = \App\Models\Inventario\Inventario::where('idProducto', $producto->idProducto)->sum('stock');

        // Extraer specs
        $specs = [];
        if ($producto->Caracteristicas_Producto) {
            foreach ($producto->Caracteristicas_Producto as $cp) {
                if ($cp->Caracteristicas) {
                    $key = trim(str_replace(':', '', $cp->Caracteristicas->especificacion));
                    $specs[$key] = $cp->caracteristicaProducto;
                    $specs[strtolower($key)] = $cp->caracteristicaProducto;
                }
            }
        }

        // Determinar precio base (si hay lógicas de calculo, acá va)
        $precio = $producto->precio ?? 0;

        return [
            'stockTotal' => $stockTotal,
            'precioMax'  => $precio,
            'specs'      => $specs,
            'tituloDefecto' => trim($producto->nombreProducto . ' ' . $producto->modelo)
        ];
    }

    /**
     * Genera la plantilla de MercadoLibre para un producto determinado.
     */
    public function generarTemplate(Producto $producto, ?object $user = null): string
    {
        ini_set('memory_limit', '1G');
        
        $resolver = new MercadoLibreCategoryResolverService();
        $mapper   = $resolver->resolve($producto);

        $templatePath = storage_path($mapper->getTemplateFile());

        if (!file_exists($templatePath)) {
            throw new \Exception("El archivo base no existe en la ruta esperada: " . $mapper->getTemplateFile());
        }

        // Cargar plantilla
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getSheetByName($mapper->getSheetName());
        
        if (!$sheet) {
            $sheet = $spreadsheet->getActiveSheet(); // Fallback si no encuentra el nombre exacto
        }

        $d = $this->calcularDatos($producto);
        
        $usuarioNombre = $user ? explode(' ', $user->user)[0] : 'admin';
        $sku = trim($producto->modelo . ' ' . $usuarioNombre . ' v1');
        
        $context = [
            'stockTotal' => $d['stockTotal'],
            'sku'        => $sku,
            'user'       => $user
        ];

        $rows = $mapper->buildRowsData($producto, $d, $context);

        $rowNum = $mapper->getStartRow();

        foreach ($rows as $rowData) {
            foreach ($rowData as $col => $val) {
                if ($val !== null && $val !== '') {
                    $sheet->setCellValueExplicit($col . $rowNum, $val, DataType::TYPE_STRING);
                }
            }
            $rowNum++;
        }

        // Guardar archivo temporal
        $prefix = $mapper->getFilePrefix();
        $fileName = $prefix . date('Ymd_His') . '.xlsx';
        $tempPath = storage_path('app/temp/' . $fileName);

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($tempPath);

        return $tempPath;
    }
}
