<?php

namespace App\Services;

use App\Models\Catalogo\Producto;
use App\Models\Precios\Calculadora;
use App\Models\Inventario\Inventario;
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
    ];

    const RESOLUCION_MAP = [
        '1920 x 1080' => 'FHD',
        '1920x1080'   => 'FHD',
        'FHD+'        => 'FHD+',
        'FHD'         => 'FHD',
        '2560 x 1440' => 'WQHD',
        '3840 x 2160' => '3840 x 2160',
        '1280 x 720'  => '1280 x 720',
        '1366 x 768'  => '1366 x 768',
        '2712 x 1220' => '2712 x 1220 (1.5K)',
        '360 x 360'   => '360 x 360',
        '390 x 390'   => '390 x 390',
        '2944 x 1840' => '3K (2944 x 1840)',
        '3K'          => '3K (2944 x 1840)',
        '450 x 450'   => '450 x 450',
        '480 x 320'   => '480 x 320',
        '480 x 480'   => '480 x 480',
        '4K UHD'      => '4K UHD',
        '4K HDR'      => '4K HDR',
        '5k'          => '5k',
        'Retina 5K'   => 'Retina 5K',
        '6K'          => '6K',
        'HD+'         => 'HD+',
        '800 x 480'   => '800 x 480',
        'HD 1.080p'   => 'HD 1.080p',
        'HD'          => 'HD',
        'HXGA'        => 'HXGA',
        'qHD'         => 'qHD',
        'WQHD'        => 'WQHD',
        'VGA'         => 'VGA',
        'Super Retina HD'  => 'Super Retina HD',
        'Super Retina XDR' => 'Super Retina XDR',
        'SD'          => 'SD',
        'SVGA'        => 'SVGA',
        'SXGA+'       => 'SXGA+',
        'SXGA'        => 'SXGA',
        '8K UHD'      => '8K UHD',
        'UXGA'        => 'UXGA',
        'WQUXGA'      => 'WQUXGA',
        'WQXGA'       => 'WQXGA',
        'WSVGA'       => 'WSVGA',
        'WSXGA'       => 'WSXGA',
        'WUXGA'       => 'WUXGA',
        'WXGA+'       => 'WXGA+',
        'WXGA'        => 'WXGA',
        'XGA+'        => 'XGA+',
        'XGA'         => 'XGA',
    ];

    const ALMACENAMIENTO_LIST = [
        '256 MB', '512 MB', '1GB', '2GB', '3GB', '4GB', '8GB', '16GB', '24GB', '30GB', '32GB', '32 GB eMMC', '50GB', '64GB', '64 GB eMMC', '80GB', '96GB', '120GB', '120GB SSD', '128GB', '128 GB eMMC', '160GB', '240GB', '250GB', '250GB SSD', '256 GB', '256 GB eMMC', '256GB SSD', '320GB', '480GB', '500GB', '500GB SSD', '512 GB', '750GB', '825GB', '960GB', '1TB', '1.5 TB', '2TB', '3TB', '4TB', '6TB', '7 TB', '8TB', '10TB', '20TB', '30TB', '32TB', '40TB', '48TB', '64TB', 'No aplica'
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


    private function extractNumber(string $value): string
    {
        if (preg_match('/[\d]+(?:[.,]\d+)?/', $value, $matches)) {
            return str_replace(',', '.', $matches[0]);
        }
        return '';
    }

    private function extractPeso(string $value): string
    {
        if (preg_match('/([\d]+(?:[.,]\d+)?)/', $value, $matches)) {
            $num = (float)str_replace(',', '.', $matches[1]);
            $strLower = strtolower(str_replace(' ', '', $value));
            
            // Si no menciona kilos, pero sí menciona gramos, lo dividimos entre 1000
            if (strpos($strLower, 'kg') === false && strpos($strLower, 'kilo') === false) {
                if (strpos($strLower, 'g') !== false || strpos($strLower, 'gr') !== false) {
                    $num = $num / 1000;
                }
            }
            return (string)round($num, 2);
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
            preg_replace('/[^A-Za-z0-9\-]/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s))
        );

        $modeloClean  = $sanitize($modelo);
        $usuarioClean = $user ? substr($sanitize($user->user ?? ''), 0, 8) : 'USR';
        $sufijo       = 'V' . $variacion;

        $sku = implode('-', array_filter([$modeloClean, $usuarioClean, $sufijo]));

        // Limitar a 50 caracteres máximo por regla de Falabella
        return substr($sku, 0, 50);
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
        $flete = (in_array($idCat, [1, 3]) || in_array($idGrp, [10, 40, 41, 42, 43])) ? 10.90 : 3.90;
        if ($idGrp === 10) {
            $comis = 0.08;
        } elseif (in_array($idGrp, [155, 156, 157, 158, 159, 160, 169])) {
            $comis = 0.15;
        } else {
            $comis = 0.10;
        }

        $precio = 0;
        if ((float)($producto->precioDolar ?? 0) > 0 && $idGrp > 0) {
            $preciosService = new \App\Services\PreciosService();
            // Obtenemos el promedio base del sistema central EN SOLES
            $precioSoles = (float)$producto->precioDolar * $tc;
            $promedioBase = $preciosService->getPromedio($precioSoles, $idGrp, 'SOL');

            // Sumamos ganancia extra manual si la hay
            $gananciaManual = (float)($producto->gananciaExtra ?? 0);
            $promedioBase += $gananciaManual;

            if ($promedioBase > 0) {
                // Factor de facturación (normalmente 1.01) desde la base de datos
                $facturacionFactor = (\App\Models\Precios\Calculadora::first()->facturacion / 100) + 1;

                // Replicando la fórmula exacta de public/js/calculator-scripts.js
                $monto      = $promedioBase * ($comis + 1);
                $costo_calc = $promedioBase + ($monto * $comis);
                $tsfact     = $costo_calc;
                $tfact      = $tsfact * $facturacionFactor;
                $promed     = (($tfact + $tsfact) / 2) + $flete;

                $precio = round($promed, 2);
            }
        }

        $precioFmt = $precio > 0 ? number_format($precio, 2, ',', '.') : '';
        $precioNormal = ($precio > 0) ? round($precio * 1.20) : 0;
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
        if ($almacenamientoFbk) {
            $normalizedInput = strtolower(str_replace(' ', '', $almacenamientoFbk));
            foreach (self::ALMACENAMIENTO_LIST as $validOption) {
                $normalizedOption = strtolower(str_replace(' ', '', $validOption));
                if ($normalizedInput === $normalizedOption) {
                    $almacenamientoFbk = $validOption;
                    break;
                }
            }
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
        $pesoCm    = $this->extractPeso($caractMap['Peso'] ?? '');
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
                'H'  => substr($d['upcFbk'], 0, 13) . ($index + 1),
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
