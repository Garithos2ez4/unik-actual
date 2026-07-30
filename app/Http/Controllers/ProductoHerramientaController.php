<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroProducto;
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
            $registro->save();

            return response()->json(['success' => true, 'message' => 'Estado de herramienta actualizado correctamente.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar serie: ' . $e->getMessage()]);
        }
    }
}
