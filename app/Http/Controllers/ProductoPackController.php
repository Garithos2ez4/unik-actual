<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use App\Models\ProductoPack;

class ProductoPackController extends Controller
{
    public function getPackComponents($idProducto)
    {
        $idPadre = decrypt($idProducto);
        $components = ProductoPack::with('ProductoHijo')
            ->where('idProductoPack', $idPadre)
            ->get();

        $formatted = $components->map(function ($comp) {
            return [
                'idProductoHijo' => $comp->idProductoHijo,
                'nombreProducto' => $comp->ProductoHijo->nombreProducto ?? 'Producto Desconocido',
                'cantidad' => $comp->cantidad,
                'porcentaje_costo' => $comp->porcentaje_costo
            ];
        });

        return response()->json(['success' => true, 'components' => $formatted]);
    }

    public function addPackComponent(Request $request, $idProducto)
    {
        try {
            $idPadre = decrypt($idProducto);
            $idHijo = $request->input('idHijo');
            $cantidad = $request->input('cantidad', 1);
            $porcentajeCosto = $request->input('porcentaje_costo', 0);

            if (!$idHijo || $cantidad < 1 || $porcentajeCosto < 0) {
                return response()->json(['success' => false, 'message' => 'Datos inválidos.']);
            }

            if ($idHijo == $idPadre) {
                return response()->json(['success' => false, 'message' => 'Un producto no puede ser componente de sí mismo.']);
            }

            $exists = ProductoPack::where('idProductoPack', $idPadre)
                ->where('idProductoHijo', $idHijo)
                ->first();

            if ($exists) {
                $exists->cantidad += $cantidad;
                $exists->porcentaje_costo = $porcentajeCosto;
                $exists->save();
            } else {
                ProductoPack::create([
                    'idProductoPack' => $idPadre,
                    'idProductoHijo' => $idHijo,
                    'cantidad' => $cantidad,
                    'porcentaje_costo' => $porcentajeCosto
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Componente agregado correctamente.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al agregar componente: ' . $e->getMessage()]);
        }
    }

    public function updatePackComponent(Request $request, $idProducto, $idHijo)
    {
        try {
            $idPadre  = decrypt($idProducto);
            $cantidad  = $request->input('cantidad', 1);
            $porcentajeCosto = $request->input('porcentaje_costo', 0);

            if ($cantidad < 1 || $porcentajeCosto < 0) {
                return response()->json(['success' => false, 'message' => 'Datos inválidos.']);
            }

            $comp = ProductoPack::where('idProductoPack', $idPadre)
                ->where('idProductoHijo', $idHijo)
                ->first();

            if (!$comp) {
                return response()->json(['success' => false, 'message' => 'Componente no encontrado.']);
            }

            $comp->cantidad         = $cantidad;
            $comp->porcentaje_costo = $porcentajeCosto;
            $comp->save();

            return response()->json(['success' => true, 'message' => 'Componente actualizado correctamente.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    public function removePackComponent($idProducto, $idHijo)
    {
        try {
            $idPadre = decrypt($idProducto);
            ProductoPack::where('idProductoPack', $idPadre)
                ->where('idProductoHijo', $idHijo)
                ->delete();

            return response()->json(['success' => true, 'message' => 'Componente removido correctamente.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al remover componente: ' . $e->getMessage()]);
        }
    }
}
