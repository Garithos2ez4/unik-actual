<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\HeaderServiceInterface;
use App\Services\CalculadoraServiceInterface;
use App\Services\GananciaQueryService;
use App\Models\Ventas\Venta;

class AnalyticsRipleyController extends Controller
{
    protected $headerService;
    protected $calculadoraService;
    protected $gananciaQueryService;

    public function __construct(HeaderServiceInterface $headerService, CalculadoraServiceInterface $calculadoraService, GananciaQueryService $gananciaQueryService)
    {
        $this->headerService = $headerService;
        $this->calculadoraService = $calculadoraService;
        $this->gananciaQueryService = $gananciaQueryService;
    }

    private function validateAccess($userModel, int $idVista)
    {
        return $userModel->Accesos->contains('idVista', $idVista);
    }

    private function applyInventarioFilter($query, $isVenta = true)
    {
        if ($isVenta) {
            // Excluir ventas devueltas/anuladas para que no inflen las mÃ©tricas
            $query->where('DetalleVenta.estado', '!=', 'DEVUELTO');
            return $query;
        } else {
            // Para compras/gastos, sÃ­ filtramos los comprobantes de INVENTARIO para no inflar los costos
            $query->whereNotExists(function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('RegistroProducto')
                    ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
                    ->join('Comprobante', 'DetalleComprobante.idComprobante', '=', 'Comprobante.idComprobante')
                    ->whereColumn('RegistroProducto.idRegistro', 'EgresoProducto.idRegistro')
                    ->where('Comprobante.numeroComprobante', 'LIKE', 'INVENTARIO%');
            });
        }
        return $query;
    }

    private function resolveDateRange(Request $request)
    {
        Carbon::setLocale('es');
        $anio = (int) $request->query('anio', now()->year);
        $mes  = (int) $request->query('mes', now()->month);

        $fechaInicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFin    = Carbon::create($anio, $mes, 1)->endOfMonth()->endOfDay();

        if ($request->filled('dia_inicio')) {
            $fechaInicio = Carbon::parse($request->query('dia_inicio'))->startOfDay();
            $fechaFin = $request->filled('dia_fin')
                ? Carbon::parse($request->query('dia_fin'))->endOfDay()
                : $fechaInicio->copy()->endOfDay();
        } elseif ($request->filled('dia_fin')) {
            $fechaFin = Carbon::parse($request->query('dia_fin'))->endOfDay();
        }

        return [$fechaInicio, $fechaFin, $anio, $mes];
    }

    public function index(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        if (!$this->validateAccess($userModel, 13)) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaÃ±a', 'warning', 'btn-danger');
            return redirect()->route('dashboard', ['user' => $userModel]);
        }

        $tc = $this->calculadoraService->getTasaCambio();
        [$fechaInicio, $fechaFin, $anio, $mes] = $this->resolveDateRange($request);
        $gruposCostoBajo = $this->calculadoraService->getGruposCostoExcepcion();

        $exprs = $this->gananciaQueryService->getSqlExpressions($tc);
        extract($exprs);

        // Usamos el Modelo Venta para iniciar la consulta
        $ventasRipley = Venta::query()
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->leftJoin('Usuario', 'Venta.idUser', '=', 'Usuario.idUser')
            ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->selectRaw("Venta.idVenta, Venta.fechaVenta, Venta.idUser, Usuario.user as nombre_usuario,
                         GROUP_CONCAT(Producto.modelo SEPARATOR ', ') as modelo,
                         SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                         
                         /* SOLO el costo del producto */
                         SUM(({$costoVentaExpr}) * DetalleVenta.cantidad + ($costosComponentesSub)) as costos_base,
                         
                         /* ComisiÃ³n limpia de Ripley */
                         SUM(({$ripleyExpr}) * DetalleVenta.cantidad) as comision_ripley,
                         
                         /* Tarifa de peso separada */
                         SUM(({$tarifaLogisticaRipleyExpr}) / GREATEST(DetalleVenta.cantidad, 1) * DetalleVenta.cantidad) as tarifa_peso_ripley")
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->where(function ($q) {
                $this->applyInventarioFilter($q, true);
            })
            ->whereRaw("UPPER(Venta.canal) = 'RIPLEY'")
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->groupBy('Venta.idVenta', 'Venta.fechaVenta', 'Venta.idUser', 'Usuario.user')
            ->orderByDesc('Venta.fechaVenta')
            ->get()
            ->map(fn($venta) => $this->gananciaQueryService->formatVentaItem($venta));
        
        // â”€â”€ Consulta para tendencia de ventas por mes (GrÃ¡fico) â”€â”€â”€â”€â”€â”€â”€â”€
        $ventasMesRaw = Venta::query()
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->selectRaw('DATE(Venta.fechaVenta) as fecha, SUM(DetalleVenta.cantidad) as total_unidades, SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as total_monto')
            ->where('DetalleVenta.precioVenta', '>', 0.10)
            ->where(function ($q) {
                $this->applyInventarioFilter($q, true);
            })
            ->whereRaw("UPPER(Venta.canal) = 'RIPLEY'")
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->groupBy(\Illuminate\Support\Facades\DB::raw('DATE(Venta.fechaVenta)'))
            ->orderBy('fecha', 'asc')
            ->get();

        $ventasMes = [];
        for ($date = $fechaInicio->copy(); $date->lte($fechaFin); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $found = $ventasMesRaw->firstWhere('fecha', $dateStr);
            $ventasMes[] = [
                'fecha' => $date->format('d/m'),
                'total' => $found ? $found->total_unidades : 0,
                'monto' => $found ? round($found->total_monto, 2) : 0
            ];
        }

        return view('analytics.components.ripley.index', [
            'user' => $userModel,
            'ventasRipley' => $ventasRipley,
            'ventasMes' => $ventasMes,
            'filtros' => compact('anio', 'mes') + $request->only('dia_inicio', 'dia_fin') + ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin],
        ]);
    }

    public function scraperRipley(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        // Validar acceso a la Vista 4 (Plataformas / Analítica)
        if (!$this->validateAccess($userModel, 4)) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
            return redirect()->route('dashboard', ['user' => $userModel]);
        }

        return view('analytics.scraper_ripley', [
            'user' => $userModel,
        ]);
    }
}

