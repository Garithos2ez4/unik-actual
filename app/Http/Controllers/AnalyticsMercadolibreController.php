<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\HeaderServiceInterface;
use App\Services\CalculadoraServiceInterface;
use App\Models\Venta;


class AnalyticsMercadolibreController extends Controller
{
    protected $headerService;
    protected $calculadoraService;

    public function __construct(HeaderServiceInterface $headerService, CalculadoraServiceInterface $calculadoraService)
    {
        $this->headerService = $headerService;
        $this->calculadoraService = $calculadoraService;
    }

    private function validateAccess($userModel, int $idVista)
    {
        return $userModel->Accesos->contains('idVista', $idVista);
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
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
            return redirect()->route('dashboard', ['user' => $userModel]);
        }

        $tc = $this->calculadoraService->getTasaCambio();
        [$fechaInicio, $fechaFin, $anio, $mes] = $this->resolveDateRange($request);

        $costoVentaExpr = "COALESCE(
            (SELECT CASE WHEN c_inner.moneda = 'DOLAR' THEN dc_inner.precioUnitario * $tc ELSE dc_inner.precioUnitario END
             FROM EgresoProducto ep_inner
             INNER JOIN RegistroProducto rp_inner ON rp_inner.idRegistro = ep_inner.idRegistro
             INNER JOIN DetalleComprobante dc_inner ON dc_inner.idDetalleComprobante = rp_inner.idDetalleComprobante
             INNER JOIN Comprobante c_inner ON c_inner.idComprobante = dc_inner.idComprobante
             WHERE ep_inner.idEgreso = DetalleVenta.idEgreso AND dc_inner.precioUnitario > 1
             LIMIT 1),
            COALESCE(Producto.precioDolar, 0) * $tc * 1.18
        )";

        $costosComponentesSub = "COALESCE((SELECT SUM(
            COALESCE(
                (SELECT CASE WHEN c_inner.moneda = 'DOLAR' THEN dc_inner.precioUnitario * $tc ELSE dc_inner.precioUnitario END
                 FROM EgresoProducto ep_inner
                 INNER JOIN RegistroProducto rp_inner ON rp_inner.idRegistro = ep_inner.idRegistro
                 INNER JOIN DetalleComprobante dc_inner ON dc_inner.idDetalleComprobante = rp_inner.idDetalleComprobante
                 INNER JOIN Comprobante c_inner ON c_inner.idComprobante = dc_inner.idComprobante
                 WHERE ep_inner.idEgreso = dv_comp.idEgreso AND dc_inner.precioUnitario > 1
                 LIMIT 1),
                COALESCE(p_comp.precioDolar, 0) * $tc * 1.18
            ) * dv_comp.cantidad
        )
        FROM DetalleVenta dv_comp
        LEFT JOIN Producto p_comp ON dv_comp.idProducto = p_comp.idProducto
        WHERE dv_comp.idVenta = DetalleVenta.idVenta
        AND dv_comp.precioVenta <= 0.01) / 
        GREATEST((SELECT COUNT(*) FROM DetalleVenta dv_main WHERE dv_main.idVenta = DetalleVenta.idVenta AND dv_main.precioVenta > 0.01), 1)
        , 0)";

        $comisionMercadoLibreExpr = "0";

        // Usamos el Modelo Venta para iniciar la consulta
        $ventasMercadoLibre = Venta::query()
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->leftJoin('Usuario', 'Venta.idUser', '=', 'Usuario.idUser')
            ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->selectRaw("Venta.idVenta, Venta.fechaVenta, Venta.idUser, Usuario.user as nombre_usuario,
                         GROUP_CONCAT(Producto.modelo SEPARATOR ', ') as modelo,
                         SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                         SUM((($costoVentaExpr) + ($comisionMercadoLibreExpr)) * DetalleVenta.cantidad + ($costosComponentesSub)) as costos,
                         SUM(($comisionMercadoLibreExpr) * DetalleVenta.cantidad) as comision_mercadolibre")
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->whereRaw("(UPPER(Venta.canal) = 'MERCADO LIBRE' OR UPPER(Venta.canal) = 'MERCADOLIBRE')")
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->groupBy('Venta.idVenta', 'Venta.fechaVenta', 'Venta.idUser', 'Usuario.user')
            ->orderByDesc('Venta.fechaVenta')
            ->get()
            ->map(function ($venta) {
                $venta->ingresos = round($venta->ingresos, 2);
                $venta->costos   = round($venta->costos, 2);
                $venta->ganancia = round($venta->ingresos - $venta->costos, 2);
                $venta->comision_mercadolibre = round($venta->comision_mercadolibre, 2);
                $venta->margen = $venta->ingresos > 0 ? round(($venta->ganancia / $venta->ingresos) * 100, 2) : 0;
                return $venta;
            });

        // Cuentas de Mercado Libre
        $cuentasMercadoLibre = DB::table('Venta')
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->join('publicacion', 'DetalleVenta.idPublicacion', '=', 'publicacion.idPublicacion')
            ->join('cuentasplataforma', 'publicacion.idCuentaPlataforma', '=', 'cuentasplataforma.idCuentaPlataforma')
            ->whereRaw("(UPPER(Venta.canal) = 'MERCADO LIBRE' OR UPPER(Venta.canal) = 'MERCADOLIBRE')")
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->select(
                'cuentasplataforma.nombreCuenta',
                DB::raw('COUNT(DISTINCT Venta.idVenta) as cantidad_ventas'),
                DB::raw('SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as total_ingresos'),
                DB::raw("SUM((($costoVentaExpr) + ($comisionMercadoLibreExpr)) * DetalleVenta.cantidad + ($costosComponentesSub)) as total_costos")
            )
            ->groupBy('cuentasplataforma.nombreCuenta')
            ->orderByDesc('total_ingresos')
            ->get()
            ->map(function ($cuenta) {
                $cuenta->total_ingresos = round($cuenta->total_ingresos, 2);
                $cuenta->total_costos = round($cuenta->total_costos, 2);
                $cuenta->ganancia = round($cuenta->total_ingresos - $cuenta->total_costos, 2);
                $cuenta->margen = $cuenta->total_ingresos > 0 ? round(($cuenta->ganancia / $cuenta->total_ingresos) * 100, 2) : 0;
                return $cuenta;
            });

        // ── Consulta para agrupar por SKU (Modelo) ───────────────────
        $skusMercadoLibre = \App\Models\DetalleVenta::query()
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->join('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->selectRaw("Producto.modelo as sku,
                         SUM(DetalleVenta.cantidad) as total_unidades,
                         SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                         SUM((($costoVentaExpr) + ($comisionMercadoLibreExpr)) * DetalleVenta.cantidad + ($costosComponentesSub)) as costos,
                         SUM(($comisionMercadoLibreExpr) * DetalleVenta.cantidad) as comision_mercadolibre")
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->whereRaw("(UPPER(Venta.canal) = 'MERCADO LIBRE' OR UPPER(Venta.canal) = 'MERCADOLIBRE')")
            ->whereBetween('Venta.fechaVenta', [$fechaInicio, $fechaFin])
            ->groupBy('Producto.modelo')
            ->orderByDesc('ingresos')
            ->get()
            ->map(function ($sku) {
                $sku->ingresos = round($sku->ingresos, 2);
                $sku->costos = round($sku->costos, 2);
                $sku->ganancia = round($sku->ingresos - $sku->costos, 2);
                $sku->comision_mercadolibre = round($sku->comision_mercadolibre, 2);
                $sku->margen = $sku->ingresos > 0 ? round(($sku->ganancia / $sku->ingresos) * 100, 2) : 0;
                return $sku;
            });

        $skusMayorRotacion = $skusMercadoLibre->sortByDesc('total_unidades')->take(5)->values();
        $skusMayorRentabilidad = $skusMercadoLibre->sortByDesc('ganancia')->take(5)->values();

        // ── Consulta para tendencia de ventas por mes (Gráfico) ────────
        $ventasMesRaw = Venta::query()
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->selectRaw('DATE(Venta.fechaVenta) as fecha, SUM(DetalleVenta.cantidad) as total_unidades, SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as total_monto')
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->whereRaw("(UPPER(Venta.canal) = 'MERCADO LIBRE' OR UPPER(Venta.canal) = 'MERCADOLIBRE')")
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

        return view('analytics.components.mercadolibre.index', [
            'user' => $userModel,
            'ventasMercadoLibre' => $ventasMercadoLibre,
            'cuentasMercadoLibre' => $cuentasMercadoLibre,
            'skusMercadoLibre' => $skusMercadoLibre,
            'skusMayorRotacion' => $skusMayorRotacion,
            'skusMayorRentabilidad' => $skusMayorRentabilidad,
            'ventasMes' => $ventasMes,
            'filtros' => compact('anio', 'mes') + $request->only('dia_inicio', 'dia_fin') + ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin],
        ]);
    }
}
