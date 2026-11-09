<?php

namespace App\Services;

use App\Models\Catalogo\MarcaProducto;
use App\Models\Catalogo\Producto;
use App\Models\Catalogo\Publicacion;
use App\Models\Inventario\Inventario_Proveedor;
use App\Models\Inventario\InventarioProveedorSku;
use App\Models\Inventario\LaptopSeguimiento;
use App\Models\Usuarios\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaptopSeguimientoService
{
    /**
     * Detecta la plataforma CPU (INTEL CORE o AMD RYZEN) desde el texto de procesador o gama.
     */
    public function detectarPlataformaCpu(?string $procesador, ?string $gama = null): string
    {
        $txt = strtoupper(trim(($procesador ?? '') . ' ' . ($gama ?? '')));
        if (str_contains($txt, 'RYZEN') || str_contains($txt, 'AMD') || str_contains($txt, 'ATHLON')) {
            return 'AMD RYZEN';
        }

        return 'INTEL CORE';
    }

    /**
     * Detecta la gama estándar a partir del texto del procesador.
     */
    public function detectarGama(?string $procesador, ?string $gamaActual = null): string
    {
        if (!empty($gamaActual)) {
            $gUpper = strtoupper(trim($gamaActual));
            if (str_contains($gUpper, 'CELERON')) return 'CELERON';
            if (str_contains($gUpper, 'RYZEN 9') || str_contains($gUpper, 'AI 9')) return 'RYZEN 9 / RYZEN AI 9';
            if (str_contains($gUpper, 'RYZEN 7') || str_contains($gUpper, 'AI 7')) return 'RYZEN 7 / RYZEN AI 7';
            if (str_contains($gUpper, 'RYZEN 5') || str_contains($gUpper, 'AI 5')) return 'RYZEN 5 / RYZEN AI 5';
            if (str_contains($gUpper, 'I9') || str_contains($gUpper, 'CORE 9') || str_contains($gUpper, 'ULTRA 9') || str_contains($gUpper, 'ULTRA9')) return 'core i9 / CORE 9 / ultra 9';
            if (str_contains($gUpper, 'I7') || str_contains($gUpper, 'CORE 7') || str_contains($gUpper, 'ULTRA 7') || str_contains($gUpper, 'ULTRA7')) return 'core i7 / CORE 7 / ultra 7';
            if (str_contains($gUpper, 'I5') || str_contains($gUpper, 'CORE 5') || str_contains($gUpper, 'ULTRA 5') || str_contains($gUpper, 'ULTRA5')) return 'core i5 / CORE 5 / ultra 5';
            if (str_contains($gUpper, 'I3') || str_contains($gUpper, 'CORE 3')) return 'core i3 / CORE 3';
        }

        $p = strtoupper(trim($procesador ?? ''));
        if (str_contains($p, 'CELERON') || str_contains($p, 'N4500') || str_contains($p, 'N100')) {
            return 'CELERON';
        }
        if (preg_match('/\b(RYZEN\s*(AI\s*)?9|R9)\b/i', $p)) {
            return 'RYZEN 9 / RYZEN AI 9';
        }
        if (preg_match('/\b(RYZEN\s*(AI\s*)?7|R7)\b/i', $p)) {
            return 'RYZEN 7 / RYZEN AI 7';
        }
        if (preg_match('/\b(RYZEN\s*(AI\s*)?5|R5)\b/i', $p)) {
            return 'RYZEN 5 / RYZEN AI 5';
        }
        if (preg_match('/\b(I9|CORE\s*9|ULTRA\s*9|U9)\b/i', $p)) {
            return 'core i9 / CORE 9 / ultra 9';
        }
        if (preg_match('/\b(I7|CORE\s*7|ULTRA\s*7|U7)\b/i', $p)) {
            return 'core i7 / CORE 7 / ultra 7';
        }
        if (preg_match('/\b(I5|CORE\s*5|ULTRA\s*5|U5)\b/i', $p)) {
            return 'core i5 / CORE 5 / ultra 5';
        }
        if (preg_match('/\b(I3|CORE\s*3)\b/i', $p)) {
            return 'core i3 / CORE 3';
        }

        return 'core i7 / CORE 7 / ultra 7';
    }

    /**
     * Normaliza el valor de una celda de proveedor (CUAD, DM, ALC, ASIA):
     * - 'x' / 'X' -> tipo STOCK (tiene stock)
     * - 'PROV'    -> tipo PROV (en lista del proveedor sin stock confirmado, celda negra)
     * - 'W06335'  -> tipo CODIGO (código en el proveedor)
     * - ''        -> tipo VACIO
     */
    public function normalizarCeldaProveedor(?string $raw): array
    {
        $val = trim((string) $raw);
        if ($val === '' || $val === "\u{00A0}") {
            return ['valor' => '', 'tipo' => 'VACIO'];
        }

        $upper = strtoupper($val);
        if ($upper === 'X' || $upper === 'SI' || $upper === 'SÍ') {
            return ['valor' => 'x', 'tipo' => 'STOCK'];
        }
        if (str_starts_with($upper, 'PROV')) {
            return ['valor' => 'PROV', 'tipo' => 'PROV'];
        }

        return ['valor' => $val, 'tipo' => 'CODIGO'];
    }

    /**
     * Parsea el texto de la columna R (CUENTA ML) como "UNIK/GAMING/UP" o "LIZ / UNIK"
     * y devuelve los IDs de CuentasPlataforma y las siglas normalizadas.
     */
    public function parsearCuentasMl(?string $raw): array
    {
        $texto = strtoupper(trim((string) $raw));
        if ($texto === '') {
            return ['ids' => [], 'siglas' => [], 'raw' => ''];
        }

        $cuentasCfg = config('laptop_seguimiento.cuentas_ml', []);
        $ids = [];
        $siglas = [];

        $partes = preg_split('/[\/,\|]+/', $texto) ?: [];
        foreach ($partes as $parte) {
            $token = trim($parte);
            if ($token === '') continue;

            foreach ($cuentasCfg as $idCuenta => $cfg) {
                if ($token === $cfg['sigla'] || in_array($token, $cfg['aliases'] ?? [], true) || str_contains($token, $cfg['sigla'])) {
                    if (!in_array((int) $idCuenta, $ids, true)) {
                        $ids[] = (int) $idCuenta;
                        $siglas[] = $cfg['sigla'];
                    }
                }
            }
        }

        return [
            'ids'    => $ids,
            'siglas' => $siglas,
            'raw'    => trim((string) $raw),
        ];
    }

    /**
     * Busca un Producto existente (grupos 1, 2, 3) por partNumber o modelo.
     */
    public function buscarProductoVinculado(?string $partNumber, ?string $modelo, array &$indiceProductos = []): ?int
    {
        if (empty($indiceProductos)) {
            $productos = Producto::whereIn('idGrupo', [1, 2, 3])
                ->get(['idProducto', 'partNumber', 'modelo', 'codigoProducto']);

            $indiceProductos = ['byPn' => [], 'byMod' => []];
            foreach ($productos as $p) {
                $pn = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $p->partNumber)));
                $mod = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $p->modelo)));
                if (strlen($pn) >= 4) {
                    $indiceProductos['byPn'][$pn] = (int) $p->idProducto;
                }
                if (strlen($mod) >= 4) {
                    $indiceProductos['byMod'][$mod] = (int) $p->idProducto;
                }
            }
        }

        $pnClean = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $partNumber)));
        $modClean = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $modelo)));

        if (strlen($pnClean) >= 4) {
            if (isset($indiceProductos['byPn'][$pnClean])) {
                return $indiceProductos['byPn'][$pnClean];
            }
            if (isset($indiceProductos['byMod'][$pnClean])) {
                return $indiceProductos['byMod'][$pnClean];
            }
        }

        if (strlen($modClean) >= 4) {
            if (isset($indiceProductos['byMod'][$modClean])) {
                return $indiceProductos['byMod'][$modClean];
            }
            if (isset($indiceProductos['byPn'][$modClean])) {
                return $indiceProductos['byPn'][$modClean];
            }
        }

        return null;
    }

    /**
     * Sincroniza los datos de proveedores de un LaptopSeguimiento con `Inventario_Proveedor`
     * e `inventario_proveedor_skus` cuando tiene `idProducto` vinculado.
     */
    public function sincronizarConInventarioProveedor(LaptopSeguimiento $seg): void
    {
        if (empty($seg->idProducto)) {
            return;
        }

        $provsData = $seg->proveedores_data ?? [];
        $proveedoresCfg = config('laptop_seguimiento.proveedores', []);

        // Obtener o crear el registro maestro en Inventario_Proveedor (UNIQUE por idProducto)
        $invProv = Inventario_Proveedor::where('idProducto', $seg->idProducto)->first();

        $primerProveedorActivo = 22; // ASIA por defecto
        $tieneStockAlguno = false;

        foreach ($proveedoresCfg as $idProv => $cfg) {
            $celda = $provsData[$idProv] ?? $provsData[(string) $idProv] ?? null;
            if ($celda && !empty($celda['valor'])) {
                $primerProveedorActivo = (int) $idProv;
                if (($celda['tipo'] ?? '') === 'STOCK' || ($celda['tipo'] ?? '') === 'CODIGO') {
                    $tieneStockAlguno = true;
                }
            }
        }

        if (!$invProv) {
            $nextId = ((int) Inventario_Proveedor::max('idInventarioProveedor')) + 1;
            $invProv = Inventario_Proveedor::create([
                'idInventarioProveedor' => $nextId,
                'idProducto'            => $seg->idProducto,
                'idProveedor'           => $primerProveedorActivo,
                'stock'                 => $tieneStockAlguno ? max(1, (int) $seg->stock_texto) : 0,
                'estado'                => 'VIGENTE',
            ]);
        }

        foreach ($proveedoresCfg as $idProv => $cfg) {
            $celda = $provsData[$idProv] ?? $provsData[(string) $idProv] ?? ['valor' => '', 'tipo' => 'VACIO'];
            $valor = trim((string) ($celda['valor'] ?? ''));
            $tipo = $celda['tipo'] ?? 'VACIO';

            if ($valor === '' || $tipo === 'VACIO') {
                InventarioProveedorSku::where('idInventarioProveedor', $invProv->idInventarioProveedor)
                    ->where('idProveedor', (int) $idProv)
                    ->delete();
                continue;
            }

            InventarioProveedorSku::updateOrCreate(
                [
                    'idInventarioProveedor' => $invProv->idInventarioProveedor,
                    'idProveedor'           => (int) $idProv,
                ],
                [
                    'sku_proveedor' => $valor,
                    'part_number'   => $seg->part_number,
                    'hay_mas'       => in_array($tipo, ['STOCK', 'CODIGO'], true),
                    'datos_extra'   => [
                        'tipo_celda' => $tipo,
                        'valor'      => $valor,
                        'origen'     => 'seguimiento_laptops',
                    ],
                ]
            );
        }
    }

    /**
     * Importa el archivo Excel (por defecto `storage/Libro11.xlsx`) hacia `laptop_seguimientos`
     * y sincroniza los vinculados con `Inventario_Proveedor` / `inventario_proveedor_skus`.
     */
    public function importarDesdeExcel(?string $filePath = null, bool $limpiarAntes = true): array
    {
        $path = $filePath ?: storage_path('Libro11.xlsx');
        if (!file_exists($path)) {
            throw new \RuntimeException("No se encontró el archivo Excel en: {$path}");
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(false);
        $book = $reader->load($path);
        $sheet = $book->getActiveSheet();
        $maxRow = $sheet->getHighestDataRow();

        $marcasCfg = config('laptop_seguimiento.marcas', []);
        $usuariosMap = Usuario::pluck('idUser', 'user')
            ->mapWithKeys(fn ($id, $name) => [strtoupper(trim($name)) => (int) $id])
            ->toArray();

        $indiceProductos = [];
        $this->buscarProductoVinculado(null, null, $indiceProductos);

        $marcaActual = 'ACER';
        $idMarcaActual = $marcasCfg['ACER']['idMarca'] ?? 5;
        $plataformaActual = 'INTEL CORE';
        $gamaActual = 'core i5 / CORE 5 / ultra 5';

        $importados = 0;
        $vinculados = 0;
        $noEncontrados = [];
        $orden = 1;

        DB::beginTransaction();
        try {
            if ($limpiarAntes) {
                LaptopSeguimiento::query()->delete();
            }

            for ($r = 3; $r <= $maxRow; $r++) {
                $colA = trim((string) $sheet->getCell("A{$r}")->getValue());
                $colB = trim((string) $sheet->getCell("B{$r}")->getValue());
                $colC = trim((string) $sheet->getCell("C{$r}")->getValue());
                $colD = trim((string) $sheet->getCell("D{$r}")->getValue());
                $colE = trim((string) $sheet->getCell("E{$r}")->getValue());
                $colF = trim((string) $sheet->getCell("F{$r}")->getValue());
                $colG = trim((string) $sheet->getCell("G{$r}")->getValue());
                $colH = trim((string) $sheet->getCell("H{$r}")->getValue());
                $colI = trim((string) $sheet->getCell("I{$r}")->getValue());
                $colJ = trim((string) $sheet->getCell("J{$r}")->getValue());
                $colK = trim((string) $sheet->getCell("K{$r}")->getValue());
                $colLRaw = $sheet->getCell("L{$r}")->getValue();
                $colM = trim((string) $sheet->getCell("M{$r}")->getValue());
                $colN = trim((string) $sheet->getCell("N{$r}")->getValue());
                $colO = trim((string) $sheet->getCell("O{$r}")->getValue());
                $colP = trim((string) $sheet->getCell("P{$r}")->getValue());
                $colQ = trim((string) $sheet->getCell("Q{$r}")->getValue());
                $colR = trim((string) $sheet->getCell("R{$r}")->getValue());

                if ($colB === '' && $colF === '' && $colJ === '') {
                    continue;
                }

                $fillType = $sheet->getStyle("B{$r}")->getFill()->getFillType();
                $rgb = $fillType === 'none' ? 'NONE' : strtoupper($sheet->getStyle("B{$r}")->getFill()->getStartColor()->getRGB());
                $bUpper = strtoupper($colB);

                // 1. ¿Es fila de cabecera de Marca? (Azul #0070C0 o nombre exacto de marca sin specs)
                if ($rgb === '0070C0' || (isset($marcasCfg[$bUpper]) && $colF === '' && $colJ === '')) {
                    foreach (array_keys($marcasCfg) as $mKey) {
                        if (str_contains($bUpper, $mKey)) {
                            $marcaActual = $mKey;
                            $idMarcaActual = $marcasCfg[$mKey]['idMarca'];
                            break;
                        }
                    }
                    continue;
                }

                // 2. ¿Es fila de cabecera de Plataforma CPU? (Celeste #00B0F0)
                if ($rgb === '00B0F0' || (in_array($bUpper, ['INTEL CORE', 'AMD RYZEN', 'AMD'], true) && $colF === '' && $colJ === '')) {
                    $plataformaActual = str_contains($bUpper, 'AMD') || str_contains($bUpper, 'RYZEN') ? 'AMD RYZEN' : 'INTEL CORE';
                    continue;
                }

                // 3. ¿Es fila de cabecera de Gama? (Magenta #F705E3 o fila solo con nombre de gama)
                if ($rgb === 'F705E3' || ($colF === '' && $colJ === '' && $colC === '' && $colO === '' && $colP === '')) {
                    $gamaActual = $this->detectarGama($colB, $colB);
                    $plataformaActual = $this->detectarPlataformaCpu($colB, $gamaActual);
                    continue;
                }

                // 4. Es una fila de modelo de laptop
                $estadoFila = match ($rgb) {
                    '66FF33' => 'CON_STOCK',
                    'FF0000' => 'AGOTADO',
                    'FFC000' => 'EN_TRANSITO',
                    '7030A0' => 'DESHABILITADA',
                    default  => strtoupper($colK) === 'PAUSADA' ? 'PAUSADA' : 'NORMAL',
                };

                // Fecha de revisión (Columna L)
                $fechaRevision = null;
                if ($colLRaw !== null && $colLRaw !== '' && trim((string) $colLRaw) !== '') {
                    if (is_numeric($colLRaw) && (float) $colLRaw > 20000) {
                        try {
                            $fechaRevision = Carbon::instance(ExcelDate::excelToDateTimeObject((float) $colLRaw))->format('Y-m-d');
                        } catch (\Throwable $e) {
                            $fechaRevision = null;
                        }
                    } else {
                        try {
                            $fechaRevision = Carbon::parse((string) $colLRaw)->format('Y-m-d');
                        } catch (\Throwable $e) {
                            $fechaRevision = null;
                        }
                    }
                }

                // Proveedores M=CUAD(40), N=DM(59), O=ALC(48), P=ASIA(22)
                $proveedoresData = [
                    40 => $this->normalizarCeldaProveedor($colM),
                    59 => $this->normalizarCeldaProveedor($colN),
                    48 => $this->normalizarCeldaProveedor($colO),
                    22 => $this->normalizarCeldaProveedor($colP),
                ];

                // Cuentas ML (Columna R)
                $cuentasMlData = $this->parsearCuentasMl($colR);

                // Responsable (Columna A) - No crear usuarios nuevos, solo vincular si coincide
                $oculto = strtoupper($colA) === 'VER OCULTOS';
                $respTexto = $oculto ? null : ($colA !== '' ? $colA : null);
                $idUser = null;
                if ($respTexto && isset($usuariosMap[strtoupper($respTexto)])) {
                    $idUser = $usuariosMap[strtoupper($respTexto)];
                }

                // Vincular con Producto si existe por P/N o Modelo
                $idProducto = $this->buscarProductoVinculado($colJ, $colB, $indiceProductos);

                $gamaFila = $this->detectarGama($colF, $gamaActual);
                $platFila = $this->detectarPlataformaCpu($colF, $gamaFila);

                $seg = LaptopSeguimiento::create([
                    'idProducto'        => $idProducto,
                    'idMarca'           => $idMarcaActual,
                    'marca_nombre'      => $marcaActual,
                    'plataforma_cpu'    => $platFila ?: $plataformaActual,
                    'gama'              => $gamaFila,
                    'idUser'            => $idUser,
                    'responsable_texto' => $respTexto,
                    'modelo'            => $colB,
                    'serie'             => $colC ?: null,
                    'tamano'            => $colD ?: null,
                    'frecuencia'        => $colE ?: null,
                    'procesador'        => $colF ?: null,
                    'ram'               => $colG ?: null,
                    'disco'             => $colH ?: null,
                    'video'             => $colI ?: null,
                    'part_number'       => $colJ ?: null,
                    'stock_texto'       => $colK ?: null,
                    'stock_unik'        => $colQ ?: null,
                    'fecha_revision'    => $fechaRevision,
                    'estado_fila'       => $estadoFila,
                    'proveedores_data'  => $proveedoresData,
                    'cuentas_ml_data'   => $cuentasMlData,
                    'oculto'            => $oculto,
                    'orden'             => $orden++,
                ]);

                $importados++;
                if ($idProducto) {
                    $vinculados++;
                    $this->sincronizarConInventarioProveedor($seg);
                } else {
                    $noEncontrados[] = [
                        'idLaptopSeguimiento' => $seg->idLaptopSeguimiento,
                        'marca'               => $marcaActual,
                        'modelo'              => $colB,
                        'part_number'         => $colJ,
                        'procesador'          => $colF,
                    ];
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'importados'     => $importados,
            'vinculados'     => $vinculados,
            'no_encontrados' => $noEncontrados,
        ];
    }

    /**
     * Obtiene los registros filtrados y agrupados por Marca -> Plataforma CPU -> Gama,
     * además de las métricas globales del tablero.
     */
    public function obtenerTableroAgrupado(array $filtros = []): array
    {
        $query = LaptopSeguimiento::with(['usuario', 'producto']);

        if (empty($filtros['mostrar_ocultos'])) {
            $query->where('oculto', false);
        }

        if (!empty($filtros['marca'])) {
            $query->where('marca_nombre', strtoupper($filtros['marca']));
        }

        if (!empty($filtros['gama'])) {
            $query->where('gama', $filtros['gama']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado_fila', $filtros['estado']);
        }

        if (!empty($filtros['responsable'])) {
            $resp = strtoupper(trim($filtros['responsable']));
            $query->where(function ($q) use ($resp) {
                $q->whereRaw('UPPER(responsable_texto) = ?', [$resp])
                  ->orWhere('idUser', (int) $resp);
            });
        }

        if (!empty($filtros['solo_vencidos'])) {
            $query->whereNotNull('fecha_revision')
                  ->whereDate('fecha_revision', '<', now()->toDateString());
        }

        if (!empty($filtros['vinculo'])) {
            if ($filtros['vinculo'] === 'vinculados') {
                $query->whereNotNull('idProducto');
            } elseif ($filtros['vinculo'] === 'sin_vincular') {
                $query->whereNull('idProducto');
            }
        }

        if (!empty($filtros['q'])) {
            $q = trim($filtros['q']);
            $query->where(function ($sub) use ($q) {
                $sub->where('modelo', 'LIKE', "%{$q}%")
                    ->orWhere('part_number', 'LIKE', "%{$q}%")
                    ->orWhere('serie', 'LIKE', "%{$q}%")
                    ->orWhere('procesador', 'LIKE', "%{$q}%")
                    ->orWhere('video', 'LIKE', "%{$q}%")
                    ->orWhere('proveedores_data', 'LIKE', "%{$q}%");
            });
        }

        $items = $query->orderBy('orden')->orderBy('idLaptopSeguimiento')->get();

        // Filtros post-query para proveedor específico o cuenta ML
        if (!empty($filtros['idProveedor'])) {
            $idProv = (int) $filtros['idProveedor'];
            $items = $items->filter(function (LaptopSeguimiento $item) use ($idProv) {
                $celda = $item->proveedores_data[$idProv] ?? $item->proveedores_data[(string) $idProv] ?? null;
                return $celda && !empty($celda['valor']);
            })->values();
        }

        if (!empty($filtros['idCuentaPlataforma'])) {
            $idCta = (int) $filtros['idCuentaPlataforma'];
            $items = $items->filter(function (LaptopSeguimiento $item) use ($idCta) {
                $ids = $item->cuentas_ml_data['ids'] ?? [];
                return in_array($idCta, $ids, true);
            })->values();
        }

        // Agrupar: Marca -> Plataforma CPU -> Gama
        $agrupado = [];
        foreach ($items as $item) {
            $m = $item->marca_nombre ?: 'OTRAS';
            $cpu = $item->plataforma_cpu ?: 'INTEL CORE';
            $gama = $item->gama ?: 'core i7 / CORE 7 / ultra 7';
            $agrupado[$m][$cpu][$gama][] = $item;
        }

        // Contadores globales (sobre todos los registros activos)
        $todos = LaptopSeguimiento::where('oculto', false)->get([
            'idLaptopSeguimiento', 'idProducto', 'estado_fila', 'fecha_revision', 'proveedores_data', 'responsable_texto'
        ]);

        $hoy = now()->startOfDay();
        $stats = [
            'total'         => $todos->count(),
            'con_stock'     => $todos->where('estado_fila', 'CON_STOCK')->count(),
            'agotados'      => $todos->where('estado_fila', 'AGOTADO')->count(),
            'en_transito'   => $todos->where('estado_fila', 'EN_TRANSITO')->count(),
            'deshabilitadas'=> $todos->where('estado_fila', 'DESHABILITADA')->count(),
            'vencidos'      => $todos->filter(fn ($x) => $x->fecha_revision && $x->fecha_revision->lt($hoy))->count(),
            'vinculados'    => $todos->whereNotNull('idProducto')->count(),
            'sin_vincular'  => $todos->whereNull('idProducto')->count(),
        ];

        $responsablesDisponibles = $todos->pluck('responsable_texto')
            ->filter()
            ->map(fn ($r) => strtoupper(trim($r)))
            ->unique()
            ->sort()
            ->values();

        return [
            'items'        => $items,
            'agrupado'     => $agrupado,
            'stats'        => $stats,
            'responsables' => $responsablesDisponibles,
        ];
    }

    /**
     * Genera un archivo Excel (.xlsx) con el mismo formato y colores que Libro11.xlsx.
     */
    public function exportarExcel(array $filtros = []): Spreadsheet
    {
        $tablero = $this->obtenerTableroAgrupado($filtros);
        $agrupado = $tablero['agrupado'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('LAPTOP ML US');

        // Fila 1: Leyenda de estados
        $sheet->mergeCells('B1:C1');
        $sheet->setCellValue('B1', 'HAY STOCK EN PROVEEDOR o TIENDA');
        $sheet->getStyle('B1:C1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('66FF33');

        $sheet->mergeCells('E1:H1');
        $sheet->setCellValue('E1', 'AGOTADO EN PROVEEDORES');
        $sheet->getStyle('E1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');
        $sheet->getStyle('E1:H1')->getFont()->getColor()->setRGB('FFFFFF');

        $sheet->mergeCells('J1:N1');
        $sheet->setCellValue('J1', 'STOCK EN TRANSITO - X LLEGAR A LIMA');
        $sheet->getStyle('J1:N1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFC000');

        // Fila 2: Cabeceras
        $headers = [
            'A' => 'RESP.',
            'B' => 'MODELO',
            'C' => 'SERIE',
            'D' => 'TAMAÑO',
            'E' => 'FRECUENCIA',
            'F' => 'PROCESADOR',
            'G' => 'RAM',
            'H' => 'DISCO',
            'I' => 'VIDEO',
            'J' => 'P/N',
            'K' => 'STOCK',
            'L' => 'FECHA',
            'M' => 'CUAD',
            'N' => 'DM',
            'O' => 'ALC',
            'P' => 'ASIA',
            'Q' => 'unik',
            'R' => 'CUENTA ML',
        ];

        foreach ($headers as $col => $text) {
            $sheet->setCellValue("{$col}2", $text);
        }
        $sheet->getStyle('A2:R2')->getFont()->setBold(true);
        $sheet->getStyle('A2:R2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');

        $row = 3;
        $hoy = now()->startOfDay();

        foreach ($agrupado as $marca => $plataformas) {
            // Cabecera de Marca (Azul #0070C0)
            $sheet->setCellValue("B{$row}", $marca);
            $sheet->setCellValue("M{$row}", 'CUAD');
            $sheet->setCellValue("N{$row}", 'DM');
            $sheet->setCellValue("O{$row}", 'ALC');
            $sheet->setCellValue("P{$row}", 'ASIA');
            $sheet->setCellValue("R{$row}", 'CUENTA ML');
            $sheet->getStyle("B{$row}:R{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0070C0');
            $sheet->getStyle("B{$row}:R{$row}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $row++;

            foreach ($plataformas as $cpu => $gamas) {
                // Cabecera CPU (Celeste #00B0F0)
                $sheet->setCellValue("B{$row}", $cpu);
                $sheet->getStyle("B{$row}:R{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('00B0F0');
                $sheet->getStyle("B{$row}:R{$row}")->getFont()->setBold(true);
                $row++;

                foreach ($gamas as $gama => $modelos) {
                    // Cabecera Gama (Magenta #F705E3)
                    $sheet->setCellValue("B{$row}", $gama);
                    $sheet->getStyle("B{$row}:R{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F705E3');
                    $sheet->getStyle("B{$row}:R{$row}")->getFont()->setBold(true);
                    $row++;

                    /** @var LaptopSeguimiento $item */
                    foreach ($modelos as $item) {
                        $provs = $item->proveedores_data ?? [];
                        $ctas = $item->cuentas_ml_data ?? [];
                        $ctasTexto = !empty($ctas['siglas']) ? implode(' / ', $ctas['siglas']) : ($ctas['raw'] ?? '');

                        $sheet->setCellValue("A{$row}", $item->nombre_responsable);
                        $sheet->setCellValue("B{$row}", $item->modelo);
                        $sheet->setCellValue("C{$row}", $item->serie);
                        $sheet->setCellValue("D{$row}", $item->tamano);
                        $sheet->setCellValue("E{$row}", $item->frecuencia);
                        $sheet->setCellValue("F{$row}", $item->procesador);
                        $sheet->setCellValue("G{$row}", $item->ram);
                        $sheet->setCellValue("H{$row}", $item->disco);
                        $sheet->setCellValue("I{$row}", $item->video);
                        $sheet->setCellValue("J{$row}", $item->part_number);
                        $sheet->setCellValue("K{$row}", $item->stock_texto);
                        $sheet->setCellValue("L{$row}", $item->fecha_revision ? $item->fecha_revision->format('d/m/Y') : '');
                        $sheet->setCellValue("M{$row}", $provs[40]['valor'] ?? ($provs['40']['valor'] ?? ''));
                        $sheet->setCellValue("N{$row}", $provs[59]['valor'] ?? ($provs['59']['valor'] ?? ''));
                        $sheet->setCellValue("O{$row}", $provs[48]['valor'] ?? ($provs['48']['valor'] ?? ''));
                        $sheet->setCellValue("P{$row}", $provs[22]['valor'] ?? ($provs['22']['valor'] ?? ''));
                        $sheet->setCellValue("Q{$row}", $item->stock_unik);
                        $sheet->setCellValue("R{$row}", $ctasTexto);

                        // Color de fila según estado o marca
                        $bgRow = match ($item->estado_fila) {
                            'CON_STOCK'     => '66FF33',
                            'AGOTADO'       => 'FF0000',
                            'EN_TRANSITO'   => 'FFC000',
                            'DESHABILITADA' => '7030A0',
                            default         => $marca === 'ACER' ? 'BFBFBF' : ($marca === 'ASUS' ? 'F6C6AD' : null),
                        };

                        if ($bgRow) {
                            $sheet->getStyle("B{$row}:K{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bgRow);
                            if (in_array($item->estado_fila, ['AGOTADO', 'DESHABILITADA'], true)) {
                                $sheet->getStyle("B{$row}:K{$row}")->getFont()->getColor()->setRGB('FFFFFF');
                            }
                        }

                        // Fecha vencida en rosa (#FFC7CE)
                        if ($item->fecha_revision && $item->fecha_revision->lt($hoy)) {
                            $sheet->getStyle("L{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFC7CE');
                        }

                        // Celdas PROV en negro (#000000 con texto blanco)
                        foreach ([40 => 'M', 59 => 'N', 48 => 'O', 22 => 'P'] as $idP => $colP) {
                            $valP = strtoupper((string) ($provs[$idP]['valor'] ?? ($provs[(string) $idP]['valor'] ?? '')));
                            if (str_starts_with($valP, 'PROV')) {
                                $sheet->getStyle("{$colP}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('000000');
                                $sheet->getStyle("{$colP}{$row}")->getFont()->getColor()->setRGB('FFFFFF');
                            }
                        }

                        $row++;
                    }
                }
            }
        }

        foreach (range('A', 'R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Resuelve el ID de publicación de Mercado Libre (MPE...) para una laptop y cuenta específica.
     */
    public function obtenerMlItemId(LaptopSeguimiento $seg, int $idCuenta): ?string
    {
        // 1. Revisar si ya está guardado en el JSON de cuentas de la laptop
        $cData = $seg->cuentas_ml_data ?? [];
        if (!empty($cData['items'][$idCuenta])) {
            return (string) $cData['items'][$idCuenta];
        }

        // 2. Buscar en la tabla Publicacion por idProducto vinculado
        if ($seg->idProducto) {
            $sku = Publicacion::where('idProducto', $seg->idProducto)
                ->where('idCuentaPlataforma', $idCuenta)
                ->value('sku');

            if ($sku) {
                // Guardar para futuros clics rápidos
                $cData['items'][$idCuenta] = (string) $sku;
                $seg->cuentas_ml_data = $cData;
                $seg->save();

                return (string) $sku;
            }
        }

        // 3. Buscar en Publicacion por coincidencia de Part Number o Modelo en el título
        $q = Publicacion::where('idCuentaPlataforma', $idCuenta);
        $pn = trim((string) $seg->part_number);
        $mod = trim((string) $seg->modelo);

        if (strlen($pn) >= 5 || strlen($mod) >= 5) {
            $q->where(function ($sub) use ($pn, $mod) {
                if (strlen($pn) >= 5) {
                    $sub->where('titulo', 'LIKE', "%{$pn}%");
                }
                if (strlen($mod) >= 5) {
                    $sub->orWhere('titulo', 'LIKE', "%{$mod}%");
                }
            });

            $pub = $q->first();
            if ($pub && !empty($pub->sku)) {
                $cData['items'][$idCuenta] = (string) $pub->sku;
                $seg->cuentas_ml_data = $cData;
                $seg->save();

                return (string) $pub->sku;
            }
        }

        return null;
    }

    /**
     * Pausa o reactiva una laptop en Mercado Libre para una cuenta específica.
     * Si la pastilla se apaga -> 'paused'. Si se enciende -> 'active'.
     */
    public function sincronizarEstadoMercadoLibre(LaptopSeguimiento $seg, int $idCuenta, bool $activar): array
    {
        if (!config('laptop_seguimiento.ml_sync_enabled', true)) {
            return ['ok' => true, 'synced' => false, 'message' => 'Sincronización ML desactivada en configuración.'];
        }

        $cuentasCfg = config('laptop_seguimiento.cuentas_ml', []);
        $cfg = $cuentasCfg[$idCuenta] ?? null;

        if (!$cfg || empty($cfg['seller_id'])) {
            return ['ok' => true, 'synced' => false, 'message' => 'Cuenta ML no encontrada o sin seller_id.'];
        }

        $sellerId = (string) $cfg['seller_id'];
        $statusTarget = $activar ? 'active' : 'paused';
        $itemId = $this->obtenerMlItemId($seg, $idCuenta);

        if (!$itemId) {
            return [
                'ok'      => true,
                'synced'  => false,
                'message' => "Guardado localmente. No se encontró código de publicación (MPE...) para {$cfg['sigla']} en esta laptop.",
            ];
        }

        try {
            /** @var \App\Services\MercadoLibreApiService $mlApi */
            $mlApi = app(\App\Services\MercadoLibreApiService::class);
            $mlApi->updateItemStatus($sellerId, $itemId, $statusTarget);

            // Actualizar también en la tabla local Publicacion si existe
            $cleanSku = preg_replace('/^MPE/i', '', $itemId);
            Publicacion::where(function ($q) use ($itemId, $cleanSku) {
                $q->where('sku', $itemId)->orWhere('sku', $cleanSku);
            })->where('idCuentaPlataforma', $idCuenta)
              ->update(['estado' => $activar ? 1 : 0]);

            $accionTxt = $activar ? 'reactivada (activa)' : 'pausada (apagada)';

            return [
                'ok'      => true,
                'synced'  => true,
                'status'  => $statusTarget,
                'item_id' => $itemId,
                'message' => "¡Éxito! Publicación {$itemId} {$accionTxt} en Mercado Libre ({$cfg['sigla']}).",
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Fallo al actualizar publicación en ML ({$itemId}): " . $e->getMessage());

            return [
                'ok'      => true, // El estado local sí se guardó correctamente
                'synced'  => false,
                'item_id' => $itemId,
                'error'   => $e->getMessage(),
                'message' => "Guardado en sistema. Aviso ML ({$cfg['sigla']}): " . $e->getMessage(),
            ];
        }
    }
}

