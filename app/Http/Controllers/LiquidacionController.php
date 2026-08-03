<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Catalogo\Producto;
use App\Models\Catalogo\DetalleProducto;
use App\Models\Inventario\Liquidacion;
use Illuminate\Support\Facades\DB;
use App\Services\HeaderServiceInterface;

class LiquidacionController extends Controller
{
    protected $headerService;

    public function __construct(HeaderServiceInterface $headerService)
    {
        $this->headerService = $headerService;
    }

    public function index()
    {
        // 1. Obtener sugeridos (stock estancado > 1 año y que no estén en liquidación)
        // We use leftJoin to allow products that might not have a DetalleProducto record yet.
        $sugeridos = Producto::select(
                'Producto.idProducto', 
                'Producto.nombreProducto', 
                'Producto.imagenProducto1',
                'Producto.precioDolar',
                'Producto.gananciaExtra'
            )
            ->join('DetalleComprobante', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->join('RegistroProducto', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('IngresoProducto', 'IngresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->leftJoin('Liquidacion', 'Liquidacion.idProducto', '=', 'Producto.idProducto')
            ->where('RegistroProducto.estado', 'NUEVO')
            ->where('IngresoProducto.fechaIngreso', '<=', now()->subYear())
            ->whereNull('Liquidacion.idLiquidacion')
            ->selectRaw('(SELECT SUM(stock) FROM Inventario WHERE Inventario.idProducto = Producto.idProducto) as stock_estancado')
            ->selectRaw('MIN(IngresoProducto.fechaIngreso) as fecha_mas_antigua')
            ->groupBy(
                'Producto.idProducto', 
                'Producto.nombreProducto', 
                'Producto.imagenProducto1',
                'Producto.precioDolar',
                'Producto.gananciaExtra'
            )
            ->havingRaw('COUNT(RegistroProducto.idRegistro) > 0 AND stock_estancado > 0')
            ->orderBy('fecha_mas_antigua', 'asc')
            ->get();

        // 2. Obtener los que ya están en liquidación
        $liquidados = Producto::select(
                'Producto.idProducto', 
                'Producto.nombreProducto', 
                'Producto.imagenProducto1',
                'Producto.precioDolar',
                'Producto.gananciaExtra',
                'Liquidacion.precio_liquidacion'
            )
            ->join('Liquidacion', 'Liquidacion.idProducto', '=', 'Producto.idProducto')
            ->get();

        // Inject current global TC for calculations
        $tcGlobal = \App\Models\Precios\Calculadora::find(2)->tasaCambio ?? 1;

        $user = $this->headerService->getModelUser();

        return view('productos.liquidacion', compact('sugeridos', 'liquidados', 'tcGlobal', 'user'));
    }

    public function agregarLiquidacion(Request $request)
    {
        $request->validate([
            'idProducto' => 'required|integer',
            'precio_liquidacion' => 'required|numeric'
        ]);

        $detalle = DetalleProducto::firstOrCreate(
            ['idProducto' => $request->idProducto],
            ['mostrarPrecioWeb' => true] // Defaults
        );

        Liquidacion::updateOrCreate(
            ['idProducto' => $request->idProducto],
            ['precio_liquidacion' => $request->precio_liquidacion]
        );

        $producto = Producto::find($request->idProducto);
        if ($producto) {
            $producto->estadoProductoWeb = 'LIQUIDACION';
            $producto->save();
        }

        return response()->json(['success' => true, 'message' => 'Producto agregado a liquidación correctamente.']);
    }

    public function quitarLiquidacion(Request $request)
    {
        $request->validate([
            'idProducto' => 'required|integer'
        ]);

        Liquidacion::where('idProducto', $request->idProducto)->delete();

        $producto = Producto::find($request->idProducto);
        if ($producto && $producto->estadoProductoWeb === 'LIQUIDACION') {
            $producto->estadoProductoWeb = 'DISPONIBLE';
            $producto->save();
        }

        return response()->json(['success' => true, 'message' => 'Producto retirado de liquidación.']);
    }

    public function buscarProductoAjax(Request $request)
    {
        $term = $request->input('q');
        if (!$term) {
            return response()->json([]);
        }

        $productos = Producto::where('estadoProductoWeb', '<>', 'DESCONTINUADO')
            ->where(function($query) use ($term) {
                $query->where('nombreProducto', 'LIKE', '%' . $term . '%')
                      ->orWhere('modelo', 'LIKE', '%' . $term . '%')
                      ->orWhere('partNumber', 'LIKE', '%' . $term . '%');
            })
            ->limit(20)
            ->get();

        $results = [];
        foreach ($productos as $p) {
            $results[] = [
                'id' => $p->idProducto,
                'nombre' => $p->nombreProducto,
                'text' => $p->nombreProducto . ' (Modelo: ' . $p->modelo . ')',
                'costoUsd' => $p->precioDolar
            ];
        }

        return response()->json(['results' => $results]);
    }
}
