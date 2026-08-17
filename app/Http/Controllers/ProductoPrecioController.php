<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Catalogo\Producto;
use App\Services\CalculadoraServiceInterface;
use App\Services\PreciosService;

class ProductoPrecioController extends Controller
{
    protected $calculadoraService;

    public function __construct(CalculadoraServiceInterface $calculadoraService)
    {
        $this->calculadoraService = $calculadoraService;
    }

    public function getUtilidad($idProducto)
    {
        $producto = Producto::with('GrupoProducto')->find($idProducto);
        if (!$producto) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado'], 404);
        }

        $igv = 1.18;
        $precioConIgv = round($producto->precioDolar * $igv, 2);

        $preciosService = new PreciosService;
        $precioCalculado = $preciosService->getPrecioCalculado(
            $producto->precioDolar,
            $producto->idGrupo,
            'DOLAR',
            $producto->estadoProductoWeb
        );

        $usarTcFijo = $producto->usar_tc_fijo ?? true;
        if ($usarTcFijo) {
            $tcUsar = (float)$this->calculadoraService->getTasaCambio();
            $tipoTcLabel = 'SUNAT';
        } else {
            $tasaFijaGlobal = (float)$this->calculadoraService->getTasaFija()->tasaCambio;
            if (isset($producto->tc_fijo) && $producto->tc_fijo > 0) {
                $tcUsar = (float)$producto->tc_fijo;
                $tipoTcLabel = 'Fijo';
            } else {
                $tcUsar = $tasaFijaGlobal;
                $tipoTcLabel = 'Fijo Global';
            }
        }

        $precioVentaUsd = $precioCalculado + $producto->gananciaExtra;
        $precioVentaSoles = round($precioVentaUsd * $tcUsar, 2);

        // Si el producto está en liquidación, el precio web final es el precio de liquidación
        if ($producto->estadoProductoWeb === 'LIQUIDACION' && $producto->liquidacion) {
            $precioVentaSoles = $producto->liquidacion->precio_liquidacion;
            $precioVentaUsd = round($precioVentaSoles / $tcUsar, 2);
        }

        $precioBaseSoles = round($producto->precioDolar * $tcUsar, 2);
        $precioIgvSoles = round($precioConIgv * $tcUsar, 2);

        // Fetch último costo desde compras
        $ultimoDetalle = \App\Models\Ventas\DetalleComprobante::with('Comprobante')
            ->where('idProducto', $idProducto)
            ->orderBy('idDetalleComprobante', 'desc')
            ->first();

        $ultimoCosto = null;
        if ($ultimoDetalle && $ultimoDetalle->Comprobante) {
            // precioUnitario incluye IGV, por lo tanto el precio sin IGV es precioUnitario / 1.18
            $costoSinIgv = $ultimoDetalle->precioUnitario / 1.18;
            
            // Si el comprobante fue en SOLES, lo convertimos a DOLARES con el TC actual
            if ($ultimoDetalle->Comprobante->moneda === 'SOL') {
                $ultimoCosto = round($costoSinIgv / $tcUsar, 2);
            } else {
                $ultimoCosto = round($costoSinIgv, 2);
            }
        }

        // Calculate multiplier for $1 base price to use in frontend JS
        $precioCalculadoMultiplier = $preciosService->getPrecioCalculado(
            1,
            $producto->idGrupo,
            'DOLAR',
            $producto->estadoProductoWeb
        );

        return response()->json([
            'success' => true,
            'nombreProducto' => $producto->nombreProducto,
            'precioDolar' => $producto->precioDolar,
            'precioConIgv' => $precioConIgv,
            'precioBaseSoles' => $precioBaseSoles,
            'precioIgvSoles' => $precioIgvSoles,
            'gananciaExtra' => $producto->gananciaExtra,
            'precioCalculado' => round($precioCalculado, 2),
            'precioCalculadoMultiplier' => $precioCalculadoMultiplier,
            'precioVentaUsd' => round($precioVentaUsd, 2),
            'precioVentaSoles' => $precioVentaSoles,
            'tasaCambio' => $tcUsar,
            'tipoTcLabel' => $tipoTcLabel,
            'ultimoCosto' => $ultimoCosto,
            'isLiquidacion' => ($producto->estadoProductoWeb === 'LIQUIDACION'),
            'precioLiquidacionSoles' => ($producto->estadoProductoWeb === 'LIQUIDACION' && $producto->liquidacion) ? $producto->liquidacion->precio_liquidacion : null
        ]);
    }

    public function updateUtilidad(Request $request)
    {
        $request->validate([
            'idProducto' => 'required|integer',
            'ganancia' => 'required|numeric',
            'precioDolar' => 'nullable|numeric'
        ]);

        $producto = Producto::find($request->idProducto);
        if (!$producto) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado'], 404);
        }

        $producto->gananciaExtra = $request->ganancia;
        if ($request->has('precioDolar') && $request->precioDolar !== null) {
            $producto->precioDolar = $request->precioDolar;
        }
        $producto->save();

        return response()->json([
            'success' => true,
            'message' => 'Utilidad actualizada correctamente'
        ]);
    }

    public function calculate(Request $request)
    {
        $precio = $request->input('price');
        $moneda = $request->input('type');
        $grupo = $request->input('idGrupo');
        $estado = $request->input('state');
        $ganancia = $request->input('ganancia');

        $servicePrecio = new PreciosService;
        $precios = array();
        $precios[] = ['calculado' => $servicePrecio->getPrecioCalculado($precio, $grupo, $moneda, $estado)];
        $precios[] = ['total' => $servicePrecio->getPrecioTotal($precio, $grupo, $moneda, $estado, $ganancia)];
        $results = $precios;

        return response()->json($results);
    }

    public function obtenerHistorialPreciosHtml($id)
    {
        try {
            $producto = Producto::findOrFail($id);

            $historial = \Illuminate\Support\Facades\DB::select("
                SELECT
                    c.fechaRegistro,
                    c.numeroComprobante,
                    c.moneda,
                    c.totalCompra,
                    dc.precioUnitario,
                    dc.precioCompra,
                    dc.medida,
                    pv.nombreProveedor,
                    htc.tasa_cambio
                FROM DetalleComprobante dc
                INNER JOIN Comprobante c ON c.idComprobante = dc.idComprobante
                LEFT JOIN Preveedor pv ON pv.idProveedor = c.idProveedor
                LEFT JOIN historial_tipo_cambio htc ON htc.fecha = c.fechaRegistro
                WHERE dc.idProducto = ?
                AND c.estado != 'INVALIDO'
                ORDER BY c.fechaRegistro DESC
            ", [$id]);

            $html = view('productos.partials.modal_historial_precios_body', compact('producto', 'historial'))->render();
            $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

            return response()->json(['html' => $html]);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerHistorialPreciosHtml: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['error' => 'Error interno del servidor.'], 500);
        }
    }

    public function obtenerHistorialPrecioTienda($id)
    {
        try {
            $historial = \App\Models\Precios\HistorialPrecioTienda::with('Usuario')
                ->where('idProducto', $id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'fecha' => \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i'),
                        'precioAnterior' => $item->precioAnterior,
                        'precioNuevo' => $item->precioNuevo,
                        'usuario' => $item->Usuario ? $item->Usuario->user : null
                    ];
                });

            return response()->json($historial);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerHistorialPrecioTienda: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor.'], 500);
        }
    }
}
