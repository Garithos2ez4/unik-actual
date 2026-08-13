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

    public function consultarRuc($ruc)
    {
        try {
            $token = env('DECOLECTA_TOKEN');
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Falta token de API'], 500);
            }

            $cacheKey = 'decolecta_ruc_' . $ruc;
            $monthKey = 'decolecta_count_' . date('Y_m');

            $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addDays(30), function () use ($token, $ruc, $monthKey) {
                // Incrementar contador de llamadas
                \Illuminate\Support\Facades\Cache::increment($monthKey);

                $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->withToken($token)
                    ->acceptJson()
                    ->get('https://api.decolecta.com/v1/sunat/ruc', [
                        'numero' => $ruc
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                // Revertir contador si falla
                \Illuminate\Support\Facades\Cache::decrement($monthKey);
                throw new \Exception('Error al consultar SUNAT: ' . $response->status());
            });

            $currentCount = \Illuminate\Support\Facades\Cache::get($monthKey, 0);

            return response()->json([
                'success' => true, 
                'data' => $data,
                'api_count' => $currentCount,
                'api_limit' => 100
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function consultarDni($dni)
    {
        try {
            $token = env('DECOLECTA_TOKEN');
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Falta token de API'], 500);
            }

            $cacheKey = 'decolecta_dni_' . $dni;
            $monthKey = 'decolecta_count_' . date('Y_m');

            $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addDays(30), function () use ($token, $dni, $monthKey) {
                // Incrementar contador de llamadas
                \Illuminate\Support\Facades\Cache::increment($monthKey);

                $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->withToken($token)
                    ->acceptJson()
                    ->get('https://api.decolecta.com/v1/reniec/dni', [
                        'numero' => $dni
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                // Revertir contador si falla
                \Illuminate\Support\Facades\Cache::decrement($monthKey);
                throw new \Exception('Error al consultar RENIEC: ' . $response->status());
            });

            $currentCount = \Illuminate\Support\Facades\Cache::get($monthKey, 0);

            return response()->json([
                'success' => true, 
                'data' => $data,
                'api_count' => $currentCount,
                'api_limit' => 100
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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
            // Regla email:rfc,dns verifica sintaxis y que el dominio exista (tenga registros MX)
            'correo' => 'nullable|email:rfc,dns',
        ]);
    }
}
