<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Services\ConfiguracionServiceInterface;
use App\Services\HeaderServiceInterface;
use Illuminate\Http\Request;

class ConfigWebController extends Controller
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

    public function web()
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $empresas = $this->configuracionService->getAllEmpresas();
                $bancos = \App\Models\Empresa\Banco::all();
                $metodosPago = \App\Models\Ventas\MetodoPago::with('TipoMetodoPago', 'Banco')->get();
                $tiposMetodoPago = \App\Models\Ventas\TipoMetodoPago::all();

                // Leer clave actual del archivo DeltronScraperService.php
                $deltronFile = app_path('Services/DeltronScraperService.php');
                $deltronContent = file_get_contents($deltronFile);
                preg_match("/protected \\\$password\s*=\s*'(.*?)';/", $deltronContent, $matches);
                $claveDeltronActual = $matches[1] ?? '';

                return view('configuracion.configweb', [
                    'user' => $userModel,
                    'pagina' => 'web',
                    'empresas' => $empresas,
                    'bancos' => $bancos,
                    'metodosPago' => $metodosPago,
                    'tiposMetodoPago' => $tiposMetodoPago,
                    'claveDeltronActual' => $claveDeltronActual
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updateCorreos(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $correos = $request->input('correos');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (!empty($correos)) {
                    foreach ($correos as $idEmpresa => $correo) {
                        $this->configuracionService->updateCorreoEmpresa($idEmpresa, $correo);
                    }

                    return back();
                } else {
                    $this->headerService->sendFlashAlerts('Error', 'Hubo un error en la operacion', 'error', 'btn-danger');
                    return back()->withInput();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updateCuentasBancarias(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $idCuenta = $request->input('id');
        $titular = $request->input('titular');
        $numeroCuenta = $request->input('cuenta');
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if ($idCuenta && $titular && $numeroCuenta) {
                    $this->configuracionService->updateCuentaBancaria($idCuenta, $titular, $numeroCuenta);
                    return back();
                } else {
                    $this->headerService->sendFlashAlerts('Faltan Datos', 'Faltan datos para completar las transaccion', 'warning', 'btn-warning');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updateClaveDeltron(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if ($request->has('claveDeltron')) {
                    $nuevaClave = $request->input('claveDeltron');
                    $deltronFile = app_path('Services/DeltronScraperService.php');
                    $deltronContent = file_get_contents($deltronFile);
                    $deltronContent = preg_replace("/protected \\\$password\s*=\s*'.*?';/", "protected \$password = '{$nuevaClave}';", $deltronContent);
                    file_put_contents($deltronFile, $deltronContent);
                }
                
                $this->headerService->sendFlashAlerts('Correcto', 'Contraseña de Deltron actualizada correctamente', 'success', 'btn-success');
                return redirect()->route('configweb', ['user' => $userModel]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function insertCuentasBancarias(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $data = [
            'idEmpresa' => $request->input('idEmpresa'),
            'idBanco' => $request->input('idBanco'),
            'tipoCuenta' => $request->input('tipoCuenta'),
            'tipoMoneda' => $request->input('tipoMoneda'),
            'titular' => $request->input('titular'),
            'numeroCuenta' => $request->input('cuenta')
        ];

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if ($data['idEmpresa'] && $data['idBanco'] && $data['tipoCuenta'] && $data['tipoMoneda'] && $data['titular'] && $data['numeroCuenta']) {
                    $this->configuracionService->createCuentaBancaria($data);
                    return back();
                } else {
                    $this->headerService->sendFlashAlerts('Faltan Datos', 'Faltan datos para completar las transaccion', 'warning', 'btn-warning');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function insertMetodoPago(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $data = [
            'idTipoMetodo' => $request->input('idTipoMetodo'),
            'idBanco' => $request->input('idBanco') ?: null,
            'nombreMetodo' => $request->input('nombreMetodo'),
            'estado' => 1
        ];

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if ($data['nombreMetodo'] && $data['idTipoMetodo']) {
                    $this->configuracionService->createMetodoPago($data);
                    $this->headerService->sendFlashAlerts('Éxito', 'Método de pago agregado correctamente', 'success', 'btn-success');
                    return back();
                } else {
                    $this->headerService->sendFlashAlerts('Faltan Datos', 'El nombre y tipo de método son obligatorios', 'warning', 'btn-warning');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function insertTipoMetodoPago(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $data = [
            'nombreTipo' => $request->input('nombreTipo')
        ];

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if ($data['nombreTipo']) {
                    $this->configuracionService->createTipoMetodoPago($data);
                    $this->headerService->sendFlashAlerts('Éxito', 'Tipo de método agregado correctamente', 'success', 'btn-success');
                    return back();
                } else {
                    $this->headerService->sendFlashAlerts('Faltan Datos', 'El nombre del tipo es obligatorio', 'warning', 'btn-warning');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updateMetodoPago(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $id = $request->input('idMetodoPago');
        $data = [
            'idTipoMetodo' => $request->input('idTipoMetodo'),
            'idBanco' => $request->input('idBanco') ?: null,
            'nombreMetodo' => $request->input('nombreMetodo'),
            'estado' => $request->has('estado') ? 1 : 0
        ];

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if ($id && $data['nombreMetodo'] && $data['idTipoMetodo']) {
                    $this->configuracionService->updateMetodoPago($id, $data);
                    $this->headerService->sendFlashAlerts('Éxito', 'Método de pago actualizado correctamente', 'success', 'btn-success');
                    return back();
                } else {
                    $this->headerService->sendFlashAlerts('Faltan Datos', 'El nombre y tipo de método son obligatorios', 'warning', 'btn-warning');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updateTipoMetodoPago(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $id = $request->input('idTipoMetodo');
        $data = [
            'nombreTipo' => $request->input('nombreTipo')
        ];

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if ($id && $data['nombreTipo']) {
                    $this->configuracionService->updateTipoMetodoPago($id, $data);
                    $this->headerService->sendFlashAlerts('Éxito', 'Tipo de método actualizado correctamente', 'success', 'btn-success');
                    return back();
                } else {
                    $this->headerService->sendFlashAlerts('Faltan Datos', 'El nombre del tipo es obligatorio', 'warning', 'btn-warning');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }
}