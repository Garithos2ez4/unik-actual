<?php

namespace App\Http\Controllers;

use App\Services\CalculadoraServiceInterface;
use App\Services\ConfiguracionServiceInterface;
use App\Services\HeaderServiceInterface;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    protected $headerService;
    protected $calculadoraService;
    protected $configuracionService;

    public function __construct(
        HeaderServiceInterface $headerService,
        CalculadoraServiceInterface $calculadoraService,
        ConfiguracionServiceInterface $configuracionService
    ) {
        $this->headerService = $headerService;
        $this->calculadoraService = $calculadoraService;
        $this->configuracionService = $configuracionService;
    }
    public function web()
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $empresas = $this->configuracionService->getAllEmpresas();
                $bancos = \App\Models\Banco::all();
                $metodosPago = \App\Models\MetodoPago::with('TipoMetodoPago', 'Banco')->get();
                $tiposMetodoPago = \App\Models\TipoMetodoPago::all();

                return view('configweb', [
                    'user' => $userModel,
                    'pagina' => 'web',
                    'empresas' => $empresas,
                    'bancos' => $bancos,
                    'metodosPago' => $metodosPago,
                    'tiposMetodoPago' => $tiposMetodoPago
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function calculos()
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $categorias = $this->configuracionService->getAllCategorias();
                $rangos = $this->configuracionService->getAllRangos();

                $calculos = $this->calculadoraService->get();

                $calculosFijo = $this->calculadoraService->getTasaFija();

                $empresas = $this->configuracionService->getAllEmpresas();

                $plataformas = $this->configuracionService->getAllPlataformas();


                return view('configcalculos', [
                    'user' => $userModel,
                    'pagina' => 'calculos',
                    'empresas' => $empresas,
                    'calculos' => $calculos,
                    'calculosfijo' => $calculosFijo,
                    'categorias' => $categorias,
                    'rangos' => $rangos,
                    'plataformas' => $plataformas
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function inventario()
    {
        $userModel = $this->headerService->getModelUser();

        $hasEditAccess = $userModel->Accesos->contains('idVista', 10);

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                // Eager load ubicaciones for the view
                $almacenes = \App\Models\Almacen::with('Ubicaciones')->get();
                $proveedores = $this->configuracionService->getAllProveedores();

                return view('configinventario', [
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

    public function productos()
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $categorias = $this->configuracionService->getAllCategorias();
                $marcas = $this->configuracionService->getAllMarcas();
                $tipos = $this->configuracionService->getAllTipoProductos();

                return view('configproductos', [
                    'user' => $userModel,
                    'pagina' => 'productos',
                    'categorias' => $categorias,
                    'marcas' => $marcas,
                    'tipos' => $tipos
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function especificaciones($idCategoria)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $categorias = $this->configuracionService->getAllCategorias();
                $categoria = $this->configuracionService->getOneCategoria(decrypt($idCategoria));
                $spects = $this->configuracionService->getAllEspecificaciones();

                return view('configespecificaciones', [
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

                return view('configespecificaciones-grupo', [
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

                return view('configespecificaciones-general', [
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

    /**
     * Aplica una plantilla de comisiones uniforme a todos los grupos de una categoría.
     * Template basado en la estructura estándar (9 rangos ordenados).
     */
    public function aplicarComisionUniforme(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $idCategoria = $request->input('idCategoria');
                $comisionesTemplate = $request->input('comision'); // array [idRango => valor]

                if (empty($idCategoria) || empty($comisionesTemplate)) {
                    $this->headerService->sendFlashAlerts('Error', 'Datos incompletos', 'error', 'btn-danger');
                    return back();
                }

                $grupos = \App\Models\GrupoProducto::where('idCategoria', $idCategoria)->get();

                foreach ($grupos as $grupo) {
                    foreach ($comisionesTemplate as $idRango => $comision) {
                        $this->configuracionService->updateComisionValue($grupo->idGrupoProducto, $idRango, $comision);
                    }
                }

                $this->headerService->sendFlashAlerts(
                    'Comisiones actualizadas',
                    'Se aplicó la plantilla uniforme a todos los grupos de la categoría.',
                    'success',
                    'btn-success'
                );
                return back();
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updateComision(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $comisiones = $request->input('comision');
        $grupo = $request->input('grupo');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (!empty($comisiones) && !empty($grupo)) {
                    foreach ($comisiones as $rango => $comision) {
                        $this->configuracionService->updateComisionValue($grupo, $rango, $comision);
                    }
                    $this->headerService->sendFlashAlerts('Actualizacion correcta', 'Operacion realizada con exito', 'success', 'btn-success');
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

    public function updateCalculosTasaFija(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $igv = $request->input('igv');
        $facturacion = $request->input('facturacion');
        $tasaCambio = $request->input('tasaCambio');
        $empresas = $request->input('empresas');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (!empty($igv) && !empty($facturacion) && !empty($tasaCambio) && !empty($empresas)) {
                    $this->configuracionService->updateCalculadoraTasaFija($igv, $facturacion, $tasaCambio);

                    foreach ($empresas as $idEmpresa => $comision) {
                        $this->configuracionService->updateComisionEmpresa($idEmpresa, $comision);
                    }

                    return back()->withInput();
                } else {
                    $this->headerService->sendFlashAlerts('Error', 'Hubo un error en la operacion', 'error', 'btn-danger');
                    return back()->withInput();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updateCalculos(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $igv = $request->input('igv');
        $facturacion = $request->input('facturacion');
        $empresas = $request->input('empresas');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (!empty($igv) && !empty($facturacion) && !empty($empresas)) {
                    $this->configuracionService->updateCalculadora($igv, $facturacion);

                    foreach ($empresas as $idEmpresa => $comision) {
                        $this->configuracionService->updateComisionEmpresa($idEmpresa, $comision);
                    }

                    return back()->withInput();
                } else {
                    $this->headerService->sendFlashAlerts('Error', 'Hubo un error en la operacion', 'error', 'btn-danger');
                    return back()->withInput();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
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

    public function createComisionPlataforma(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $idPlataforma = $request->input('plataforma');
        $comision = $request->input('comision');
        $flete = $request->input('flete');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (isset($idPlataforma) && isset($comision) && isset($flete)) {
                    $this->configuracionService->createComisionPlataforma($idPlataforma, $comision, $flete);
                }
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function deleteComisionPlataforma(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $idComisionPlataforma = $request->input('id');
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (isset($idComisionPlataforma)) {
                    $this->configuracionService->deleteComisionPlataforma($idComisionPlataforma);
                }
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
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
