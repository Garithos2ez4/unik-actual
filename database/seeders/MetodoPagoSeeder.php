<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MetodoPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insertar Tipos de Método de Pago
        DB::table('TipoMetodoPago')->insert([
            ['idTipoMetodo' => 1, 'nombreTipo' => 'EFECTIVO'],
            ['idTipoMetodo' => 2, 'nombreTipo' => 'BILLETERA DIGITAL'],
            ['idTipoMetodo' => 3, 'nombreTipo' => 'TRANSFERENCIA'],
            ['idTipoMetodo' => 4, 'nombreTipo' => 'PASARELA WEB'],
            ['idTipoMetodo' => 5, 'nombreTipo' => 'TARJETA (POS)'],
        ]);

        // Insertar Métodos de Pago
        DB::table('MetodoPago')->insert([
            ['idMetodoPago' => 1, 'idTipoMetodo' => 1, 'idBanco' => null, 'nombreMetodo' => 'Efectivo', 'estado' => 1],
            ['idMetodoPago' => 2, 'idTipoMetodo' => 2, 'idBanco' => 1,    'nombreMetodo' => 'Yape', 'estado' => 1],
            ['idMetodoPago' => 3, 'idTipoMetodo' => 2, 'idBanco' => null, 'nombreMetodo' => 'Plin', 'estado' => 1],
            ['idMetodoPago' => 4, 'idTipoMetodo' => 3, 'idBanco' => null, 'nombreMetodo' => 'Transferencia', 'estado' => 1],
            ['idMetodoPago' => 5, 'idTipoMetodo' => 4, 'idBanco' => null, 'nombreMetodo' => 'Mercado Pago', 'estado' => 1],
            ['idMetodoPago' => 6, 'idTipoMetodo' => 4, 'idBanco' => null, 'nombreMetodo' => 'Link de Pago', 'estado' => 1],
        ]);
    }
}
