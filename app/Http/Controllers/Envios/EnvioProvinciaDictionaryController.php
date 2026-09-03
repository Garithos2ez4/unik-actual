<?php

namespace App\Http\Controllers\Envios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\EnvioProvinciaServiceInterface;

class EnvioProvinciaDictionaryController extends Controller
{
    protected $envioService;

    public function __construct(EnvioProvinciaServiceInterface $envioService)
    {
        $this->envioService = $envioService;
    }

    public function storeAgencia(Request $request)
    {
        try {
            $agencia = $this->envioService->createAgencia($request->nombre);
            return response()->json(['success' => true, 'agencia' => $agencia]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    public function storeProvincia(Request $request)
    {
        try {
            $provincia = $this->envioService->createProvincia($request->nombre);
            return response()->json(['success' => true, 'provincia' => $provincia]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    public function storeDestino(Request $request)
    {
        try {
            $destino = $this->envioService->createDestino($request->idProvincia, $request->nombre);
            return response()->json(['success' => true, 'destino' => $destino]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    public function storeSubAgencia(Request $request)
    {
        try {
            $subAgencia = $this->envioService->createSubAgencia($request->idAgencia, $request->idDestino, $request->nombre, $request->direccion);
            return response()->json(['success' => true, 'sub_agencia' => $subAgencia]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    public function syncAllAgencias()
    {
        // Al sincronizar 6 agencias masivamente se superan los 30 segundos límite de PHP,
        // por lo que desactivamos el límite de tiempo.
        set_time_limit(0);

        try {
            // No truncamos la tabla porque las sucursales ya están siendo referenciadas en envíos anteriores.
            // Los comandos de Artisan ya tienen lógica de "updateOrCreate" interna.

            // Ejecutar los comandos de sincronización
            \Illuminate\Support\Facades\Artisan::call('sync:marvisur');
            \Illuminate\Support\Facades\Artisan::call('sync:emtrafesa');
            \Illuminate\Support\Facades\Artisan::call('sync:olva');
            \Illuminate\Support\Facades\Artisan::call('sync:shalom');
            \Illuminate\Support\Facades\Artisan::call('sync:espinoza');
            \Illuminate\Support\Facades\Artisan::call('sync:flores');

            return response()->json([
                'success' => true,
                'message' => 'Sincronización masiva completada correctamente.'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error en sincronización masiva: ' . $th->getMessage()
            ], 500);
        }
    }
}
