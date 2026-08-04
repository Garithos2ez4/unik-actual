<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\CalculadoraServiceInterface;
use App\Models\Ventas\Venta;

class GananciaController extends Controller
{
    protected $calculadoraService;

    public function __construct(CalculadoraServiceInterface $calculadoraService)
    {
        $this->calculadoraService = $calculadoraService;
    }


    /**
     * Devuelve las ganancias de todas las ventas.
     */
    public function getAllGanancias()
    {
        $tc = $this->calculadoraService->getTasaCambio();
        $costoExpr = $this->calculadoraService->getCostoVentaExpr((string)$tc);

        $comisionFalabellaExpr = "CASE WHEN UPPER(Venta.canal) = 'FALABELLA' THEN 
                (CASE WHEN GrupoProducto.idCategoria IN (1, 3) OR GrupoProducto.idGrupoProducto IN (10, 40, 41, 42, 43) THEN 10.90 ELSE 3.90 END)
                + (DetalleVenta.precioVenta * CASE WHEN GrupoProducto.idGrupoProducto = 10 THEN 0.08 ELSE 0.10 END)
            ELSE 0 END";

        $subqueryTipoCambioCosto = "COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE(c_inner.fechaRegistro))) ASC LIMIT 1), $tc)";

        $costosComponentesSubInner = $this->calculadoraService->getCostoVentaExpr($subqueryTipoCambioCosto, (string)$tc, 'dv_comp', 'p_comp');
        $costosComponentesSub = "COALESCE((SELECT SUM(
            ({$costosComponentesSubInner}) * dv_comp.cantidad
        )
        FROM DetalleVenta dv_comp
        LEFT JOIN Producto p_comp ON dv_comp.idProducto = p_comp.idProducto
        WHERE dv_comp.idVenta = DetalleVenta.idVenta
        AND dv_comp.precioVenta <= 0.10) / 
        GREATEST((SELECT COUNT(*) FROM DetalleVenta dv_main WHERE dv_main.idVenta = DetalleVenta.idVenta AND dv_main.precioVenta > 0.10), 1)
        , 0)";

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
                         SUM(({$costoExpr} + ({$comisionFalabellaExpr})) * DetalleVenta.cantidad + ({$costosComponentesSub})) as costos,
                         SUM(({$comisionFalabellaExpr}) * DetalleVenta.cantidad) as comision_falabella")
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->groupBy('Venta.idVenta', 'Venta.fechaVenta', 'Venta.idUser', 'Usuario.user')
            ->orderByDesc('Venta.fechaVenta')
            ->get()
            ->map(function ($venta) {
                $venta->ingresos = round($venta->ingresos, 2);
                $venta->costos   = round($venta->costos, 2);
                $venta->ganancia = round($venta->ingresos - $venta->costos, 2);
                $venta->comision_falabella = round($venta->comision_falabella, 2);

                // Deduplicar modelos: "A, A, A, B" => "A (x3), B"
                if ($venta->modelo_raw) {
                    $modelos = array_map('trim', explode(',', $venta->modelo_raw));
                    $counts = array_count_values($modelos);
                    $parts = [];
                    foreach ($counts as $modelo => $count) {
                        $parts[] = $count >= 2 ? "{$modelo} (x{$count})" : $modelo;
                    }
                    $venta->modelo = implode(', ', $parts);
                } else {
                    $venta->modelo = null;
                }
                unset($venta->modelo_raw);

                return $venta;
            });

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
        $costoExpr = $this->calculadoraService->getCostoVentaExpr((string)$tc);

        $comisionFalabellaExpr = "CASE WHEN UPPER(Venta.canal) = 'FALABELLA' THEN 
                (CASE WHEN GrupoProducto.idCategoria IN (1, 3) OR GrupoProducto.idGrupoProducto IN (10, 40, 41, 42, 43) THEN 10.90 ELSE 3.90 END)
                + (DetalleVenta.precioVenta * CASE WHEN GrupoProducto.idGrupoProducto = 10 THEN 0.08 ELSE 0.10 END)
            ELSE 0 END";

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
                         (({$costoExpr} + ({$comisionFalabellaExpr})) * DetalleVenta.cantidad) as costos,
                         (({$comisionFalabellaExpr}) * DetalleVenta.cantidad) as comision_falabella")
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
            ->map(function ($detalle) {
                $detalle->ingresos = round($detalle->ingresos, 2);
                $detalle->costos   = round($detalle->costos, 2);
                $detalle->ganancia = round($detalle->ingresos - $detalle->costos, 2);
                $detalle->comision_falabella = round($detalle->comision_falabella, 2);
                return $detalle;
            });

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
        $costoExpr = $this->calculadoraService->getCostoVentaExpr((string)$tc);

        $comisionFalabellaExpr = "CASE WHEN UPPER(Venta.canal) = 'FALABELLA' THEN 
                (CASE WHEN GrupoProducto.idCategoria IN (1, 3) OR GrupoProducto.idGrupoProducto IN (10, 40, 41, 42, 43) THEN 10.90 ELSE 3.90 END)
                + (DetalleVenta.precioVenta * CASE WHEN GrupoProducto.idGrupoProducto = 10 THEN 0.08 WHEN GrupoProducto.idGrupoProducto IN (155, 156, 157, 158, 159, 160, 169) THEN 0.15 ELSE 0.10 END)
            ELSE 0 END";

        $venta = Venta::query()
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->selectRaw("Venta.idVenta, Venta.fechaVenta,
                         GROUP_CONCAT(Producto.modelo SEPARATOR ', ') as modelo,
                         SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                         SUM((($costoExpr) + ($comisionFalabellaExpr)) * DetalleVenta.cantidad) as costos,
                         SUM(($comisionFalabellaExpr) * DetalleVenta.cantidad) as comision_falabella")
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

        return response()->json([
            'success'  => true,
            'tc_usado' => $tc,
            'data'     => $venta
        ]);
    }
}
