<?php

namespace App\Http\Controllers;

use App\Services\ClienteServiceInterface;
use App\Services\HeaderServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
    protected $headerService;
    protected $clienteService;

    public function __construct(HeaderServiceInterface $headerService, ClienteServiceInterface $clienteService)
    {
        $this->headerService = $headerService;
        $this->clienteService = $clienteService;
    }

    public function index(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $tipoDocumentos = $this->clienteService->getAllTipoDocumentos();

        $query = $request->input('q');
        if (!empty($query)) {
            $clientes = $this->clienteService->searchClientePaginated($query, 20);
        } else {
            $clientes = $this->clienteService->paginateAllCliente(20);
        }

        return view('clientes', [
            'user' => $userModel,
            'tipoDocumentos' => $tipoDocumentos,
            'clientes' => $clientes,
            'searchQuery' => $query
        ]);
    }

    public function createCliente(Request $request)
    {
        // 1. Usar validación centralizada
        $validator = $this->validarDatosCliente($request);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // 2. Si pasa la validación, llamamos al servicio
        $response = $this->clienteService->createCliente(
            $request->nombre,
            $request->apepaterno,
            $request->apematerno,
            (int)$request->tipodoc,
            trim($request->numerodoc),
            $request->numerotelf,
            $request->correo
        );

        return response()->json($response);
    }

    public function updateCliente(Request $request, $id)
    {
        // 1. Usar la misma validación centralizada
        $validator = $this->validarDatosCliente($request);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // 2. Si pasa la validación, llamamos al servicio
        $response = $this->clienteService->updateCliente(
            $id,
            $request->nombre,
            $request->apepaterno,
            $request->apematerno,
            (int)$request->tipodoc,
            trim($request->numerodoc),
            $request->numerotelf,
            $request->correo
        );

        return response()->json($response);
    }

    public function searchCliente(Request $request)
    {
        $response = $this->clienteService->searchAjaxCLiente($request->query('query'));
        return response()->json($response);
    }

    /**
     * Método privado para centralizar la lógica de validación de clientes.
     */
    private function validarDatosCliente(Request $request)
    {
        return Validator::make($request->all(), [
            'nombre'    => 'required|string|max:100',
            'tipodoc'   => 'required|integer',
            'numerodoc' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    $tipoDoc = (int) $request->tipodoc;

                    if ($tipoDoc === 1 && !preg_match('/^[0-9]{8}$/', $value)) {
                        $fail('El DNI debe tener exactamente 8 dígitos numéricos.');
                    }
                    if ($tipoDoc === 3 && !preg_match('/^(10|15|17|20)[0-9]{9}$/', $value)) {
                        $fail('El RUC debe tener 11 dígitos numéricos y comenzar con 10, 15, 17 o 20.');
                    }
                },
            ],
            // Puedes agregar más reglas aquí si lo necesitas (ej. email)
            'correo' => 'nullable|email',
        ]);
    }
}
