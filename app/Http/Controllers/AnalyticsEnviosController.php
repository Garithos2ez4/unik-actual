<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HeaderServiceInterface;
use App\Models\EnvioProvincia;
use Illuminate\Support\Facades\DB;

class AnalyticsEnviosController extends Controller
{
    protected $headerService;

    public function __construct(HeaderServiceInterface $headerService)
    {
        $this->headerService = $headerService;
    }

    public function index(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        
        return view('analytics.components.envios.index', [
            'user' => $userModel
        ]);
    }

    public function getTopProvincias(Request $request)
    {
        // Obtener el top 5 de destinos/provincias
        $topProvincias = EnvioProvincia::select('idDestino', DB::raw('count(*) as total'))
            ->whereDate('fecha_envio', '>=', '2026-06-01')
            ->with('Destino.Provincia')
            ->groupBy('idDestino')
            ->orderBy('total', 'desc')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'provincia' => $item->Destino && $item->Destino->Provincia ? $item->Destino->Provincia->nombre : 'Desconocido', // o nombreProvincia
                    'destino' => $item->Destino ? $item->Destino->nombre : 'Desconocido',
                    'total' => $item->total
                ];
            });

        return response()->json($topProvincias);
    }

    public function getTopClientes(Request $request)
    {
        // Obtener el top 5 de clientes con más envíos
        $topClientes = EnvioProvincia::select('idCliente', DB::raw('count(*) as total'))
            ->whereDate('fecha_envio', '>=', '2026-06-01')
            ->with('Cliente')
            ->groupBy('idCliente')
            ->orderBy('total', 'desc')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'cliente' => $item->Cliente ? trim($item->Cliente->nombre . ' ' . $item->Cliente->apellidoPaterno . ' ' . $item->Cliente->apellidoMaterno) : 'Desconocido',
                    'documento' => $item->Cliente ? $item->Cliente->numeroDocumento : '-',
                    'total' => $item->total
                ];
            });

        return response()->json($topClientes);
    }

    public function getTopAgencias(Request $request)
    {
        // Obtener el top 5 de agencias
        $topAgencias = EnvioProvincia::select('idAgencia', DB::raw('count(*) as total'))
            ->whereDate('fecha_envio', '>=', '2026-06-01')
            ->with('Agencia')
            ->groupBy('idAgencia')
            ->orderBy('total', 'desc')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'agencia' => $item->Agencia ? $item->Agencia->nombre : 'Desconocido',
                    'estado' => $item->Agencia ? ($item->Agencia->estado ? 'Activo' : 'Inactivo') : '-',
                    'total' => $item->total
                ];
            });

        return response()->json($topAgencias);
    }
}
