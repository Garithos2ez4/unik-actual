<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Calculadora;
use App\Models\Inventario;
use App\Services\Falabella\FalabellaCategoryResolverService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class FalabellaTemplateService
{
    // ── Constantes de categoría Falabella (template completo) ──────────────
    const CATEGORIA_MONITORES = '483 - Electrónica / Computación / Periféricos de Monitor';
    const CATEGORIA_LAPTOPS   = '432 - Electrónica / Computación / Computadores / Portátiles|notebooks';

    const GARANTIA_MAP = [
        '60 meses' => '5 años',
        '5 años'   => '5 años',
        '48 meses' => '4 años',
        '4 años'   => '4 años',
        '36 meses' => '3 años',
        '3 años'   => '3 años',
        '24 meses' => '2 años',
        '2 años'   => '2 años',
        '18 meses' => '18 meses',
        '15 meses' => '15 meses',
        '12 meses' => '1 año',
        '1 año'    => '1 año',
        '9 meses'  => '9 meses',
        '6 meses'  => '6 meses',
        '3 meses'  => '3 meses',
        '2 meses'  => '2 meses',
        '1 mes'    => '1 mes',
    ];

    const HZ_MAP = [
        '30'  => '30Hz',
        '60'  => '60Hz',
        '75'  => '75Hz',
        '100' => '100Hz',
        '120' => '120Hz',
        '144' => '144Hz',
        '165' => '165Hz',
        '240' => '240Hz',
    ];

    const RESOLUCION_MAP = [
        '1920 x 1080' => 'FHD',
        '1920x1080'   => 'FHD',
        'FHD'         => 'FHD',
        '2560 x 1440' => 'WQHD',
        '3840 x 2160' => '3840 x 2160',
        '1280 x 720'  => '1280 x 720',
        '1366 x 768'  => '1366 x 768',
    ];

    const CORES_MAP = [
        '1'  => 'Single core',
        '2'  => 'Dual core',
        '3'  => 'Triple core',
        '4'  => 'Quad core',
        '6'  => 'Hexa core',
        '8'  => 'Octa core',
        '10' => 'Deca core',
        '11' => '11 core',
        '12' => '12 core',
        '14' => '14 core',
        '16' => '16 core',
        '24' => '24 core',
    ];

    // ── Helpers internos ────────────────────────────────────────────────────

    private function extractNumber(string $value): string
    {
        if (preg_match('/[\d]+(?:[.,]\d+)?/', $value, $matches)) {
            return str_replace(',', '.', $matches[0]);
        }
        return '';
    }

    /**
     * Genera un SKU único para Falabella:
     * codigoProducto + modelo + usuario + V1/V2/V3
     * Sin caracteres especiales, máximo 50 caracteres.
     */
    private function buildSku(string $codigo, string $modelo, ?object $user, int $variacion): string
    {
        $sanitize = fn(string $s) => strtoupper(
            preg_replace('/[^A-Za-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s))
        );

        $modeloClean  = substr($sanitize($modelo), 0, 10);
        $usuarioClean = $user ? substr($sanitize($user->user ?? ''), 0, 8) : 'USR';
        $sufijo       = 'V' . $variacion;

        $sku = implode('-', array_filter([$codigo, $modeloClean, $usuarioClean, $sufijo]));

        return substr($sku, 0, 50);
    }

    /**
     * Carga y calcula todos los datos del producto necesarios para los mappers.
     * Los mappers acceden a estos datos vía el array $d.
     */
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
        $flete = (in_array($idCat, [1, 3]) || in_array($idGrp, [10, 40, 41, 42, 43])) ? 10.90 : 3.90;
        $comis = ($idGrp === 10) ? 0.08 : 0.10;
        $costo = (float)($producto->precioDolar ?? 0) * $tc * 1.18;
        $gan   = (float)($producto->gananciaExtra ?? 0);
        $precio = ($costo > 0) ? round(($costo + $gan + $flete) / (1 - $comis), 2) : 0;
        $precioFmt = $precio > 0 ? number_format($precio, 2, ',', '.') : '';
        $precioNormal = ($costo > 0) ? round($precio * 1.20, 2) : 0;
        $precioNormalFmt = $precioNormal > 0 ? number_format($precioNormal, 2, ',', '.') : '';

        $garantiaFbk = '';
        $gRaw = strtolower(trim((string)($producto->garantia ?? '')));
        foreach (self::GARANTIA_MAP as $pat => $val) {
            if (stripos($gRaw, strtolower($pat)) !== false) {
                $garantiaFbk = $val;
                break;
            }
        }

        $hzRaw = $caractMap['Hz'] ?? '';
        $hzNum = preg_replace('/[^0-9]/', '', $hzRaw);
        $hzFbk = self::HZ_MAP[$hzNum] ?? $hzRaw;

        $resolFbk = '';
        $resolRaw = $caractMap['Resolucion'] ?? '';
        foreach (self::RESOLUCION_MAP as $pat => $val) {
            if (stripos($resolRaw, $pat) !== false) {
                $resolFbk = $val;
                break;
            }
        }

        $alto  = $caractMap['Alto']  ?? '';
        $ancho = $caractMap['Ancho'] ?? '';
        $largo = $caractMap['Largo'] ?? '';
        $dim   = ($alto || $ancho || $largo) ? trim("$alto x $ancho x $largo") : '';

        $nucleosFbk = '';
        $nucleosRaw = $caractMap['NUCLEOS'] ?? $caractMap['Nucleos'] ?? $caractMap['nucleos'] ?? '';
        $nucleosNum = preg_replace('/[^0-9]/', '', $nucleosRaw);
        $nucleosFbk = self::CORES_MAP[$nucleosNum] ?? $nucleosRaw;

        $almacenamientoFbk = $caractMap['Almacenamiento'] ?? '';
        if (strtoupper(str_replace(' ', '', $almacenamientoFbk)) === '512GB') {
            $almacenamientoFbk = '512 GB';
        }

        $upc = preg_replace('/[^0-9]/', '', $producto->UPC ?? '');
        if (empty($upc) || $upc == '0') {
            $upc = sprintf('7%012d', $producto->idProducto);
        }
        $upcFbk = substr($upc, 0, 18);

        // Valores pre-extraídos para los mappers (evitan llamar extractNumber() dentro del mapper)
        $anchoCm   = $this->extractNumber($ancho);
        $largoCm   = $this->extractNumber($largo);
        $altoCm    = $this->extractNumber($alto);
        $pesoCm    = $this->extractNumber($caractMap['Peso'] ?? '');
        $pulgadasCm = $this->extractNumber($caractMap['Pulgadas'] ?? '');

        return compact(
            'marca',
            'caractMap',
            'precioFmt',
            'precioNormalFmt',
            'garantiaFbk',
            'hzFbk',
            'resolFbk',
            'alto',
            'ancho',
            'largo',
            'dim',
            'upcFbk',
            'nucleosFbk',
            'almacenamientoFbk',
            'anchoCm',
            'largoCm',
            'altoCm',
            'pesoCm',
            'pulgadasCm'
        );
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

    // ── Template Completo (solo monitores, sin cambios) ─────────────────────

    public function generarTemplate(Producto $producto, ?object $user = null): string
    {
        $spreadsheet = IOFactory::load(storage_path('falabella/falabella_template_base.xlsx'));
        $sheet = $spreadsheet->getSheetByName('Subir plantilla');
        $row = 4;
        $d = $this->calcularDatos($producto);

        $today     = Carbon::now();
        $saleStart = $today->format('Y-m-d');
        $saleEnd   = $today->copy()->addYears(5)->format('Y-m-d');
        $stockTotal = Inventario::where('idProducto', $producto->idProducto)->sum('stock');

        $variaciones = [
            ['titulo' => $producto->nombreProducto,              'sku' => $this->buildSku($producto->codigoProducto, $producto->modelo ?? '', $user, 1)],
            ['titulo' => $producto->nombreProducto . ' Gamer',   'sku' => $this->buildSku($producto->codigoProducto, $producto->modelo ?? '', $user, 2)],
            ['titulo' => $producto->nombreProducto . ' Oficina', 'sku' => $this->buildSku($producto->codigoProducto, $producto->modelo ?? '', $user, 3)],
        ];

        foreach ($variaciones as $index => $var) {
            $currentRow = $row + $index;
            $data = [
                'A'  => $var['titulo'],
                'B'  => $d['marca'],
                'C'  => $producto->modelo,
                'D'  => $producto->descripcionProducto,
                'E'  => self::CATEGORIA_MONITORES,
                'G'  => $var['sku'],
                'H'  => $d['upcFbk'],
                'J'  => $stockTotal,
                'K'  => $d['precioNormalFmt'],
                'L'  => $d['precioFmt'],
                'M'  => $saleStart,
                'N'  => $saleEnd,
                'P'  => $d['resolFbk'],
                'R'  => $d['alto'],
                'S'  => $d['ancho'],
                'T'  => $d['largo'],
                'V'  => $d['caractMap']['Panel'] ?? '',
                'X'  => $d['caractMap']['Contraste'] ?? '',
                'Y'  => $d['dim'],
                'Z'  => 'Si',
                'AA' => $d['caractMap']['Tiempo de respuesta'] ?? '',
                'AB' => $d['hzFbk'],
                'AD' => 'Nuevo',
                'AH' => $producto->garantia,
                'AI' => $d['garantiaFbk'],
                'AK' => $d['anchoCm'],
                'AL' => $d['largoCm'],
                'AM' => $d['altoCm'],
                'AN' => $d['pesoCm'],
            ];

            foreach ($data as $col => $val) {
                $sheet->setCellValue($col . $currentRow, $val);
            }
        }

        $ts = Carbon::now()->format('Y-m-d_His');
        return $this->guardarYRetornar($spreadsheet, "ProductCreationTemplate_{$ts}.xlsx");
    }
    /**
     * Devuelve las sugerencias de títulos para el modal del frontend.
     * Usado por el endpoint AJAX GET /producto/{id}/falabella-titulos-sugeridos
     */
    public function sugerirTitulos(Producto $producto): array
    {
        $producto->loadMissing(['MarcaProducto', 'GrupoProducto', 'Caracteristicas_Producto.Caracteristicas']);
        $resolver = new FalabellaCategoryResolverService();
        $mapper   = $resolver->resolve($producto);
        $d        = $this->calcularDatos($producto);
        return $mapper->sugerirTitulos($producto, $d);
    }

    public function generarTemplateExpress(Producto $producto, ?object $user = null, array $titulos = []): string
    {
        $resolver = new FalabellaCategoryResolverService();
        $mapper   = $resolver->resolve($producto);

        $spreadsheet = IOFactory::load(storage_path($mapper->getTemplateFile()));
        $sheet       = $spreadsheet->getSheetByName('Subir plantilla');
        $d           = $this->calcularDatos($producto);

        $today      = Carbon::now();
        $stockTotal = Inventario::where('idProducto', $producto->idProducto)->sum('stock');

        $context = [
            'stockTotal' => $stockTotal,
            'saleStart'  => $today->format('Y-m-d'),
            'saleEnd'    => $today->copy()->addYears(5)->format('Y-m-d'),
        ];

        $buildSkuFn  = fn(string $codigo, string $modelo, ?object $u, int $v) => $this->buildSku($codigo, $modelo, $u, $v);
        $variaciones = $mapper->getVariaciones($producto, $buildSkuFn, $user, $titulos);

        foreach ($variaciones as $index => $var) {
            $data = $mapper->buildColumnData($var, $d, $producto, $context);
            foreach ($data as $col => $val) {
                $sheet->setCellValue($col . ($mapper->getStartRow() + $index), $val);
            }
        }

        $ts = Carbon::now()->format('Y-m-d_His');
        return $this->guardarYRetornar($spreadsheet, $mapper->getFilePrefix() . $ts . '.xlsx');
    }
}
