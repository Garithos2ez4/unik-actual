<?php

namespace App\Http\Controllers\Productos;

use App\Http\Controllers\Controller;
use App\Models\Catalogo\Producto;
use App\Models\Inventario\LaptopSeguimiento;
use App\Models\Usuarios\Usuario;
use App\Services\HeaderServiceInterface;
use App\Services\LaptopSeguimientoService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaptopSeguimientoController extends Controller
{
    public function __construct(
        protected HeaderServiceInterface $headerService,
        protected LaptopSeguimientoService $service
    ) {}

    public function index(Request $request)
    {
        $user = $this->headerService->getModelUser();

        // Auto-importar Libro11.xlsx en la primera visita si la tabla está vacía
        if (LaptopSeguimiento::count() === 0 && file_exists(storage_path('Libro11.xlsx'))) {
            try {
                $this->service->importarDesdeExcel(storage_path('Libro11.xlsx'), false);
            } catch (\Throwable $e) {
                // Continuar aunque falle la auto-importación inicial
            }
        }

        $filtros = [
            'q'                  => $request->query('q', ''),
            'marca'              => $request->query('marca', ''),
            'gama'               => $request->query('gama', ''),
            'estado'             => $request->query('estado', ''),
            'idProveedor'        => $request->query('idProveedor', ''),
            'idCuentaPlataforma' => $request->query('idCuentaPlataforma', ''),
            'responsable'        => $request->query('responsable', ''),
            'solo_vencidos'      => $request->boolean('solo_vencidos'),
            'vinculo'            => $request->query('vinculo', ''),
            'mostrar_ocultos'    => $request->boolean('mostrar_ocultos'),
        ];

        $tablero = $this->service->obtenerTableroAgrupado($filtros);

        $proveedoresCfg = config('laptop_seguimiento.proveedores', []);
        $cuentasMlCfg   = config('laptop_seguimiento.cuentas_ml', []);
        $marcasCfg      = config('laptop_seguimiento.marcas', []);
        $estadosCfg     = config('laptop_seguimiento.estados', []);
        $gamasCfg       = config('laptop_seguimiento.gamas', []);

        $usuarios = Usuario::orderBy('user')->get(['idUser', 'user']);
        $productosLaptop = Producto::whereIn('idGrupo', [1, 2, 3])
            ->orderBy('nombreProducto')
            ->get(['idProducto', 'codigoProducto', 'modelo', 'partNumber', 'nombreProducto']);
        $frecuenciasSugeridas = $this->service->obtenerSugerenciasFrecuencia();

        return view('laptops.seguimiento', [
            'user'                 => $user,
            'agrupado'             => $tablero['agrupado'],
            'stats'                => $tablero['stats'],
            'responsables'         => $tablero['responsables'],
            'filtros'              => $filtros,
            'proveedoresCfg'       => $proveedoresCfg,
            'cuentasMlCfg'         => $cuentasMlCfg,
            'marcasCfg'            => $marcasCfg,
            'estadosCfg'           => $estadosCfg,
            'gamasCfg'             => $gamasCfg,
            'usuarios'             => $usuarios,
            'productosLaptop'      => $productosLaptop,
            'frecuenciasSugeridas' => $frecuenciasSugeridas,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'marca_nombre'      => 'required|string|max:60',
            'modelo'            => 'required|string|max:180',
            'serie'             => 'nullable|string|max:120',
            'tamano'            => 'nullable|string|max:40',
            'frecuencia'        => 'nullable|string|max:40',
            'procesador'        => 'nullable|string|max:120',
            'gama'              => 'nullable|string|max:60',
            'ram'               => 'nullable|string|max:40',
            'disco'             => 'nullable|string|max:40',
            'video'             => 'nullable|string|max:80',
            'part_number'       => 'nullable|string|max:120',
            'stock_texto'       => 'nullable|string|max:50',
            'stock_unik'        => 'nullable|string|max:50',
            'fecha_revision'    => 'nullable|date',
            'estado_fila'       => 'nullable|string|max:30',
            'responsable_texto' => 'nullable|string|max:60',
            'idProducto'        => 'nullable|integer',
            'observacion'       => 'nullable|string',
        ]);

        $marcaUpper = strtoupper(trim($data['marca_nombre']));
        $marcasCfg = config('laptop_seguimiento.marcas', []);
        $idMarca = $marcasCfg[$marcaUpper]['idMarca'] ?? null;

        $gama = !empty($data['gama'])
            ? $data['gama']
            : $this->service->detectarGama($data['procesador'] ?? '');
        $plataforma = $this->service->detectarPlataformaCpu($data['procesador'] ?? '', $gama);

        $proveedoresData = [
            40 => $this->service->normalizarCeldaProveedor($request->input('prov_40')),
            59 => $this->service->normalizarCeldaProveedor($request->input('prov_59')),
            48 => $this->service->normalizarCeldaProveedor($request->input('prov_48')),
            22 => $this->service->normalizarCeldaProveedor($request->input('prov_22')),
        ];

        $cuentasIds = array_map('intval', (array) $request->input('cuentas_ml', []));
        $cuentasCfg = config('laptop_seguimiento.cuentas_ml', []);
        $siglas = [];
        foreach ($cuentasIds as $cid) {
            if (isset($cuentasCfg[$cid])) {
                $siglas[] = $cuentasCfg[$cid]['sigla'];
            }
        }

        $idProducto = $data['idProducto'] ?? null;
        if (!$idProducto) {
            $indice = [];
            $idProducto = $this->service->buscarProductoVinculado($data['part_number'] ?? null, $data['modelo'], $indice);
        }

        $frecuencia = !empty($data['frecuencia']) ? trim($data['frecuencia']) : null;
        if (empty($frecuencia) && $idProducto) {
            $frecuencia = $this->service->obtenerFrecuenciaProducto($idProducto);
        }

        $idUser = null;
        if (!empty($data['responsable_texto'])) {
            $u = Usuario::whereRaw('UPPER(user) = ?', [strtoupper(trim($data['responsable_texto']))])->first();
            if ($u) {
                $idUser = $u->idUser;
            }
        }

        $seg = LaptopSeguimiento::create([
            'idProducto'        => $idProducto,
            'idMarca'           => $idMarca,
            'marca_nombre'      => $marcaUpper,
            'plataforma_cpu'    => $plataforma,
            'gama'              => $gama,
            'idUser'            => $idUser,
            'responsable_texto' => $data['responsable_texto'] ?? null,
            'modelo'            => $data['modelo'],
            'serie'             => $data['serie'] ?? null,
            'tamano'            => $data['tamano'] ?? null,
            'frecuencia'        => $frecuencia,
            'procesador'        => $data['procesador'] ?? null,
            'ram'               => $data['ram'] ?? null,
            'disco'             => $data['disco'] ?? null,
            'video'             => $data['video'] ?? null,
            'part_number'       => $data['part_number'] ?? null,
            'stock_texto'       => $data['stock_texto'] ?? null,
            'stock_unik'        => $data['stock_unik'] ?? null,
            'fecha_revision'    => $data['fecha_revision'] ?? now()->toDateString(),
            'estado_fila'       => $data['estado_fila'] ?? 'NORMAL',
            'proveedores_data'  => $proveedoresData,
            'cuentas_ml_data'   => [
                'ids'    => $cuentasIds,
                'siglas' => $siglas,
                'raw'    => implode(' / ', $siglas),
            ],
            'orden'             => ((int) LaptopSeguimiento::max('orden')) + 1,
            'observacion'       => $data['observacion'] ?? null,
        ]);

        if ($seg->idProducto) {
            $this->service->sincronizarConInventarioProveedor($seg);
        }

        return redirect()->route('laptops.seguimiento.index')->with([
            'title'   => 'Laptop registrada',
            'message' => "Se agregó {$seg->modelo} al tablero de seguimiento.",
            'icon'    => 'success',
            'button'  => 'btn btn-success',
        ]);
    }

    /**
     * Edición rápida en línea (AJAX) de cualquier celda del tablero.
     */
    public function updateInline(Request $request, int $id)
    {
        $seg = LaptopSeguimiento::findOrFail($id);
        $campo = (string) $request->input('campo');
        $valor = $request->input('valor');

        if (str_starts_with($campo, 'prov_')) {
            $idProv = (int) str_replace('prov_', '', $campo);
            $provs = $seg->proveedores_data ?? [];
            $provs[$idProv] = $this->service->normalizarCeldaProveedor((string) $valor);
            $seg->proveedores_data = $provs;
            $seg->fecha_revision = now()->toDateString();
            $seg->save();

            if ($seg->idProducto) {
                $this->service->sincronizarConInventarioProveedor($seg);
            }
        } elseif ($campo === 'toggle_cuenta_ml') {
            $idCuenta = (int) $valor;
            $cuentasCfg = config('laptop_seguimiento.cuentas_ml', []);
            $actual = $seg->cuentas_ml_data ?? ['ids' => [], 'siglas' => [], 'raw' => ''];
            $ids = array_map('intval', $actual['ids'] ?? []);

            $ahoraActivo = false;
            if (in_array($idCuenta, $ids, true)) {
                $ids = array_values(array_diff($ids, [$idCuenta]));
                $ahoraActivo = false; // Se apagó la pastilla -> pausar en ML
            } else {
                $ids[] = $idCuenta;
                $ahoraActivo = true;  // Se encendió la pastilla -> activar en ML
            }

            $siglas = [];
            foreach ($ids as $cid) {
                if (isset($cuentasCfg[$cid])) {
                    $siglas[] = $cuentasCfg[$cid]['sigla'];
                }
            }

            $cData = $actual;
            $cData['ids'] = $ids;
            $cData['siglas'] = $siglas;
            $cData['raw'] = implode(' / ', $siglas);

            $seg->cuentas_ml_data = $cData;
            $seg->save();

            // Sincronización oficial con la API de Mercado Libre (Pausar o Reactivar ítem)
            $mlResult = $this->service->sincronizarEstadoMercadoLibre($seg, $idCuenta, $ahoraActivo);
        } elseif ($campo === 'fecha_hoy') {
            $seg->fecha_revision = now()->toDateString();
            $seg->save();
        } elseif ($campo === 'fecha_revision') {
            $seg->fecha_revision = !empty($valor) ? $valor : null;
            $seg->save();
        } elseif ($campo === 'estado_fila') {
            $seg->estado_fila = (string) $valor;
            $seg->fecha_revision = now()->toDateString();
            $seg->save();
        } elseif ($campo === 'responsable_texto') {
            $txt = trim((string) $valor);
            $seg->responsable_texto = $txt !== '' ? strtoupper($txt) : null;
            $u = $txt !== '' ? Usuario::whereRaw('UPPER(user) = ?', [strtoupper($txt)])->first() : null;
            $seg->idUser = $u ? $u->idUser : null;
            $seg->save();
        } elseif ($campo === 'idProducto') {
            $seg->idProducto = !empty($valor) ? (int) $valor : null;
            if ($seg->idProducto && (empty($seg->frecuencia) || $seg->frecuencia === '-')) {
                $freqProd = $this->service->obtenerFrecuenciaProducto($seg->idProducto);
                if ($freqProd) {
                    $seg->frecuencia = $freqProd;
                }
            }
            $seg->save();
            if ($seg->idProducto) {
                $this->service->sincronizarConInventarioProveedor($seg);
            }
        } elseif (in_array($campo, ['modelo', 'serie', 'tamano', 'frecuencia', 'procesador', 'ram', 'disco', 'video', 'part_number', 'stock_texto', 'stock_unik', 'observacion'], true)) {
            $seg->{$campo} = trim((string) $valor) !== '' ? trim((string) $valor) : null;
            if ($campo === 'procesador') {
                $seg->gama = $this->service->detectarGama($seg->procesador);
                $seg->plataforma_cpu = $this->service->detectarPlataformaCpu($seg->procesador, $seg->gama);
            }
            $seg->save();
        }

        return response()->json([
            'ok'               => true,
            'id'               => $seg->idLaptopSeguimiento,
            'frecuencia'       => $seg->frecuencia,
            'estado_fila'      => $seg->estado_fila,
            'fecha_revision'   => $seg->fecha_revision ? $seg->fecha_revision->format('Y-m-d') : '',
            'fecha_formateada' => $seg->fecha_revision ? $seg->fecha_revision->format('d/m/Y') : '-',
            'vencido'          => $seg->estaVencido(),
            'proveedores_data' => $seg->proveedores_data,
            'cuentas_ml_data'  => $seg->cuentas_ml_data,
            'idProducto'       => $seg->idProducto,
            'ml'               => $mlResult ?? null,
        ]);
    }

    public function syncFrecuencias()
    {
        $actualizados = $this->service->sincronizarFrecuenciasDesdeProductos();

        return response()->json([
            'ok'           => true,
            'actualizados' => $actualizados,
            'mensaje'      => "Se completaron las frecuencias de {$actualizados} laptops desde las especificaciones de catálogo.",
        ]);
    }

    public function destroy(int $id)
    {
        $seg = LaptopSeguimiento::findOrFail($id);
        $seg->delete();

        return response()->json(['ok' => true]);
    }

    public function importarExcel(Request $request)
    {
        $filePath = null;
        if ($request->hasFile('archivo_excel')) {
            $request->validate([
                'archivo_excel' => 'required|file|mimes:xlsx,xls',
            ]);
            $filePath = $request->file('archivo_excel')->getRealPath();
        }

        $res = $this->service->importarDesdeExcel($filePath, true);

        return redirect()->route('laptops.seguimiento.index')->with([
            'title'          => 'Importación Completada',
            'message'        => "Se importaron {$res['importados']} modelos ({$res['vinculados']} vinculados automáticamente con el catálogo Producto y " . count($res['no_encontrados']) . " en seguimiento externo).",
            'icon'           => 'success',
            'button'         => 'btn btn-success',
            'no_encontrados' => $res['no_encontrados'],
        ]);
    }

    public function vincularAutomatico()
    {
        $sinVincular = LaptopSeguimiento::whereNull('idProducto')->get();
        $indice = [];
        $nuevosVinculados = 0;

        foreach ($sinVincular as $seg) {
            $idProd = $this->service->buscarProductoVinculado($seg->part_number, $seg->modelo, $indice);
            if ($idProd) {
                $seg->idProducto = $idProd;
                if (empty($seg->frecuencia) || $seg->frecuencia === '-') {
                    $freqProd = $this->service->obtenerFrecuenciaProducto($idProd);
                    if ($freqProd) {
                        $seg->frecuencia = $freqProd;
                    }
                }
                $seg->save();
                $this->service->sincronizarConInventarioProveedor($seg);
                $nuevosVinculados++;
            }
        }

        $frecuenciasActualizadas = $this->service->sincronizarFrecuenciasDesdeProductos();

        $detalles = [];
        if ($nuevosVinculados > 0) $detalles[] = "{$nuevosVinculados} modelos vinculados al catálogo";
        if ($frecuenciasActualizadas > 0) $detalles[] = "{$frecuenciasActualizadas} frecuencias (Hz) autocompletadas desde especificaciones";
        $msg = !empty($detalles) ? implode(' y ', $detalles) . '.' : 'Todos los modelos ya se encuentran al día con el catálogo.';

        return redirect()->route('laptops.seguimiento.index')->with([
            'title'   => 'Sincronización con Catálogo',
            'message' => $msg,
            'icon'    => 'info',
            'button'  => 'btn btn-primary',
        ]);
    }

    public function exportarExcel(Request $request): StreamedResponse
    {
        $filtros = [
            'q'                  => $request->query('q', ''),
            'marca'              => $request->query('marca', ''),
            'gama'               => $request->query('gama', ''),
            'estado'             => $request->query('estado', ''),
            'idProveedor'        => $request->query('idProveedor', ''),
            'idCuentaPlataforma' => $request->query('idCuentaPlataforma', ''),
            'responsable'        => $request->query('responsable', ''),
            'solo_vencidos'      => $request->boolean('solo_vencidos'),
            'vinculo'            => $request->query('vinculo', ''),
        ];

        $spreadsheet = $this->service->exportarExcel($filtros);
        $fileName = 'LAPTOP_ML_US_' . now()->format('Y-m-d_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

