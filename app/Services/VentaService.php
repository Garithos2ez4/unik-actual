<?php

namespace App\Services;

use App\Repositories\VentaRepositoryInterface;
use App\Repositories\DetalleVentaRepositoryInterface;
use App\Services\HeaderServiceInterface;
use App\Services\CalculadoraServiceInterface;
use Illuminate\Support\Facades\DB;

class VentaService implements VentaServiceInterface
{
    protected $ventaRepository;
    protected $detalleVentaRepository;
    protected $headerService;
    protected $egresoService;
    protected $calculadoraService;

    public function __construct(
        VentaRepositoryInterface $ventaRepository,
        DetalleVentaRepositoryInterface $detalleVentaRepository,
        HeaderServiceInterface $headerService,
        EgresoProductoServiceInterface $egresoService,
        CalculadoraServiceInterface $calculadoraService
    ) {
        $this->ventaRepository = $ventaRepository;
        $this->detalleVentaRepository = $detalleVentaRepository;
        $this->headerService = $headerService;
        $this->egresoService = $egresoService;
        $this->calculadoraService = $calculadoraService;
    }

    public function createVenta(array $ventaData, array $detallesData, array $pagos = [])
    {
        DB::beginTransaction();
        try {
            // 1. Obtener el usuario activo
            $userModel = $this->headerService->getModelUser();
            $ventaData['idUser'] = $userModel->idUser;
            
            // Si no se envía fechaVenta, usar la actual
            if (!isset($ventaData['fechaVenta'])) {
                $ventaData['fechaVenta'] = now()->toDateTimeString();
            }

            // 2. Crear la Venta (Cabecera)
            $venta = $this->ventaRepository->create($ventaData);

            $totalVenta = 0;

            // 3. Crear los Detalles y sumar el total
            foreach ($detallesData as $detalle) {
                // Validación para asegurar que hay un precio
                $precioProvisto = isset($detalle['precioVenta']) && $detalle['precioVenta'] !== '';
                $precio = $precioProvisto ? floatval($detalle['precioVenta']) : null;
                
                // Si el usuario digitó exactamente 0, guardarlo como 0.1 para evitar que herede el precio total
                if ($precioProvisto && $precio == 0) {
                    $precio = 0.1;
                }

                $cantidad = isset($detalle['cantidad']) ? intval($detalle['cantidad']) : 1;
                
                $detalle['idVenta'] = $venta->idVenta;
                $detalle['cantidad'] = $cantidad;
                
                // Si viene de una publicación, el origen es PUBLICACION, si no TIENDA
                if (!isset($detalle['origenPrecio'])) {
                    $detalle['origenPrecio'] = !empty($detalle['idPublicacion']) ? 'PUBLICACION' : 'TIENDA';
                }

                // Lógica automática: heredar precio si no se proporcionó (es nulo o string vacío)
                if (!$precioProvisto) {
                    $precio = 0; // Default en caso falle
                    if ($detalle['origenPrecio'] === 'PUBLICACION' && !empty($detalle['idPublicacion'])) {
                        $publicacion = \App\Models\Publicacion::find($detalle['idPublicacion']);
                        if ($publicacion) {
                            $precio = $publicacion->precioPublicacion;
                        }
                    } else if (!empty($detalle['idProducto'])) {
                        $producto = \App\Models\Producto::find($detalle['idProducto']);
                        if ($producto) {
                            $tasaCambio = $this->calculadoraService->obtenerCambioDolar() ?? 1;
                            $precio = $producto->precioDolar * $tasaCambio;
                        }
                    }
                }
                
                $detalle['precioVenta'] = $precio;

                $this->detalleVentaRepository->create($detalle);

                $totalVenta += ($precio * $cantidad);
            }

            // 4. Actualizar el Total de la Venta
            $venta->totalVenta = $totalVenta;
            $venta->save();

            // 5. Registrar Pagos
            if (!empty($pagos)) {
                foreach ($pagos as $pago) {
                    \App\Models\PagoVenta::create([
                        'idVenta' => $venta->idVenta,
                        'idMetodoPago' => $pago['idMetodo'],
                        'idCuentaBancaria' => $pago['idCuentaBancaria'] ?? null,
                        'monto' => $pago['monto'],
                        'nroOperacion' => $pago['ref'] ?? null,
                    ]);
                }
            } else {
                // Si no enviaron pagos pero es de tienda y hay un total, asume Efectivo
                if ($venta->canal === 'TIENDA' && $totalVenta > 0) {
                    $metodoEfectivo = \App\Models\MetodoPago::where('nombreMetodo', 'LIKE', '%Efectivo%')
                                                            ->orWhere('idMetodoPago', 1)
                                                            ->first();
                    if ($metodoEfectivo) {
                        \App\Models\PagoVenta::create([
                            'idVenta' => $venta->idVenta,
                            'idMetodoPago' => $metodoEfectivo->idMetodoPago,
                            'monto' => $totalVenta,
                            'nroOperacion' => null,
                        ]);
                    }
                }
            }

            DB::commit();

            return $venta;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getAllVentas($paginate = 50)
    {
        return $this->ventaRepository->getAll($paginate);
    }

    public function getVentaById($idVenta)
    {
        return $this->ventaRepository->getOne('idVenta', $idVenta);
    }
}
