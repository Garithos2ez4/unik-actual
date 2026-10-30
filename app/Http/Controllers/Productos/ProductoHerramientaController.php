<?php

namespace App\Http\Controllers\Productos;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Inventario\RegistroProducto;
use Exception;

class ProductoHerramientaController extends Controller
{
    public function getSeriesHerramienta($idProducto)
    {
        try {
            // El ID enviado desde openServiciosModal({{ $producto->idProducto }}) viene sin encriptar.
            $idProd = $idProducto;

            // Buscar todas las series (RegistroProducto) asociadas a este producto, excluyendo las entregadas
            $series = RegistroProducto::whereHas('DetalleComprobante', function ($q) use ($idProd) {
                $q->where('idProducto', $idProd);
            })->where('estado', '!=', 'ENTREGADO')->with(['Almacen'])->get();

            $formatted = $series->map(function ($serie) {
                return [
                    'idRegistro' => $serie->idRegistro,
                    'numeroSerie' => $serie->numeroSerie,
                    'estado' => $serie->estado,
                    'almacen' => $serie->Almacen->descripcion ?? 'Desconocido',
                    'es_herramienta' => (bool)$serie->es_herramienta
                ];
            });

            return response()->json(['success' => true, 'series' => $formatted]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al obtener series: ' . $e->getMessage()]);
        }
    }

    public function toggleHerramienta(Request $request)
    {
        try {
            $idRegistro = $request->input('idRegistro');
            $es_herramienta = $request->boolean('es_herramienta');

            if (!$idRegistro) {
                return response()->json(['success' => false, 'message' => 'ID de registro no proporcionado.']);
            }

            $registro = RegistroProducto::find($idRegistro);

            if (!$registro) {
                return response()->json(['success' => false, 'message' => 'Serie no encontrada.']);
            }

            $registro->es_herramienta = $es_herramienta;
            if ($es_herramienta) {
                // Si se marca como herramienta de servicio/taller, pasa a EN_USO
                $registro->estado = 'EN_USO';
            } else {
                // Si se quita el check de herramienta, regresa a NUEVO (disponible para venta)
                $registro->estado = 'NUEVO';
            }
            $registro->save();

            // Sincronizar el inventario físico (stock) del almacén correspondiente
            $idProducto = $registro->DetalleComprobante->idProducto ?? null;
            $idAlmacen = $registro->idAlmacen;
            if ($idProducto && $idAlmacen) {
                $detalles = \App\Models\Ventas\DetalleComprobante::where('idProducto', $idProducto)->pluck('idDetalleComprobante');
                $nuevoStock = RegistroProducto::whereIn('idDetalleComprobante', $detalles)
                    ->where('idAlmacen', $idAlmacen)
                    ->whereIn('estado', ['NUEVO', 'ABIERTO', 'DEVOLUCION'])
                    ->where('es_herramienta', 0)
                    ->count();

                \App\Models\Inventario\Inventario::updateOrCreate(
                    ['idProducto' => $idProducto, 'idAlmacen' => $idAlmacen],
                    ['stock' => $nuevoStock]
                );

                $productoService = app(\App\Services\ProductoService::class);
                $productoService->validateState($idProducto, true);
            }

            return response()->json([
                'success' => true,
                'message' => $es_herramienta 
                    ? 'Serie marcada como herramienta de servicio (EN USO).' 
                    : 'Serie desmarcada. Estado restablecido a NUEVO y disponible en stock.',
                'nuevo_estado' => $registro->estado,
                'es_herramienta' => (bool)$registro->es_herramienta
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar serie: ' . $e->getMessage()]);
        }
    }
}

