<?php
namespace App\Http\Controllers;

use App\Services\ClienteServiceInterface;
use App\Services\HeaderServiceInterface;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    protected $headerService;
    protected $clienteService;

    public function __construct(HeaderServiceInterface $headerService,
                                ClienteServiceInterface $clienteService)
    {
        $this->headerService = $headerService;
        $this->clienteService = $clienteService;
    }

    public function index(Request $request){
        $userModel = $this->headerService->getModelUser();
        $tipoDocumentos = $this->clienteService->getAllTipoDocumentos();
        
        $query = $request->input('q');
        if (!empty($query)) {
            $clientes = $this->clienteService->searchClientePaginated($query, 20);
        } else {
            $clientes = $this->clienteService->paginateAllCliente(20);
        }

        return view('clientes',[
            'user' => $userModel,
            'tipoDocumentos' => $tipoDocumentos,
            'clientes' => $clientes,
            'searchQuery' => $query
        ]);
    }

    public function createCliente(Request $request){
        $userModel = $this->headerService->getModelUser();
        $nombre = $request->input('nombre');
        $apePaterno = $request->input('apepaterno');
        $apeMaterno = $request->input('apematerno');
        $tipoDoc = $request->input('tipodoc');
        $numeroDoc = $request->input('numerodoc');
        $telefono = $request->input('numerotelf');
        $correo = $request->input('correo');

        if(isset($nombre) && isset($tipoDoc) && isset($numeroDoc)){
            $response =  $this->clienteService->createCliente($nombre,$apePaterno,$apeMaterno,$tipoDoc,$numeroDoc,$telefono,$correo);
            return response()->json($response);
        }
    }

    public function updateCliente(Request $request, $id){
        $userModel = $this->headerService->getModelUser();
        $nombre = $request->input('nombre');
        $apePaterno = $request->input('apepaterno');
        $apeMaterno = $request->input('apematerno');
        $tipoDoc = $request->input('tipodoc');
        $numeroDoc = $request->input('numerodoc');
        $telefono = $request->input('numerotelf');
        $correo = $request->input('correo');

        if(isset($nombre) && isset($tipoDoc) && isset($numeroDoc)){
            $response =  $this->clienteService->updateCliente($id,$nombre,$apePaterno,$apeMaterno,$tipoDoc,$numeroDoc,$telefono,$correo);
            return response()->json($response);
        }
    }

    public function searchCliente(Request $request){
        $response = $this->clienteService->searchAjaxCLiente($request->query('query'));
        return response()->json($response);
    }
}