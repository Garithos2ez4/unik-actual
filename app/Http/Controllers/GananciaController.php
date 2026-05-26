<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\CalculadoraServiceInterface;

class GananciaController extends Controller
{
    protected $calculadoraService;

    public function __construct(CalculadoraServiceInterface $calculadoraService)
    {
        $this->calculadoraService = $calculadoraService;
    }


    private function costoUnitarioExpr(float $tc): string
    {
        return "COALESCE(
            (
                SELECT CASE WHEN c.moneda = 'DOLAR' THEN dc.precioUnitario * $tc ELSE dc.precioUnitario END
                FROM EgresoProducto ep
                INNER JOIN RegistroProducto rp ON rp.idRegistro = ep.idRegistro
                INNER JOIN DetalleComprobante dc ON dc.idDetalleComprobante = rp.idDetalleComprobante
                INNER JOIN Comprobante c ON c.idComprobante = dc.idComprobante
                WHERE ep.idEgreso = DetalleVenta.idEgreso
                  AND dc.precioUnitario > 1
                  AND c.numeroComprobante NOT LIKE 'INVENTARIO%'
                LIMIT 1
            ),
            COALESCE(Producto.precioDolar, 0) * $tc
        )";
    }

    /**
     * Devuelve las ganancias de todas las ventas.
     */
    public function getAllGanancias()
    {
        $tc = $this->calculadoraService->getTasaCambio();
        $costoExpr = $this->costoUnitarioExpr($tc);

        $comisionFalabellaExpr = "CASE 
            WHEN UPPER(Venta.canal) = 'FALABELLA' THEN 
                (CASE WHEN GrupoProducto.idCategoria IN (1, 3) OR GrupoProducto.idGrupoProducto IN (10, 40, 41, 42, 43) THEN 10.90 ELSE 3.90 END)
                + (DetalleVenta.precioVenta * CASE WHEN GrupoProducto.idGrupoProducto = 10 THEN 0.08 ELSE 0.10 END)
            ELSE 0 
        END";

        $ganancias = DB::table('Venta')
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->select(
                'Venta.idVenta',
                'Venta.fechaVenta',
                DB::raw("GROUP_CONCAT(Producto.modelo SEPARATOR ', ') as modelo"),
                DB::raw('SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos'),
                DB::raw("SUM((($costoExpr) + ($comisionFalabellaExpr)) * DetalleVenta.cantidad) as costos")
            )
            ->groupBy('Venta.idVenta', 'Venta.fechaVenta')
            ->orderBy('Venta.fechaVenta', 'desc')
            ->get()
            ->map(function ($venta) {
                $venta->ingresos = round($venta->ingresos, 2);
                $venta->costos   = round($venta->costos, 2);
                $venta->ganancia = round($venta->ingresos - $venta->costos, 2);
                return $venta;
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
        $costoExpr = $this->costoUnitarioExpr($tc);

        $comisionFalabellaExpr = "CASE 
            WHEN UPPER(Venta.canal) = 'FALABELLA' THEN 
                (CASE WHEN GrupoProducto.idCategoria IN (1, 3) OR GrupoProducto.idGrupoProducto IN (10, 40, 41, 42, 43) THEN 10.90 ELSE 3.90 END)
                + (DetalleVenta.precioVenta * CASE WHEN GrupoProducto.idGrupoProducto = 10 THEN 0.08 ELSE 0.10 END)
            ELSE 0 
        END";

        $venta = DB::table('Venta')
            ->join('DetalleVenta', 'Venta.idVenta', '=', 'DetalleVenta.idVenta')
            ->leftJoin('Producto', 'DetalleVenta.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('GrupoProducto', 'Producto.idGrupo', '=', 'GrupoProducto.idGrupoProducto')
            ->select(
                'Venta.idVenta',
                'Venta.fechaVenta',
                DB::raw("GROUP_CONCAT(Producto.modelo SEPARATOR ', ') as modelo"),
                DB::raw('SUM(DetalleVenta.precioVenta * DetalleVenta.cantidad) as ingresos'),
                DB::raw("SUM((($costoExpr) + ($comisionFalabellaExpr)) * DetalleVenta.cantidad) as costos")
            )
            ->where('Venta.idVenta', $idVenta)
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

        return response()->json([
            'success'  => true,
            'tc_usado' => $tc,
            'data'     => $venta
        ]);
    }
}
