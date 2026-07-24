<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LicenciaServiceInterface;
use App\Services\HeaderServiceInterface;
use App\Exports\PlantillaLicenciaExport;
use App\Imports\LicenciaImport;
use App\Models\CategoriaLicencia;
use App\Models\Licencia;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Preveedor;
use App\Models\TipoLicencia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\LicenciaUsada;
use Illuminate\Pagination\LengthAwarePaginator;


class LicenciaController extends Controller
{
    protected $licenciaService;
    protected $headerService;

    public function __construct(
        LicenciaServiceInterface $licenciaService,
        HeaderServiceInterface $headerService
    ) {

        $this->licenciaService = $licenciaService;
        $this->headerService = $headerService;
    }
    public function descargarPlantilla()
    {
        return Excel::download(new PlantillaLicenciaExport, 'plantilla_licencias.xlsx');
    }

    public function index(Request $request)
    {
        $tiposLicencias = TipoLicencia::where('estado', 1)->get();
        $proveedores = Preveedor::all();
        $user = $this->headerService->getModelUser();
        $search = $request->input('search');
        $tipo = $request->input('tipo');

        $licencias = $this->licenciaService->getLicenciasNuevasQuery()
            ->when($search, function ($query) use ($search) {
                $query->where('voucher_code', 'like', "%{$search}%");
            })
            ->when($tipo, function ($query, $tipo) {
                $query->where('id_tipo', $tipo);
            })
            ->with('tipoLicencia')
            ->orderByDesc('id')
            ->paginate(10)
            ->appends($request->all());

        $totalesPorTipo = Licencia::select('id_tipo', DB::raw('COUNT(*) as total'))
            ->where('estado', 'NUEVA')
            ->groupBy('id_tipo')
            ->with('tipoLicencia')
            ->get();

        if ($request->query('page') || $request->query('container')) {
            $view = view('components.Licencias.lista_licencias', [
                'licencias' => $licencias,
                'container' => $request->query('container', 'container-list-licencias')
            ])->render();

            return response()->json(['html' => $view]);
        }

        return view('licencias.index', [
            'licencias' => $licencias,
            'user' => $user,
            'search' => $search,
            'proveedores' => $proveedores,
            'tiposLicencias' => $tiposLicencias,
            'tipoSeleccionado' => $tipo,
            'totalesPorTipo' => $totalesPorTipo
        ]);
    }

    public function create()
    {
        $user = $this->headerService->getModelUser();
        $tiposLicencia = TipoLicencia::where('estado', 1)->get();
        $categoria = CategoriaLicencia::all();
        $proveedores = Preveedor::all();
        return view('licencias.create', compact('user', 'tiposLicencia', 'proveedores','categoria'));
    }

    public function store(Request $request)
    {

            $request->validate([
            'voucher_code' => 'required|string|max:100|unique:licencias,voucher_code',
            'id_tipo'      => 'required|exists:tipo_licencia,id',
            'idProveedor'  => 'required|exists:Preveedor,idProveedor', // 👈 validar proveedor
            'orden_compra' => 'nullable|string|max:100',
            'cantidad_usos' => 'nullable|int'
        ], [
            'voucher_code.required' => 'Debes ingresar el código de la licencia.',
            'voucher_code.unique'   => 'Este código ya existe.',
            'id_tipo.required'      => 'Debes seleccionar el tipo de licencia.',
            'idProveedor.required'  => 'Debes seleccionar un proveedor.',
             'cantidad_usos'         => 'Test'
        ]);

        $this->licenciaService->crearLicencia($request->all());

        return redirect()->route('licencias.index')->with('success', 'Licencia registrada correctamente.');
    }

    public function vistaImportar()
    {
        $user = $this->headerService->getModelUser();

        return view('licencias.importar', [
            'user' => $user,
            'tiposLicencia' => TipoLicencia::where('estado', 1)->get(),
            'proveedores' => Preveedor::all(),
            'categorias' => CategoriaLicencia::all(),
        ]);
    }

    public function importarExcel(Request $request)
    {
        $user = $this->headerService->getModelUser();

        $request->validate([
            'archivo'     => 'required|file|mimes:xlsx,xls|max:5120',
            'id_tipo'     => 'required|exists:tipo_licencia,id',
            'idProveedor' => 'required|exists:Preveedor,idProveedor',
        ], [
            'archivo.required'     => 'Debes adjuntar un archivo Excel.',
            'archivo.mimes'        => 'Solo se aceptan archivos .xlsx o .xls.',
            'archivo.max'          => 'El archivo no debe superar 5MB.',
            'id_tipo.required'     => 'Debes seleccionar el tipo de licencia.',
            'idProveedor.required' => 'Debes seleccionar un proveedor.',
        ]);

        $datosFijos = $request->only([
            'id_tipo',
            'idProveedor',
            'id_categoria',
            'orden_compra',
            'cantidad_usos',
        ]);

        try {
            $import = new LicenciaImport();
            Excel::import($import, $request->file('archivo'));

            $preview = $import->rows
                ->map(fn($row) => ['voucher_code' => trim($row['voucher_code'] ?? '')])
                ->filter(fn($r) => !empty($r['voucher_code']))
                ->values();

            if ($preview->isEmpty()) {
                return back()
                    ->withInput()
                    ->with('import_error', 'El archivo no contiene ningún voucher_code válido. Asegúrate de que la columna se llame exactamente "voucher_code".');
            }

            // Detectar duplicados en la BD
            $existentes = \App\Models\Licencia::whereIn('voucher_code', $preview->pluck('voucher_code'))->pluck('voucher_code');
            $duplicados = $existentes->count();

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            return back()->withInput()->with('import_error', 'Error en la validación del Excel: ' . collect($e->failures())->map(fn($f) => $f->errors()[0])->implode(', '));
        } catch (\Exception $e) {
            \Log::error('Error importando Excel de licencias: ' . $e->getMessage());
            return back()->withInput()->with('import_error', 'No se pudo leer el archivo. Verifica que sea un Excel válido y que tenga la columna "voucher_code".');
        }

        return view('licencias.importar', [
            'user'             => $user,
            'previewLicencias' => $preview,
            'datosFijos'       => $datosFijos,
            'duplicadosCount'  => $duplicados,
            'tiposLicencia'    => TipoLicencia::where('estado', 1)->get(),
            'proveedores'      => Preveedor::all(),
            'categorias'       => CategoriaLicencia::all(),
        ]);
    }

    public function confirmarImportacion(Request $request)
    {
        $licencias = json_decode(base64_decode($request->licencias), true);

        if (empty($licencias)) {
            return redirect()->route('licencias.importar.vista')
                ->with('import_error', 'No se recibieron licencias para importar.');
        }

        $importadas = 0;
        $omitidas   = 0;

        \DB::beginTransaction();
        try {
            foreach ($licencias as $licencia) {
                $code = trim($licencia['voucher_code'] ?? '');
                if (empty($code)) { $omitidas++; continue; }

                // Omitir duplicados sin explotar
                if (\App\Models\Licencia::where('voucher_code', $code)->exists()) {
                    $omitidas++;
                    continue;
                }

                Licencia::create([
                    'voucher_code'  => $code,
                    'id_tipo'       => $request->id_tipo,
                    'idProveedor'   => $request->idProveedor,
                    'id_categoria'  => $request->id_categoria,
                    'orden_compra'  => $request->orden_compra,
                    'cantidad_usos' => $request->cantidad_usos ?? 1,
                    'estado'        => 'NUEVA',
                ]);
                $importadas++;
            }
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error confirmando importación de licencias: ' . $e->getMessage());
            return redirect()->route('licencias.importar.vista')
                ->with('import_error', 'Ocurrió un error al guardar: ' . $e->getMessage());
        }

        $msg = "Se importaron {$importadas} licencia(s) correctamente.";
        if ($omitidas > 0) {
            $msg .= " Se omitieron {$omitidas} por estar duplicadas o vacías.";
        }

        return redirect()->route('licencias.index')->with('success', $msg);
    }
    public function showFormularioEstado($serial, $nuevoEstado)
    {
        $user = $this->headerService->getModelUser();
        $licencia = $this->licenciaService->buscarLicenciaPorCodigo($serial);

        return view("licencias.estados.formulario_{$nuevoEstado}", compact('licencia', 'user'));
    }

    public function cambiarEstado(Request $request, $serial)
    {
        $request->validate([
            'nuevo_estado' => 'required|in:USADA,DEFECTUOSA,RECUPERADA',
        ]);

        if ($request->input('nuevo_estado') === 'USADA') {
            $request->validate([
            'modo_uso' => 'nullable|in:PARCIAL,COMPLETO',
        ]);
    }


        $datosForm = $request->except('nuevo_estado');
        if ($request->hasFile('archivo')) {
            $datosForm['archivo'] = $request->file('archivo');
        }
        $nuevoEstado = $request->input('nuevo_estado');

        $this->licenciaService->cambiarEstadoConFormulario($serial, $nuevoEstado, $datosForm);

        $mensaje = match($nuevoEstado) {
            'USADA' => 'Licencia marcada como usada correctamente.',
            'DEFECTUOSA' => 'Licencia marcada como defectuosa correctamente.',
            'RECUPERADA' => 'Licencia marcada como recuperada correctamente.',
            default => 'Estado de licencia actualizado.'
        };

        return redirect()->route('licencias.index')->with('success', $mensaje);
    }

    public function usadas(Request $request)
    {
        $user = $this->headerService->getModelUser();
        $search = $request->input('search');

        $licencias = LicenciaUsada::with('licencia.tipoLicencia')
            ->when($search, function ($query, $search) {
                $query->where('serial_equipo', 'like', "%{$search}%")
                    ->orWhere('clave_key', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->get();

        // 🔹 Agrupación condicional
        $licenciasAgrupadas = $licencias->groupBy(function ($item) {

            if ($item->licencia && $item->licencia->esMultifuncional()) {
                return 'multi_' . $item->serial_equipo;
            }

            return 'uni_' . $item->id;
        });

        // 🔹 Convertimos a colección plana de grupos
        $grupos = $licenciasAgrupadas->values();

        // 🔹 Paginación manual
        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $grupos->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginado = new LengthAwarePaginator(
            $currentItems,
            $grupos->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('licencias.usadas', [
            'licenciasAgrupadas' => $paginado,
            'user' => $user,
            'search' => $search
        ]);
    }

    public function defectuosas()
    {
        $user = $this->headerService->getModelUser();

        $licenciasDefectuosas = \App\Models\LicenciaDefectuosa::with('licencia.tipoLicencia','licencia.proveedor')
            ->where('estado', 'DEFECTUOSA')
            ->orderByDesc('id')
            ->paginate(10);

        return view('licencias.defectuosas', [
            'licenciasDefectuosas' => $licenciasDefectuosas,
            'user' => $user
        ]);
    }
    public function recuperadas()
    {
        $user = $this->headerService->getModelUser();

        $licenciasRecuperadas = \App\Models\LicenciaRecuperada::with('licencia','licencia.proveedor')
            ->where('estado', 'RECUPERADA')
            ->orderByDesc('id')
            ->paginate(10);

        return view('licencias.recuperadas', [
            'licenciasRecuperadas' => $licenciasRecuperadas,
            'user' => $user
        ]);
    }

    public function storeTipoLicencia(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_licencia,nombre',
        ]);

        $tipo = \App\Models\TipoLicencia::create([
            'nombre' => $request->nombre,
            'estado' => 1
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'id'      => $tipo->id,
                'nombre'  => $tipo->nombre,
            ]);
        }

        return redirect()->back()->with('success', 'Tipo de licencia agregado correctamente');
    }

    public function getAllTipos()
    {
        $tipos = \App\Models\TipoLicencia::orderBy('nombre')->get();
        return response()->json($tipos);
    }

    public function toggleTipoEstado(Request $request)
    {
        $tipo = \App\Models\TipoLicencia::find($request->id);
        if ($tipo) {
            $tipo->estado = $request->estado ? 1 : 0;
            $tipo->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'Tipo no encontrado']);
    }
    public function descargarLicencia($id)
    {
        $licencia = LicenciaUsada::findOrFail($id);

        if ($licencia->archivo && Storage::disk('public')->exists($licencia->archivo)) {

            return response()->download(
                Storage::disk('public')->path($licencia->archivo),
                basename($licencia->archivo)
            );
        }

        return back()->with('error', 'El archivo no existe físicamente.');
    }

}
