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
        $subqueryTipoCambioCosto = "COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE(c_inner.fechaRegistro))) ASC LIMIT 1), $tc)";
        $costoExpr = $this->calculadoraService->getCostoVentaExpr($subqueryTipoCambioCosto, (string)$tc);

        $comisionFalabellaExpr = "CASE WHEN UPPER(Venta.canal) = 'FALABELLA' THEN 
                (CASE WHEN GrupoProducto.idCategoria IN (1, 3) OR GrupoProducto.idGrupoProducto IN (10, 40, 41, 42, 43) THEN 10.90 ELSE 3.90 END)
                + (DetalleVenta.precioVenta * CASE WHEN GrupoProducto.idGrupoProducto = 10 THEN 0.08 WHEN GrupoProducto.idGrupoProducto IN (155, 156, 157, 158, 159, 160, 169) THEN 0.15 ELSE 0.10 END)
            ELSE 0 END";

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

        $subqueryTcDia = "COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE(Venta.fechaVenta))) ASC LIMIT 1), $tc)";

        $pesoRipleySubquery = "COALESCE(
            (SELECT CAST(REPLACE(REPLACE(cp52.caracteristicaProducto, ' KG', ''), ',', '.') AS DECIMAL(10,3))
             FROM caracteristicas_producto cp52
             WHERE cp52.idProducto = Producto.idProducto AND cp52.idCaracteristica = 52 LIMIT 1),
            (SELECT CAST(REPLACE(REPLACE(cp10.caracteristicaProducto, ' KG', ''), ',', '.') AS DECIMAL(10,3))
             FROM caracteristicas_producto cp10
             WHERE cp10.idProducto = Producto.idProducto AND cp10.idCaracteristica = 10 LIMIT 1)
        )";
        $tarifaLogisticaRipleyExpr = "CASE
            WHEN ({$pesoRipleySubquery}) IS NULL THEN 0
            WHEN ({$pesoRipleySubquery}) <= 0.50  THEN 4.90
            WHEN ({$pesoRipleySubquery}) <= 1.00  THEN 4.90
            WHEN ({$pesoRipleySubquery}) <= 3.00  THEN 5.90
            WHEN ({$pesoRipleySubquery}) <= 8.00  THEN 9.90
            WHEN ({$pesoRipleySubquery}) <= 25.00 THEN 12.90
            WHEN ({$pesoRipleySubquery}) <= 40.00 THEN 15.90
            WHEN ({$pesoRipleySubquery}) <= 150.00 THEN 28.90
            WHEN ({$pesoRipleySubquery}) <= 260.00 THEN 40.90
            ELSE 40.90 END";
        $comisionRipleyExpr = "CASE WHEN UPPER(Venta.canal) = 'RIPLEY' THEN (DetalleVenta.precioVenta * 0.12) + CASE WHEN DetalleVenta.precioVenta <= 39.00 THEN 2.00 ELSE 0 END + (({$tarifaLogisticaRipleyExpr}) / GREATEST(DetalleVenta.cantidad, 1)) ELSE 0 END";

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
                         SUM(({$costoExpr} + ({$comisionFalabellaExpr}) + ({$comisionRipleyExpr})) * DetalleVenta.cantidad + ({$costosComponentesSub})) as costos,
                         SUM(({$comisionFalabellaExpr}) * DetalleVenta.cantidad) as comision_falabella,
                         SUM(({$comisionRipleyExpr}) * DetalleVenta.cantidad) as comision_ripley,
                         {$subqueryTcDia} as tc_dia")
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->groupBy('Venta.idVenta', 'Venta.fechaVenta', 'Venta.idUser', 'Usuario.user')
            ->orderByDesc('Venta.fechaVenta')
            ->get()
            ->map(function ($venta) {
                $venta->ingresos = round($venta->ingresos, 2);
                $venta->costos   = round($venta->costos, 2);
                $venta->ganancia = round($venta->ingresos - $venta->costos, 2);
                $venta->comision_falabella = round($venta->comision_falabella, 2);
                $venta->comision_ripley = round($venta->comision_ripley, 2);
                $venta->tc_dia   = round((float)$venta->tc_dia, 3);

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
     * Devuelve las ganancias SOLO de las ventas de Ripley.
     */
    public function getRipleyGanancias()
    {
        $tc = $this->calculadoraService->getTasaCambio();
        $subqueryTipoCambioCosto = "COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE(c_inner.fechaRegistro))) ASC LIMIT 1), $tc)";
        $costoExpr = $this->calculadoraService->getCostoVentaExpr($subqueryTipoCambioCosto, (string)$tc);

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

        $subqueryTcDia = "COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE(Venta.fechaVenta))) ASC LIMIT 1), $tc)";

        $pesoRipleySubquery = "COALESCE(
            (SELECT CAST(REPLACE(REPLACE(cp52.caracteristicaProducto, ' KG', ''), ',', '.') AS DECIMAL(10,3))
             FROM caracteristicas_producto cp52
             WHERE cp52.idProducto = Producto.idProducto AND cp52.idCaracteristica = 52 LIMIT 1),
            (SELECT CAST(REPLACE(REPLACE(cp10.caracteristicaProducto, ' KG', ''), ',', '.') AS DECIMAL(10,3))
             FROM caracteristicas_producto cp10
             WHERE cp10.idProducto = Producto.idProducto AND cp10.idCaracteristica = 10 LIMIT 1)
        )";
        $tarifaLogisticaRipleyExpr = "CASE
            WHEN ({$pesoRipleySubquery}) IS NULL THEN 0
            WHEN ({$pesoRipleySubquery}) <= 0.50  THEN 4.90
            WHEN ({$pesoRipleySubquery}) <= 1.00  THEN 4.90
            WHEN ({$pesoRipleySubquery}) <= 3.00  THEN 5.90
            WHEN ({$pesoRipleySubquery}) <= 8.00  THEN 9.90
            WHEN ({$pesoRipleySubquery}) <= 25.00 THEN 12.90
            WHEN ({$pesoRipleySubquery}) <= 40.00 THEN 15.90
            WHEN ({$pesoRipleySubquery}) <= 150.00 THEN 28.90
            WHEN ({$pesoRipleySubquery}) <= 260.00 THEN 40.90
            ELSE 40.90 END";
            
        // Ripley comision: 12% + S/2 si precio <= 39
        $comisionRipleySoloExpr = "(DetalleVenta.precioVenta * 0.12) + CASE WHEN DetalleVenta.precioVenta <= 39.00 THEN 2.00 ELSE 0 END";
        $comisionRipleyTotalExpr = "({$comisionRipleySoloExpr}) + (({$tarifaLogisticaRipleyExpr}) / GREATEST(DetalleVenta.cantidad, 1))";

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
                         SUM(({$costoExpr} + ({$comisionRipleyTotalExpr})) * DetalleVenta.cantidad + ({$costosComponentesSub})) as costos,
                         SUM(({$comisionRipleyTotalExpr}) * DetalleVenta.cantidad) as comision_ripley,
                         SUM(({$comisionRipleySoloExpr}) * DetalleVenta.cantidad) as comision_ripley_base,
                         SUM(({$tarifaLogisticaRipleyExpr}) / GREATEST(DetalleVenta.cantidad, 1) * DetalleVenta.cantidad) as tarifa_peso_ripley,
                         {$subqueryTcDia} as tc_dia")
            ->whereRaw("UPPER(Venta.canal) = 'RIPLEY'")
            ->where('DetalleVenta.precioVenta', '>', 0)
            ->groupBy('Venta.idVenta', 'Venta.fechaVenta', 'Venta.idUser', 'Usuario.user')
            ->orderByDesc('Venta.fechaVenta')
            ->get()
            ->map(function ($venta) {
                $venta->ingresos = round($venta->ingresos, 2);
                $venta->costos   = round($venta->costos, 2);
                $venta->ganancia = round($venta->ingresos - $venta->costos, 2);
                $venta->comision_ripley = round($venta->comision_ripley, 2);
                $venta->comision_ripley_base = round($venta->comision_ripley_base, 2);
                $venta->tarifa_peso_ripley = round($venta->tarifa_peso_ripley, 2);
                $venta->tc_dia   = round((float)$venta->tc_dia, 3);

                // Deduplicar modelos
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
        $subqueryTipoCambioCosto = "COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE(c_inner.fechaRegistro))) ASC LIMIT 1), $tc)";
        $costoExpr = $this->calculadoraService->getCostoVentaExpr($subqueryTipoCambioCosto, (string)$tc);

        $comisionFalabellaExpr = "CASE WHEN UPPER(Venta.canal) = 'FALABELLA' THEN 
                (CASE WHEN GrupoProducto.idCategoria IN (1, 3) OR GrupoProducto.idGrupoProducto IN (10, 40, 41, 42, 43) THEN 10.90 ELSE 3.90 END)
                + (DetalleVenta.precioVenta * CASE WHEN GrupoProducto.idGrupoProducto = 10 THEN 0.08 WHEN GrupoProducto.idGrupoProducto IN (155, 156, 157, 158, 159, 160, 169) THEN 0.15 ELSE 0.10 END)
            ELSE 0 END";

        $pesoRipleySubquery = "COALESCE(
            (SELECT CAST(REPLACE(REPLACE(cp52.caracteristicaProducto, ' KG', ''), ',', '.') AS DECIMAL(10,3))
             FROM caracteristicas_producto cp52
             WHERE cp52.idProducto = Producto.idProducto AND cp52.idCaracteristica = 52 LIMIT 1),
            (SELECT CAST(REPLACE(REPLACE(cp10.caracteristicaProducto, ' KG', ''), ',', '.') AS DECIMAL(10,3))
             FROM caracteristicas_producto cp10
             WHERE cp10.idProducto = Producto.idProducto AND cp10.idCaracteristica = 10 LIMIT 1)
        )";
        $tarifaLogisticaRipleyExpr = "CASE
            WHEN ({$pesoRipleySubquery}) IS NULL THEN 0
            WHEN ({$pesoRipleySubquery}) <= 0.50  THEN 4.90
            WHEN ({$pesoRipleySubquery}) <= 1.00  THEN 4.90
            WHEN ({$pesoRipleySubquery}) <= 3.00  THEN 5.90
            WHEN ({$pesoRipleySubquery}) <= 8.00  THEN 9.90
            WHEN ({$pesoRipleySubquery}) <= 25.00 THEN 12.90
            WHEN ({$pesoRipleySubquery}) <= 40.00 THEN 15.90
            WHEN ({$pesoRipleySubquery}) <= 150.00 THEN 28.90
            WHEN ({$pesoRipleySubquery}) <= 260.00 THEN 40.90
            ELSE 40.90 END";
        $comisionRipleyExpr = "CASE WHEN UPPER(Venta.canal) = 'RIPLEY' THEN (DetalleVenta.precioVenta * 0.12) + CASE WHEN DetalleVenta.precioVenta <= 39.00 THEN 2.00 ELSE 0 END + (({$tarifaLogisticaRipleyExpr}) / GREATEST(DetalleVenta.cantidad, 1)) ELSE 0 END";

        $subqueryTcDia = "COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE(Venta.fechaVenta))) ASC LIMIT 1), $tc)";

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
                         (({$costoExpr} + ({$comisionFalabellaExpr}) + ({$comisionRipleyExpr})) * DetalleVenta.cantidad) as costos,
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
            ->map(function ($detalle) {
                $detalle->ingresos = round($detalle->ingresos, 2);
                $detalle->costos   = round($detalle->costos, 2);
                $detalle->ganancia = round($detalle->ingresos - $detalle->costos, 2);
                $detalle->comision_falabella = round($detalle->comision_falabella, 2);
                $detalle->comision_ripley = round($detalle->comision_ripley, 2);
                $detalle->tc_dia   = round((float)$detalle->tc_dia, 3);
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
        $subqueryTipoCambioCosto = "COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE(c_inner.fechaRegistro))) ASC LIMIT 1), $tc)";
        $costoExpr = $this->calculadoraService->getCostoVentaExpr($subqueryTipoCambioCosto, (string)$tc);

        $comisionFalabellaExpr = "CASE WHEN UPPER(Venta.canal) = 'FALABELLA' THEN 
                (CASE WHEN GrupoProducto.idCategoria IN (1, 3) OR GrupoProducto.idGrupoProducto IN (10, 40, 41, 42, 43) THEN 10.90 ELSE 3.90 END)
                + (DetalleVenta.precioVenta * CASE WHEN GrupoProducto.idGrupoProducto = 10 THEN 0.08 WHEN GrupoProducto.idGrupoProducto IN (155, 156, 157, 158, 159, 160, 169) THEN 0.15 ELSE 0.10 END)
            ELSE 0 END";

        $pesoRipleySubquery = "COALESCE(
            (SELECT CAST(REPLACE(REPLACE(cp52.caracteristicaProducto, ' KG', ''), ',', '.') AS DECIMAL(10,3))
             FROM caracteristicas_producto cp52
             WHERE cp52.idProducto = Producto.idProducto AND cp52.idCaracteristica = 52 LIMIT 1),
            (SELECT CAST(REPLACE(REPLACE(cp10.caracteristicaProducto, ' KG', ''), ',', '.') AS DECIMAL(10,3))
             FROM caracteristicas_producto cp10
             WHERE cp10.idProducto = Producto.idProducto AND cp10.idCaracteristica = 10 LIMIT 1)
        )";
        $tarifaLogisticaRipleyExpr = "CASE
            WHEN ({$pesoRipleySubquery}) IS NULL THEN 0
            WHEN ({$pesoRipleySubquery}) <= 0.50  THEN 4.90
            WHEN ({$pesoRipleySubquery}) <= 1.00  THEN 4.90
            WHEN ({$pesoRipleySubquery}) <= 3.00  THEN 5.90
            WHEN ({$pesoRipleySubquery}) <= 8.00  THEN 9.90
            WHEN ({$pesoRipleySubquery}) <= 25.00 THEN 12.90
            WHEN ({$pesoRipleySubquery}) <= 40.00 THEN 15.90
            WHEN ({$pesoRipleySubquery}) <= 150.00 THEN 28.90
            WHEN ({$pesoRipleySubquery}) <= 260.00 THEN 40.90
            ELSE 40.90 END";
        $comisionRipleyExpr = "CASE WHEN UPPER(Venta.canal) = 'RIPLEY' THEN (DetalleVenta.precioVenta * 0.12) + CASE WHEN DetalleVenta.precioVenta <= 39.00 THEN 2.00 ELSE 0 END + (({$tarifaLogisticaRipleyExpr}) / GREATEST(DetalleVenta.cantidad, 1)) ELSE 0 END";

        $subqueryTcDia = "COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE(Venta.fechaVenta))) ASC LIMIT 1), $tc)";

        $venta = Venta::query()
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->selectRaw("Venta.idVenta, Venta.fechaVenta,
                         GROUP_CONCAT(Producto.modelo SEPARATOR ', ') as modelo,
                         SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos,
                         SUM((($costoExpr) + ($comisionFalabellaExpr) + ($comisionRipleyExpr)) * DetalleVenta.cantidad) as costos,
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
