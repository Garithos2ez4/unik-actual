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

    private function resolveDateRange(Request $request)
    {
        \Carbon\Carbon::setLocale('es');
        $anio = (int) $request->query('anio', now()->year);
        $mes  = (int) $request->query('mes', now()->month);

        $fechaInicio = \Carbon\Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFin    = \Carbon\Carbon::create($anio, $mes, 1)->endOfMonth()->endOfDay();

        if ($request->filled('dia_inicio')) {
            $fechaInicio = \Carbon\Carbon::parse($request->query('dia_inicio'))->startOfDay();
            $fechaFin = $request->filled('dia_fin')
                ? \Carbon\Carbon::parse($request->query('dia_fin'))->endOfDay()
                : $fechaInicio->copy()->endOfDay();
        } elseif ($request->filled('dia_fin')) {
            $fechaFin = \Carbon\Carbon::parse($request->query('dia_fin'))->endOfDay();
        }

        return [$fechaInicio, $fechaFin, $anio, $mes];
    }

    public function index(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        
        [$fechaInicio, $fechaFin, $anio, $mes] = $this->resolveDateRange($request);

        $filtros = compact('anio', 'mes') + $request->only('dia_inicio', 'dia_fin') + ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin];

        return view('analytics.components.envios.index', [
            'user' => $userModel,
            'filtros' => $filtros
        ]);
    }

    public function getTopProvincias(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolveDateRange($request);

        // Obtener el top 5 de destinos/provincias
        $topProvincias = EnvioProvincia::select('idDestino', DB::raw('count(*) as total'))
            ->whereBetween('fecha_envio', [$fechaInicio, $fechaFin])
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
        [$fechaInicio, $fechaFin] = $this->resolveDateRange($request);

        // Obtener el top 5 de clientes con más envíos
        $topClientes = EnvioProvincia::select('idCliente', DB::raw('count(*) as total'))
            ->whereBetween('fecha_envio', [$fechaInicio, $fechaFin])
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
        [$fechaInicio, $fechaFin] = $this->resolveDateRange($request);

        // Obtener el top 5 de agencias
        $topAgencias = EnvioProvincia::select('idAgencia', DB::raw('count(*) as total'))
            ->whereBetween('fecha_envio', [$fechaInicio, $fechaFin])
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
