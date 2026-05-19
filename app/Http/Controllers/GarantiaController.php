<?php

namespace App\Http\Controllers;

use App\Services\GarantiaServiceInterface;
use App\Services\HeaderServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GarantiaController extends Controller
{
    protected $headerService;
    protected $garantiaService;

    public function __construct(HeaderServiceInterface $headerService,
                                GarantiaServiceInterface $garantiaService)
    {
        $this->headerService = $headerService;
        $this->garantiaService = $garantiaService;
    }

    public function index($date){
        $userModel = $this->headerService->getModelUser();

        $month = Carbon::createFromFormat('Y-m', $date);
        $garantias = $this->garantiaService->getGarantiasByMonth($month,50);

        return view('garantias',['user' => $userModel,
                                'garantias' => $garantias]);
    }

    public function create(){
        $userModel = $this->headerService->getModelUser();

        $tipoDocumentos = $this->garantiaService->getAllTipoDocumentos();

        return view('creategarantia',['user' => $userModel,
                                    'tipoDocumentos' => $tipoDocumentos]);
    }

    public function insertGarantia(Request $request){
        $idRegistro = $request->input('idregistro');
        $idCliente = $request->input('idcliente');
        $nroComprobante = $request->input('numerocomprobante');
        $recepcion = $request->input('recepcion');
        $estado = $request->input('estado');
        $falla = $request->input('falla');

        if(!empty($idRegistro) && !empty($idCliente)){
            if(empty($nroComprobante)){
                $this->headerService->sendFlashAlerts('Ocurrio un error','Hubo un error con el numero de comprobante','danger','btn-danger');
                return back();
            }

            if(empty($recepcion) || empty($estado) || empty($falla)){
                $this->headerService->sendFlashAlerts('Ocurrio un error','Faltan datos en el detalle de la garantia','danger','btn-danger');
                return back();
            }

            $result = $this->garantiaService->insertGarantia($idRegistro,$idCliente,$nroComprobante,$recepcion,$estado,$falla);

            session()->flash('success_pdf', $result);
            return back();
        }
        $this->headerService->sendFlashAlerts('Ocurrio un error','Hubo un error en la operación','danger','btn-danger');
        return back();
    }

    public function searchRegistro(Request $request)
    {
        $query = $request->input('query');
        $results = $this->garantiaService->searchAjaxRegistro($query);

        return response()->json($results);
    }

    public function getOneRegistro(Request $request)
    {
        $query = $request->input('query');
        $results = $this->garantiaService->getOneAjaxRegistro($query);

        return response()->json($results);
    }
}
