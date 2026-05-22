<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\VentaServiceInterface;
use App\Services\HeaderServiceInterface;

class VentaController extends Controller
{
    protected $ventaService;
    protected $headerService;

    public function __construct(
        VentaServiceInterface $ventaService,
        HeaderServiceInterface $headerService
    ) {
        $this->ventaService = $ventaService;
        $this->headerService = $headerService;
    }

    public function index()
    {
        $userModel = $this->headerService->getModelUser();
        // TODO: Agregar validación de vista/permisos similar a egresos
        $ventas = $this->ventaService->getAllVentas(50);

        // Devolvemos a una vista 'ventas.index' que crearemos después
        // return view('ventas.index', ['user' => $userModel, 'ventas' => $ventas]);
        return response()->json($ventas); // Temporal hasta que esté la vista
    }

    public function show($id)
    {
        $venta = $this->ventaService->getVentaById($id);
        if (!$venta) {
            return response()->json(['error' => 'Venta no encontrada'], 404);
        }
        $venta->load('DetallesVenta.Producto', 'Cliente', 'Usuario');
        
        return response()->json($venta);
    }

    public function store(Request $request)
    {
        // Esta función recibirá la petición con cabecera y detalles de la venta
        $userModel = $this->headerService->getModelUser();
        
        $ventaData = [
            'idCliente' => $request->input('idCliente'),
            'canal' => $request->input('canal', 'TIENDA'),
            'numeroOrden' => $request->input('numeroOrden'),
            'fechaVenta' => $request->input('fechaVenta'),
            'observacion' => $request->input('observacion'),
        ];

        $detallesData = $request->input('detalles', []); // Array de detalles

        if (empty($detallesData)) {
            $this->headerService->sendFlashAlerts('Error', 'La venta debe tener al menos un producto.', 'info', 'btn-warning');
            return back();
        }

        try {
            $venta = $this->ventaService->createVenta($ventaData, $detallesData);
            
            // TODO: Integrar con createEgreso() para hacer el descargo de stock si es necesario.
            // Actualmente createVenta solo crea la venta. El descargo físico de inventario ocurre en EgresoProductoService.
            
            $this->headerService->sendFlashAlerts('Venta registrada', 'Venta y detalles registrados exitosamente', 'success', 'btn-success');
            return back();

        } catch (\Exception $e) {
            $this->headerService->sendFlashAlerts('Error', $e->getMessage(), 'error', 'btn-danger');
            return back();
        }
    }
}
