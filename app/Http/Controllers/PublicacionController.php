<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\HeaderServiceInterface;
use App\Services\PublicacionServiceInterface;

class PublicacionController extends Controller
{
    protected $headerService;
    protected $publicacionService;

    public function __construct(
        HeaderServiceInterface $headerService,
        PublicacionServiceInterface $publicacionService
    ) {
        $this->headerService = $headerService;
        $this->publicacionService = $publicacionService;
    }

    public function index($date)
    {
        if ($date === 'buscar') {
            return $this->buscar(request(), app(\App\Repositories\PublicacionRepositoryInterface::class));
        }

        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 1) {
                Carbon::setLocale('es');
                $carbonMonth = Carbon::createFromFormat('Y-m', $date);
                $publicaciones = $this->publicacionService->getAllPubliByMonth($date);
                $plataformas = $this->publicacionService->getAllPlatafromasByTipe('TIENDA');

                return view('publicaciones.publicaciones', [
                    'user' => $userModel,
                    'publicaciones' => $publicaciones,
                    'plataformas' => $plataformas,
                    'fecha' => $carbonMonth
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function create($idPlataforma)
    {
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 1) {
                $plataforma = $this->publicacionService->getOneByPlataformaId(decrypt($idPlataforma));
                return view('createpublicacion', [
                    'user' => $userModel,
                    'plataforma' => $plataforma
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function insertPublicacion(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $titulo = $request->input('titulo');
        $sku = $request->input('sku');
        $fecha = $request->input('fecha');
        $cuenta = $request->input('cuenta');
        $idproducto = $request->input('idproducto');
        $precio = $request->input('precio');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 1) {
                if ($cuenta && $precio && $titulo && $fecha && $sku) {
                    if ($idproducto) {
                        $data = [
                            'idCuentaPlataforma' => $cuenta,
                            'idProducto' => $idproducto,
                            'sku' => $sku,
                            'titulo' => $titulo,
                            'fechaPublicacion' => $fecha,
                            'precioPublicacion' => $precio
                        ];

                        $message = $this->publicacionService->insertPublicacion($data);
                        $this->headerService->sendFlashAlerts('Producto no encontrado', '', 'info', 'btn-success');
                        return $message['transaccion'] ?
                            redirect()->route('publicaciones', [now()->format('Y-m')]) :
                            back()->withInput();
                    } else {
                        $this->headerService->sendFlashAlerts('Producto no encontrado', 'Revisa los datos acerca del producto.', 'info', 'btn-warning');
                        return back()->withInput();
                    }
                } else {
                    $this->headerService->sendFlashAlerts('Datos vacios', 'Ingresa datos en el formulario.', 'info', 'btn-warning');
                    return back()->withInput();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updateEstado(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $id = $request->input('idpubli');
        $titulo = $request->input('titulo');
        $precio = $request->input('precio');
        $estado = $request->input('estado');
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 1) {
                if (isset($id) && isset($titulo) && isset($precio) && isset($estado)) {
                    $message = $this->publicacionService->updatePublicacion($id, $titulo, $precio, $estado);
                    $this->headerService->sendFlashAlerts($message, ' ', 'warning', 'btn-success');
                    return back();
                } else {
                    $this->headerService->sendFlashAlerts('Ocurrio un error', 'Faltan datos', 'error', 'btn-danger');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function searchPublicacion(Request $request)
    {
        $query = $request->input('query');
        $results = $this->publicacionService->searchAjaxPubli($query);

        return response()->json($results);
    }

    public function buscar(Request $request, \App\Repositories\PublicacionRepositoryInterface $publicacionRepository)
    {
        $userModel = $this->headerService->getModelUser();
        $termino = $request->input('q');

        // Usamos el método que ya tienes preparado en tu repositorio.
        // Le pasamos 100 como límite para que traiga todas las coincidencias razonables.
        $publicaciones = $publicacionRepository->searchByEgreso($termino, 100);

        return view('publicaciones.resultados', [
            'publicaciones' => $publicaciones,
            'termino' => $termino,
            'user' => $userModel
        ]);
    }
}
