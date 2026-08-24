<?php

namespace App\Http\Controllers;

use App\Services\CalculadoraServiceInterface;
use App\Services\GananciaQueryService;
use App\Models\Ventas\Venta;

class GananciaController extends Controller
{
    protected $calculadoraService;
    protected $gananciaQueryService;

    public function __construct(CalculadoraServiceInterface $calculadoraService, GananciaQueryService $gananciaQueryService)
    {
        $this->calculadoraService = $calculadoraService;
        $this->gananciaQueryService = $gananciaQueryService;
    }

    /**
     * Devuelve las ganancias de todas las ventas.
     */
    public function getAllGanancias()
    {
        $tc = $this->calculadoraService->getTasaCambio();
        $exprs = $this->gananciaQueryService->getSqlExpressions($tc);
        extract($exprs);

        $ganancias = Venta::query()
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->leftJoin('Usuario', 'Venta.idUser', '=', 'Usuario.idUser')
            ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->leftJoin('EgresoProducto', 'DetalleVenta.idEgreso', '=', 'EgresoProducto.idEgreso')
            ->leftJoin('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->selectRaw("Venta.idVenta, Venta.fechaVenta, Venta.idUser, Usuario.user as nombre_usuario,
                         GROUP_CONCAT(Producto.modelo SEPARATOR ', ') as modelo_raw,
                         GROUP_CONCAT(RegistroProducto.numeroSerie SEPARATOR ', ') as series,
                         SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                         SUM(({$costoVentaExpr} + ({$comisionFalabellaExpr}) + ({$comisionRipleyExpr})) * DetalleVenta.cantidad + ({$costosComponentesSub})) as costos,
                         SUM(({$comisionFalabellaExpr}) * DetalleVenta.cantidad) as comision_falabella,
                         SUM(({$comisionRipleyExpr}) * DetalleVenta.cantidad) as comision_ripley,
                         {$subqueryTcDia} as tc_dia")
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->groupBy('Venta.idVenta', 'Venta.fechaVenta', 'Venta.idUser', 'Usuario.user')
            ->orderByDesc('Venta.fechaVenta')
            ->get()
            ->map(fn($venta) => $this->gananciaQueryService->formatVentaItem($venta));

        return response()->json([
            'success'  => true,
            'tc_usado' => $tc,
            'data'     => $ganancias
        ]);
    }

    /**
     * Devuelve las ganancias SOLO de las ventas de Ripley.
     */
    public function getRipleyGanancias()
    {
        $tc = $this->calculadoraService->getTasaCambio();
        $exprs = $this->gananciaQueryService->getSqlExpressions($tc);
        extract($exprs);

        $ganancias = Venta::query()
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->leftJoin('Usuario', 'Venta.idUser', '=', 'Usuario.idUser')
            ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('EgresoProducto', 'DetalleVenta.idEgreso', '=', 'EgresoProducto.idEgreso')
            ->leftJoin('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->selectRaw("Venta.idVenta, Venta.fechaVenta, Venta.idUser, Usuario.user as nombre_usuario,
                         GROUP_CONCAT(Producto.modelo SEPARATOR ', ') as modelo_raw,
                         GROUP_CONCAT(RegistroProducto.numeroSerie SEPARATOR ', ') as series,
                         SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                         
                         /* OJO AQUÍ: Costos es SOLO el costo del producto, sin comisiones ni envíos */
                         SUM(({$costoVentaExpr}) * DetalleVenta.cantidad + ({$costosComponentesSub})) as costos_base,
                         
                         /* Comision Pura (12% + los 2 soles si aplica) */
                         SUM(({$ripleyExpr}) * DetalleVenta.cantidad) as comision_ripley_base,
                         
                         /* Tarifa Logística (Peso) */
                         SUM(({$tarifaLogisticaRipleyExpr}) / GREATEST(DetalleVenta.cantidad, 1) * DetalleVenta.cantidad) as tarifa_peso_ripley,
                         
                         {$subqueryTcDia} as tc_dia")
            ->whereRaw("UPPER(Venta.canal) = 'RIPLEY'")
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->groupBy('Venta.idVenta', 'Venta.fechaVenta', 'Venta.idUser', 'Usuario.user')
            ->orderByDesc('Venta.fechaVenta')
            ->get()
            ->map(fn($venta) => $this->gananciaQueryService->formatVentaItem($venta));

        return response()->json([
            'success'  => true,
            'tc_usado' => $tc,
            'data'     => $ganancias
        ]);
    }

    /**
     * Devuelve las ganancias detalladas por cada producto (DetalleVenta) de todas las ventas.
     */
    public function getAllGananciasPorDetalle()
    {
        $tc = $this->calculadoraService->getTasaCambio();
        $exprs = $this->gananciaQueryService->getSqlExpressions($tc);
        extract($exprs);

        $ganancias = \App\Models\Ventas\DetalleVenta::query()
            ->join('Venta', 'DetalleVenta.idVenta', '=', 'Venta.idVenta')
            ->leftJoin('Usuario', 'Venta.idUser', '=', 'Usuario.idUser')
            ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->leftJoin('EgresoProducto', 'DetalleVenta.idEgreso', '=', 'EgresoProducto.idEgreso')
            ->leftJoin('RegistroProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->selectRaw("DetalleVenta.idDetalleVenta, Venta.idVenta, Venta.fechaVenta, Venta.idUser, Usuario.user as nombre_usuario,
                         Producto.nombreProducto,
                         Producto.modelo,
                         DetalleVenta.cantidad,
                         GROUP_CONCAT(RegistroProducto.numeroSerie SEPARATOR ', ') as series,
                         (DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                         (({$costoVentaExpr} + ({$comisionFalabellaExpr}) + ({$comisionRipleyExpr})) * DetalleVenta.cantidad) as costos,
                         (({$comisionFalabellaExpr}) * DetalleVenta.cantidad) as comision_falabella,
                         (({$comisionRipleyExpr}) * DetalleVenta.cantidad) as comision_ripley,
                         {$subqueryTcDia} as tc_dia")
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->groupBy(
                'DetalleVenta.idDetalleVenta',
                'Venta.idVenta',
                'Venta.fechaVenta',
                'Venta.idUser',
                'Usuario.user',
                'Producto.nombreProducto',
                'Producto.modelo',
                'DetalleVenta.cantidad',
                'DetalleVenta.precioVenta',
                'GrupoProducto.idCategoria',
                'GrupoProducto.idGrupoProducto',
                'Venta.canal'
            )
            ->orderByDesc('Venta.fechaVenta')
            ->get()
            ->map(fn($detalle) => $this->gananciaQueryService->formatVentaItem($detalle));

        return response()->json([
            'success'  => true,
            'tc_usado' => $tc,
            'data'     => $ganancias
        ]);
    }

    /**
     * Devuelve la ganancia de una venta específica.
     */
    public function getGananciaPorVenta($idVenta)
    {
        $tc = $this->calculadoraService->getTasaCambio();
        $exprs = $this->gananciaQueryService->getSqlExpressions($tc);
        extract($exprs);

        $venta = Venta::query()
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->selectRaw("Venta.idVenta, Venta.fechaVenta,
                         GROUP_CONCAT(Producto.modelo SEPARATOR ', ') as modelo,
                         SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                         SUM((($costoVentaExpr) + ($comisionFalabellaExpr) + ($comisionRipleyExpr)) * DetalleVenta.cantidad) as costos,
                         SUM(($comisionFalabellaExpr) * DetalleVenta.cantidad) as comision_falabella,
                         SUM(($comisionRipleyExpr) * DetalleVenta.cantidad) as comision_ripley,
                         {$subqueryTcDia} as tc_dia")
            ->where('Venta.idVenta', $idVenta)
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->groupBy('Venta.idVenta', 'Venta.fechaVenta')
            ->first();

        if (!$venta) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada'
            ], 404);
        }

        $venta->ingresos = round($venta->ingresos, 2);
        $venta->costos   = round($venta->costos, 2);
        $venta->ganancia = round($venta->ingresos - $venta->costos, 2);
        $venta->comision_falabella = round($venta->comision_falabella, 2);
        $venta->comision_ripley = round($venta->comision_ripley, 2);

        return response()->json([
            'success'  => true,
            'tc_usado' => $tc,
            'data'     => $venta
        ]);
    }
}
