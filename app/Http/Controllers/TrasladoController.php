<?php

namespace App\Http\Controllers;

use App\Services\HeaderServiceInterface;
use App\Services\TrasladoServiceInterface;
use Illuminate\Http\Request;

class TrasladoController extends Controller
{
    protected $headerService;
    protected $trasladoService;

    public function __construct(HeaderServiceInterface $headerService,
                                TrasladoServiceInterface $trasladoService)
    {
        $this->headerService = $headerService;
        $this->trasladoService = $trasladoService;
    }

    public function index(){
        $userModel = $this->headerService->getModelUser();
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 8){
                $almacenes = $this->trasladoService->getAllAlmacenes();
                return view('traslado',['user' => $userModel,
                                        'almacenes' => $almacenes]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para realizar esta accion','warning','btn-danger');
        return back();
    }

    public function updateRegistroAlmacen(Request $request){
        $userModel = $this->headerService->getModelUser();
        $data = $request->input('traslado');
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 8){
                if($data) {
                    $this->trasladoService->updateRegistros($data);
                }
                $this->headerService->sendFlashAlerts('Traslado Existoso','Los registros se actualizaron correctamente','success','btn-success');
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para realizar esta accion','warning','btn-danger');
        return back();

    }

    public function searchProductoAjax(Request $request)
    {
        $query = $request->input('query');
        if(empty($query)) return response()->json([]);

        $productos = \App\Models\Producto::where('modelo', 'like', "%$query%")
            ->orWhere('nombreProducto', 'like', "%$query%")
            ->orWhere('codigoProducto', 'like', "%$query%")
            ->orWhere('partNumber', 'like', "%$query%")
            ->with(['MarcaProducto'])
            ->limit(10)
            ->get();

        return response()->json($productos);
    }

    public function getSeriesDisponibles(Request $request)
    {
        $idProducto = $request->input('idProducto');
        if (empty($idProducto)) {
            return response()->json([]);
        }

        // Series disponibles para traslado (no entregadas ni invalidas)
        $series = \App\Models\RegistroProducto::with(['Almacen', 'DetalleComprobante.Producto', 'DetalleComprobante.Comprobante.Preveedor'])
            ->whereHas('DetalleComprobante', function($q) use ($idProducto) {
                $q->where('idProducto', $idProducto);
            })
            ->whereNotIn('estado', ['ENTREGADO', 'INVALIDO'])
            ->get();

        return response()->json($series);
    }

    public function searchMultipleSeries(Request $request)
    {
        $seriesArr = $request->input('series', []);
        if(empty($seriesArr)) return response()->json([]);

        $series = \App\Models\RegistroProducto::with(['Almacen', 'DetalleComprobante.Producto', 'DetalleComprobante.Comprobante.Preveedor'])
            ->whereIn('numeroSerie', $seriesArr)
            ->whereNotIn('estado', ['ENTREGADO', 'INVALIDO'])
            ->get();

        return response()->json($series);
    }
}