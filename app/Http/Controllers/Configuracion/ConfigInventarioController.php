<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Services\ConfiguracionServiceInterface;
use App\Services\HeaderServiceInterface;
use Illuminate\Http\Request;

class ConfigInventarioController extends Controller
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

    public function inventario()
    {
        $userModel = $this->headerService->getModelUser();

        $hasEditAccess = $userModel->Accesos->contains('idVista', 10);

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                // Eager load ubicaciones for the view
                $almacenes = \App\Models\Inventario\Almacen::with('Ubicaciones')->get();
                $proveedores = $this->configuracionService->getAllProveedores();

                return view('configuracion.configinventario', [
                    'user' => $userModel,
                    'pagina' => 'inventario',
                    'almacenes' => $almacenes,
                    'proveedores' => $proveedores,
                    'hasEditAccess' => $hasEditAccess
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function createAlmacen(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $descripcion = $request->input('descripcion');
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $this->configuracionService->createAlmacen($descripcion);
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function createUbicacionAlmacen(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $idAlmacen = $request->input('idAlmacen');
        $nombre = $request->input('nombre');
        $descripcion = $request->input('descripcion');
        $num_filas = $request->input('num_filas', 1);

        $rutaFoto = null;
        if ($request->hasFile('foto')) {
            $rutaFoto = $request->file('foto')->store('racks', 'public');
        }

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $this->configuracionService->createUbicacionAlmacen($idAlmacen, $nombre, $descripcion, $rutaFoto, $num_filas);
                $this->headerService->sendFlashAlerts('Éxito', 'Ubicación añadida correctamente', 'success', 'btn-success');
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function addFila(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $idAlmacen = $request->input('idAlmacen');
        $nombre_rack = $request->input('nombre_rack');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $this->configuracionService->addFilaToRack($idAlmacen, $nombre_rack);
                $this->headerService->sendFlashAlerts('Éxito', 'Fila agregada correctamente', 'success', 'btn-success');
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function deleteFila($idUbicacionExacta)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $this->configuracionService->deleteFilaFromRack($idUbicacionExacta);
                $this->headerService->sendFlashAlerts('Éxito', 'Fila eliminada correctamente', 'success', 'btn-success');
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function deleteUbicacionAlmacen($id)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                // Posible mejora: Verificar si está en uso antes de borrar
                $this->configuracionService->deleteUbicacionAlmacen($id);
                $this->headerService->sendFlashAlerts('Éxito', 'Ubicación eliminada correctamente', 'success', 'btn-success');
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updateUbicacionAlmacen(Request $request, $id)
    {
        $userModel = $this->headerService->getModelUser();
        $nombre = $request->input('nombre');
        $descripcion = $request->input('descripcion');

        $rutaFoto = null;
        if ($request->hasFile('foto')) {
            $rutaFoto = $request->file('foto')->store('racks', 'public');
        }

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $this->configuracionService->updateUbicacionAlmacen($id, $nombre, $descripcion, $rutaFoto);
                $this->headerService->sendFlashAlerts('Éxito', 'Ubicación actualizada correctamente', 'success', 'btn-success');
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function createProveedor(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $razSocial = $request->input('razonsocial');
        $nombreComercial = $request->input('nombrecomercial');
        $ruc = $request->input('ruc');
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $this->configuracionService->createProveedor($razSocial, $nombreComercial, $ruc);
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }
}