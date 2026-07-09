<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Calculadora;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class FalabellaTemplateService
{
    const CATEGORIA_MONITORES = '483 - Electrónica / Computación / Periféricos de Monitor';

    const GARANTIA_MAP = [
        '60 meses' => '5 años', '5 años'   => '5 años',
        '48 meses' => '4 años', '4 años'   => '4 años',
        '36 meses' => '3 años', '3 años'   => '3 años',
        '24 meses' => '2 años', '2 años'   => '2 años',
        '18 meses' => '18 meses', '15 meses' => '15 meses',
        '12 meses' => '1 año', '1 año'    => '1 año',
        '9 meses'  => '9 meses', '6 meses'  => '6 meses',
        '3 meses'  => '3 meses', '2 meses'  => '2 meses', '1 mes' => '1 mes',
    ];

    const HZ_MAP = [
        '30' => '30Hz', '60' => '60Hz', '75' => '75Hz',
        '100' => '100Hz', '120' => '120Hz', '144' => '144Hz',
        '165' => '165Hz', '240' => '240Hz',
    ];

    const RESOLUCION_MAP = [
        '1920 x 1080' => 'FHD', '1920x1080' => 'FHD', 'FHD' => 'FHD',
        '2560 x 1440' => 'WQHD',
        '3840 x 2160' => '3840 x 2160',
        '1280 x 720'  => '1280 x 720',
        '1366 x 768'  => '1366 x 768',
    ];

    private function extractNumber(string $value): string
    {
        if (preg_match('/[\d]+(?:[.,]\d+)?/', $value, $matches)) {
            return str_replace(',', '.', $matches[0]);
        }
        return '';
    }

    private function calcularDatos(Producto $producto): array
    {
        $producto->loadMissing(['MarcaProducto', 'GrupoProducto', 'Caracteristicas_Producto.Caracteristicas']);
        $marca  = $producto->MarcaProducto->nombreMarca ?? '';
        $grupo  = $producto->GrupoProducto;
        $caract = $producto->Caracteristicas_Producto;
        $tc     = (float)(Calculadora::first()->tasaCambio ?? 3.41);

        $caractMap = [];
        foreach ($caract as $c) {
            if ($c->Caracteristicas) {
                $caractMap[$c->Caracteristicas->especificacion] = $c->caracteristicaProducto;
            }
        }

        $idCat = $grupo ? (int)$grupo->idCategoria : 0;
        $idGrp = $grupo ? (int)$grupo->idGrupoProducto : 0;
        $flete = (in_array($idCat, [1, 3]) || in_array($idGrp, [10,40,41,42,43])) ? 10.90 : 3.90;
        $comis = ($idGrp === 10) ? 0.08 : 0.10;
        $costo = (float)($producto->precioDolar ?? 0) * $tc * 1.18;
        $gan   = (float)($producto->gananciaExtra ?? 0);
        $precio = ($costo > 0) ? round(($costo + $gan + $flete) / (1 - $comis), 2) : 0;
        $precioFmt = $precio > 0 ? number_format($precio, 2, ',', '.') : '';

        $garantiaFbk = '';
        $gRaw = strtolower(trim((string)($producto->garantia ?? '')));
        foreach (self::GARANTIA_MAP as $pat => $val) {
            if (stripos($gRaw, strtolower($pat)) !== false) { $garantiaFbk = $val; break; }
        }

        $hzRaw = $caractMap['Hz'] ?? '';
        $hzNum = preg_replace('/[^0-9]/', '', $hzRaw);
        $hzFbk = self::HZ_MAP[$hzNum] ?? $hzRaw;

        $resolFbk = '';
        $resolRaw = $caractMap['Resolucion'] ?? '';
        foreach (self::RESOLUCION_MAP as $pat => $val) {
            if (stripos($resolRaw, $pat) !== false) { $resolFbk = $val; break; }
        }

        $alto  = $caractMap['Alto']  ?? '';
        $ancho = $caractMap['Ancho'] ?? '';
        $largo = $caractMap['Largo'] ?? '';
        $dim   = ($alto || $ancho || $largo) ? trim("$alto x $ancho x $largo") : '';

        $upc = preg_replace('/[^0-9]/', '', $producto->UPC ?? '');
        if (empty($upc) || $upc == '0') {
            $upc = sprintf('7%012d', $producto->idProducto);
        }
        $upcFbk = substr($upc, 0, 18);

        return compact('marca','caractMap','precioFmt','garantiaFbk','hzFbk','resolFbk','alto','ancho','largo','dim','upcFbk');
    }

    private function guardarYRetornar($spreadsheet, string $fileName): string
    {
        $tempPath = storage_path("app/public/temp/{$fileName}");
        
        $dir = dirname($tempPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);
        return $tempPath;
    }

    public function generarTemplate(Producto $producto): string
    {
        $spreadsheet = IOFactory::load(storage_path('falabella/falabella_template_base.xlsx'));
        $sheet = $spreadsheet->getSheetByName('Subir plantilla');
        $row = 4;
        $d = $this->calcularDatos($producto);

        $data = [
            'A' => $producto->nombreProducto, 'B' => $d['marca'],
            'C' => $producto->modelo, 'D' => $producto->descripcionProducto,
            'E' => self::CATEGORIA_MONITORES,
            'G' => $producto->codigoProducto, 'H' => $d['upcFbk'],
            'K' => $d['precioFmt'], 'P' => $d['resolFbk'],
            'R' => $d['alto'], 'S' => $d['ancho'], 'T' => $d['largo'],
            'V' => $d['caractMap']['Panel'] ?? '',
            'X' => $d['caractMap']['Contraste'] ?? '',
            'Y' => $d['dim'], 'Z' => 'Si',
            'AA' => $d['caractMap']['Tiempo de respuesta'] ?? '',
            'AB' => $d['hzFbk'], 'AD' => 'Nuevo',
            'AH' => $producto->garantia, 'AI' => $d['garantiaFbk'],
            'AK' => $this->extractNumber($d['ancho']),
            'AL' => $this->extractNumber($d['largo']),
            'AM' => $this->extractNumber($d['alto']),
            'AN' => $this->extractNumber($d['caractMap']['Peso'] ?? ''),
        ];

        foreach ($data as $col => $val) { $sheet->setCellValue($col . $row, $val); }

        $ts = Carbon::now()->format('Y-m-d_His');
        return $this->guardarYRetornar($spreadsheet, "ProductCreationTemplate_{$ts}.xlsx");
    }

    public function generarTemplateExpress(Producto $producto): string
    {
        $spreadsheet = IOFactory::load(storage_path('falabella/falabella_template_express_base.xlsx'));
        $sheet = $spreadsheet->getSheetByName('Subir plantilla');
        $row = 5;
        $d = $this->calcularDatos($producto);

        $data = [
            'A' => $producto->nombreProducto, 'B' => $d['marca'],
            'C' => $producto->descripcionProducto,
            'D' => self::CATEGORIA_MONITORES,
            'E' => $producto->codigoProducto, 'F' => $d['upcFbk'],
            'I' => $d['precioFmt'],
            'M' => '2026',
            'N' => $d['resolFbk'],
            'Q' => $d['caractMap']['Panel'] ?? '',
            'S' => $d['dim'],
            'U' => 'Nuevo',
            'V' => $this->extractNumber($d['ancho']),
            'W' => $this->extractNumber($d['largo']),
            'X' => $this->extractNumber($d['alto']),
            'Y' => $this->extractNumber($d['caractMap']['Peso'] ?? ''),
        ];

        foreach ($data as $col => $val) { $sheet->setCellValue($col . $row, $val); }

        $ts = Carbon::now()->format('Y-m-d_His');
        return $this->guardarYRetornar($spreadsheet, "ProductCreationTemplate-Express_{$ts}.xlsx");
    }
}