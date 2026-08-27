<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Services\ConfiguracionServiceInterface;
use App\Services\HeaderServiceInterface;
use Illuminate\Http\Request;

class ConfigProductosController extends Controller
{
    protected $headerService;
    protected $configuracionService;

    public function __construct(
        HeaderServiceInterface $headerService,
        ConfiguracionServiceInterface $configuracionService
    ) {
        $this->headerService = $headerService;
        $this->configuracionService = $configuracionService;
    }

    public function productos()
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $categorias = $this->configuracionService->getAllCategorias();
                $marcas = $this->configuracionService->getAllMarcas();
                $tipos = $this->configuracionService->getAllTipoProductos();
                $alertas = \Illuminate\Support\Facades\DB::table('alerta_precios')->get();
                $vigilados = \App\Models\Ecommerce\ProductoVigiladoFalabella::all();
                $mappers = \App\Models\Catalogo\PlataformaMapper::with(['plataforma', 'categoria', 'grupoProducto'])->get();
                $plataformas = \App\Models\Empresa\Plataforma::all();

                return view('configuracion.configproductos', [
                    'user' => $userModel,
                    'pagina' => 'productos',
                    'categorias' => $categorias,
                    'marcas' => $marcas,
                    'tipos' => $tipos,
                    'alertas' => $alertas,
                    'vigilados' => $vigilados,
                    'mappers' => $mappers,
                    'plataformas' => $plataformas
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function insertMapper(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'idPlataforma' => 'required|integer',
            'idCategoria' => 'nullable|integer',
            'idGrupoProducto' => 'nullable|integer',
            'tipo_template' => 'required|string|in:express,completo',
            'mapper_class' => 'nullable|string'
        ]);

        if (empty($request->idCategoria) && empty($request->idGrupoProducto)) {
            $this->headerService->sendFlashAlerts('Error', 'Debes seleccionar al menos una Categoría o un Grupo.', 'error', 'btn-danger');
            return back();
        }

        // Limpiamos los "::class" que el usuario pudiera poner por accidente o si la copia tal cual
        $mapperClass = $request->mapper_class;
        if ($mapperClass) {
            $mapperClass = str_replace('::class', '', $mapperClass);
        }

        \App\Models\Catalogo\PlataformaMapper::create([
            'idPlataforma' => $request->idPlataforma,
            'idCategoria' => $request->idCategoria ?: null,
            'idGrupoProducto' => $request->idGrupoProducto ?: null,
            'tipo_template' => $request->tipo_template,
            'mapper_class' => $mapperClass ?: null,
        ]);

        $this->headerService->sendFlashAlerts('Éxito', 'Mapper guardado correctamente.', 'success', 'btn-success');
        return back();
    }

    public function deleteMapper($id)
    {
        $mapper = \App\Models\Catalogo\PlataformaMapper::findOrFail($id);
        $mapper->delete();

        $this->headerService->sendFlashAlerts('Éxito', 'Mapper eliminado correctamente.', 'success', 'btn-success');
        return back();
    }

    public function createMarcaProducto(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $nombre = $request->input('nombre');
        $img = $request->file('img');
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (isset($nombre) && isset($img)) {
                    try {
                        $this->configuracionService->createMarcaProducto($nombre, $img);
                        $this->headerService->sendFlashAlerts('Éxito', 'Marca creada correctamente', 'success', 'btn-success');
                    } catch (\Exception $e) {
                        $this->headerService->sendFlashAlerts('Error', 'La marca ya existe o hubo un problema: ' . $e->getMessage(), 'warning', 'btn-danger');
                    }
                }
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function createGrupoProducto(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $categoria = $request->input('categoria');
        $grupo = $request->input('grupo');
        $img = $request->file('img');
        $tipo = $request->input('tipo');
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (isset($categoria) && isset($grupo) && isset($img) && isset($tipo)) {
                    $this->configuracionService->createGrupoProducto($categoria, $grupo, $tipo, $img);
                }
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updateGrupoProducto(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $id = $request->input('idGrupo');
        $nombre = $request->input('grupo');
        $tipo = $request->input('tipo');
        $img = $request->file('img');
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (isset($id) && isset($nombre) && isset($tipo)) {
                    $this->configuracionService->updateGrupoProducto($id, $nombre, $tipo, $img);
                }
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function createCategoriaProducto(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $nombre = $request->input('nombreCategoria');
        $icon = $request->input('iconCategoria');
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (isset($nombre)) {
                    $this->configuracionService->createCategoriaProducto($nombre, $icon);
                }
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updateCategoriaProducto(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $id = $request->input('idCategoria');
        $nombre = $request->input('nombreCategoria');
        $icon = $request->input('iconCategoria');
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (isset($id) && isset($nombre)) {
                    $this->configuracionService->updateCategoriaProducto($id, $nombre, $icon);
                }
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }
}