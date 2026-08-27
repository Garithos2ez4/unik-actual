<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Services\ConfiguracionServiceInterface;
use App\Services\HeaderServiceInterface;
use Illuminate\Http\Request;

class ConfigEspecificacionesController extends Controller
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

    public function especificaciones($idCategoria)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $categorias = $this->configuracionService->getAllCategorias();
                $categoria = $this->configuracionService->getOneCategoria(decrypt($idCategoria));
                $spects = $this->configuracionService->getAllEspecificaciones();

                return view('configuracion.configespecificaciones', [
                    'user' => $userModel,
                    'pagina' => 'especificaciones',
                    'categorias' => $categorias,
                    'categoria' => $categoria,
                    'caracteristicas' => $spects
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function especificacionesGrupo($idCategoria)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $categorias = $this->configuracionService->getAllCategorias();
                $categoria = $this->configuracionService->getOneCategoria(decrypt($idCategoria));
                $spects = $this->configuracionService->getAllEspecificaciones();
                $subDivide = 'GRUPOS';

                return view('configuracion.configespecificaciones-grupo', [
                    'user' => $userModel,
                    'pagina' => 'especificaciones',
                    'categorias' => $categorias,
                    'categoria' => $categoria,
                    'caracteristicas' => $spects,
                    'subDivide' => $subDivide
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function especificacionesGeneral()
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $spects = $this->configuracionService->getAllEspecificaciones();
                $subDivide = 'GENERAL';

                return view('configuracion.configespecificaciones-general', [
                    'user' => $userModel,
                    'pagina' => 'especificaciones',
                    'caracteristicas' => $spects,
                    'subDivide' => $subDivide
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function createCaracteristica(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $descripcion = $request->input('descripcion');
        $tipo = $request->input('tipo');
        $sugerencias = $request->input('createsugerencia');
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if ($descripcion) {
                    $this->configuracionService->createCaracteristica($descripcion, $tipo, $sugerencias);
                    $this->headerService->sendFlashAlerts('Especificacion creada', $descripcion . ' creada correctamente.', 'success', 'btn-success');
                    return back();
                } else {
                    $this->headerService->sendFlashAlerts('Faltan datos', 'Ingresa datos validos', 'warning', 'btn-danger');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updateCaracteristica(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $operacion = $request->input('operacion');
        $idCaracteristica = $request->input('id');
        $tipo = $request->input('tipo');
        $updateSugerencias = $request->input('updatesugerencia');
        $createSugerencias = $request->input('createsugerencia');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if ($operacion == 'DELETE') {
                    if (isset($idCaracteristica)) {
                        $this->configuracionService->removeCaracteristica($idCaracteristica);
                        $this->headerService->sendFlashAlerts('Operacion exitosa', 'Datos eliminados correctamente', 'success', 'btn-success');
                        return back();
                        $this->headerService->sendFlashAlerts('Error en la operacion', 'No puedes eliminar una especificacion si esta en uso.', 'warning', 'btn-danger');
                        return back();
                    }

                    $this->headerService->sendFlashAlerts('Ocurrio un error', 'Hubo un error de operacion intentalo más tarde', 'warning', 'btn-danger');
                    return back();
                } else if ($operacion == 'UPDATE') {
                    if (isset($idCaracteristica) && isset($tipo)) {
                        $this->configuracionService->updateOrCreateCaracteristica($idCaracteristica, $tipo, $updateSugerencias, $createSugerencias);
                        $this->headerService->sendFlashAlerts('Operacion exitosa', 'Datos actualizados correctamente', 'success', 'btn-success');
                        return back();
                    }
                    $this->headerService->sendFlashAlerts('Ocurrio un error', 'Hubo un error de operacion intentalo más tarde', 'warning', 'btn-danger');
                    return back();
                } else {
                    $this->headerService->sendFlashAlerts('Ocurrio un error', 'Hubo un error de operacion intentalo más tarde', 'warning', 'btn-danger');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function removeSugerencia(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $sugerencia = $request->input('sugerencia');
        $type = $request->input('type');
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (isset($sugerencia)) {
                    $model = $this->configuracionService->removeSugerencia($sugerencia, $type);
                    return response()->json($model);
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function insertCaracteristicaXGrupo(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $idGrupo = $request->input('grupo');
        $idCaracteristica = $request->input('caracteristica');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if ($idGrupo && $idCaracteristica) {
                    $model = $this->configuracionService->insertCaracteristicaXGrupo($idGrupo, $idCaracteristica);
                    return response()->json($model->load('GrupoProducto', 'Caracteristicas'));
                } else {
                    $this->headerService->sendFlashAlerts('Faltan datos', 'Ingresa datos validos', 'warning', 'btn-danger');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function deleteCaracteristicaXGrupo(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $idCaracteristica = $request->input('caracteristica');
        $idGrupo = $request->input('grupo');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if ($idCaracteristica && $idGrupo) {
                    $this->configuracionService->deleteCaracteristicaXGrupo($idGrupo, $idCaracteristica);
                    return response()->json('Eliminación exitosa');
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }
}