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

    public function getVentasLaptopsAio()
    {
        $ventas = \App\Models\Ventas\Venta::with([
            'Cliente',
            'Usuario',
            'DetallesVenta.Producto.MarcaProducto',
            'DetallesVenta.EgresoProducto.RegistroProducto.DetalleComprobante.Comprobante'
        ])
            ->whereDate('fechaVenta', '>=', '2026-06-01')
            ->whereHas('DetallesVenta.Producto', function ($query) {
                // Grupos: 1=Laptop Gamer, 2=Laptop Empresarial, 3=Laptop Hogar, 10=PC All in One
                $query->whereIn('idGrupo', [1, 2, 3, 10]);
            })
            ->orderBy('idVenta', 'desc')
            ->get()
            ->map(function ($venta) {
                $tasaCambio = \App\Models\Precios\Calculadora::first()->tasaCambio ?? 3.70;

                $laptop = null;
                $componentes = [];
                $costoLaptop = 0;
                $costoComponentes = 0;
                $precioVentaTotal = 0;

                foreach ($venta->DetallesVenta as $detalle) {
                    // Calcular costo dinámico (igual que en GananciaController)
                    $costoUnitario = 0;
                    if ($detalle->EgresoProducto && $detalle->EgresoProducto->RegistroProducto && $detalle->EgresoProducto->RegistroProducto->DetalleComprobante) {
                        $dc = $detalle->EgresoProducto->RegistroProducto->DetalleComprobante;
                        $moneda = $dc->Comprobante->moneda ?? 'SOLES';
                        $precioCompra = $dc->precioUnitario ?? 0;
                        if (strtoupper($moneda) === 'DOLAR' || strtoupper($moneda) === 'USD') {
                            $costoUnitario = $precioCompra * $tasaCambio;
                        } else {
                            $costoUnitario = $precioCompra;
                        }
                    } else {
                        $precioDolar = $detalle->Producto->precioDolar ?? 0;
                        $costoUnitario = $precioDolar * $tasaCambio * 1.18;
                    }

                    $costoFila = $costoUnitario * $detalle->cantidad;
                    $precioFila = $detalle->precioVenta * $detalle->cantidad;
                    $precioVentaTotal += $precioFila;

                    if (in_array($detalle->Producto->idGrupo ?? 0, [1, 2, 3, 10]) && !$laptop) {
                        $laptop = $detalle;
                        $costoLaptop += $costoFila;
                    } else {
                        $componentes[] = [
                            'producto' => $detalle->Producto->nombreProducto ?? '',
                            'modelo' => $detalle->Producto->modelo ?? '',
                            'cantidad' => $detalle->cantidad,
                            'precioVenta' => round($precioFila, 2),
                            'costo' => round($costoFila, 2),
                        ];
                        $costoComponentes += $costoFila;
                    }
                }

                return [
                    'idVenta' => $venta->idVenta,
                    'fechaVenta' => $venta->fechaVenta,
                    'canal' => $venta->canal,
                    'cliente' => $venta->Cliente->nombreCliente ?? 'Anónimo',
                    'vendedor' => $venta->Usuario->user ?? '',
                    'producto_principal' => $laptop->Producto->nombreProducto ?? '',
                    'modelo_principal' => $laptop->Producto->modelo ?? '',
                    'marca_principal' => $laptop->Producto->MarcaProducto->nombreMarca ?? '',
                    'cantidad_principal' => $laptop->cantidad ?? 1,
                    'precio_venta_total' => round($precioVentaTotal, 2),
                    'costo_laptop' => round($costoLaptop, 2),
                    'costo_componentes' => round($costoComponentes, 2),
                    'costo_total' => round($costoLaptop + $costoComponentes, 2),
                    'ganancia' => round($precioVentaTotal - ($costoLaptop + $costoComponentes), 2),
                    'componentes' => $componentes
                ];
            });

        return response()->json($ventas);
    }
}
