<?php

namespace App\Services;

use App\Repositories\VentaRepositoryInterface;
use App\Repositories\DetalleVentaRepositoryInterface;
use App\Services\HeaderServiceInterface;
use Illuminate\Support\Facades\DB;

class VentaService implements VentaServiceInterface
{
    protected $ventaRepository;
    protected $detalleVentaRepository;
    protected $headerService;
    protected $egresoService;

    public function __construct(
        VentaRepositoryInterface $ventaRepository,
        DetalleVentaRepositoryInterface $detalleVentaRepository,
        HeaderServiceInterface $headerService,
        EgresoProductoServiceInterface $egresoService
    ) {
        $this->ventaRepository = $ventaRepository;
        $this->detalleVentaRepository = $detalleVentaRepository;
        $this->headerService = $headerService;
        $this->egresoService = $egresoService;
    }

    public function createVenta(array $ventaData, array $detallesData)
    {
        DB::beginTransaction();
        try {
            // 1. Obtener el usuario activo
            $userModel = $this->headerService->getModelUser();
            $ventaData['idUser'] = $userModel->idUser;
            
            // Si no se envía fechaVenta, usar la actual
            if (!isset($ventaData['fechaVenta'])) {
                $ventaData['fechaVenta'] = now()->toDateString();
            }

            // 2. Crear la Venta (Cabecera)
            $venta = $this->ventaRepository->create($ventaData);

            $totalVenta = 0;

            // 3. Crear los Detalles y sumar el total
            foreach ($detallesData as $detalle) {
                // Validación para asegurar que hay un precio
                $precio = isset($detalle['precioVenta']) ? floatval($detalle['precioVenta']) : 0;
                $cantidad = isset($detalle['cantidad']) ? intval($detalle['cantidad']) : 1;
                
                $detalle['idVenta'] = $venta->idVenta;
                $detalle['precioVenta'] = $precio;
                $detalle['cantidad'] = $cantidad;
                
                // Si viene de una publicación, el origen es PUBLICACION, si no TIENDA
                if (!isset($detalle['origenPrecio'])) {
                    $detalle['origenPrecio'] = !empty($detalle['idPublicacion']) ? 'PUBLICACION' : 'TIENDA';
                }

                // Lógica automática: heredar precio si no se proporcionó
                if (!isset($detalle['precioVenta']) || $detalle['precioVenta'] == 0) {
                    if ($detalle['origenPrecio'] === 'PUBLICACION' && !empty($detalle['idPublicacion'])) {
                        $publicacion = \App\Models\Publicacion::find($detalle['idPublicacion']);
                        if ($publicacion) {
                            $precio = $publicacion->precioPublicacion;
                        }
                    } else if (!empty($detalle['idProducto'])) {
                        $producto = \App\Models\Producto::find($detalle['idProducto']);
                        if ($producto) {
                            // Convertir precioDolar a local o usarlo directo, aquí usamos precioDolar por defecto
                            $precio = $producto->precioDolar;
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
