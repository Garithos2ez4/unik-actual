<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OlvaTarifaController extends Controller
{
    /**
     * Devuelve la lista de cajas oficiales de Olva Courier con sus medidas (Según Manual COM-M-03).
     */
    public function getCajas()
    {
        $cajas = [
            [
                'id' => 'sobre_burbupack',
                'nombre' => 'Sobre Burbupack (N° 1)',
                'largo' => 25,
                'ancho' => 18,
                'alto' => 1, // Es un sobre plano
                'precio' => 2.01
            ],
            [
                'id' => 'caja_chiquita',
                'nombre' => 'Caja Chiquita',
                'largo' => 14,
                'ancho' => 10,
                'alto' => 10,
                'precio' => 0.00 // <-- Actualizar precio de esta nueva caja
            ],
            [
                'id' => 'caja_2',
                'nombre' => 'Caja N° 2',
                'largo' => 18,
                'ancho' => 10,
                'alto' => 14,
                'precio' => 1.81
            ],
            [
                'id' => 'caja_3',
                'nombre' => 'Caja Chica (N° 3)',
                'largo' => 26,
                'ancho' => 16.5,
                'alto' => 12,
                'precio' => 1.99
            ],
            [
                'id' => 'caja_4',
                'nombre' => 'Caja Mediana (N° 4)',
                'largo' => 31,
                'ancho' => 22,
                'alto' => 26,
                'precio' => 3.50
            ],
            [
                'id' => 'caja_5',
                'nombre' => 'Caja Grande (N° 5)',
                'largo' => 39,
                'ancho' => 22,
                'alto' => 31,
                'precio' => 4.20
            ],
            [
                'id' => 'caja_6',
                'nombre' => 'Caja para Documentos (N° 6)',
                'largo' => 39,
                'ancho' => 22,
                'alto' => 31,
                'precio' => 2.50
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $cajas
        ]);
    }
}
