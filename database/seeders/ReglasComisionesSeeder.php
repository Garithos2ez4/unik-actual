<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReglasComisionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Ecommerce\ReglaComision::truncate();

        // --- FALABELLA ---
        \App\Models\Ecommerce\ReglaComision::create([
            'plataforma' => 'FALABELLA',
            'nombre_regla' => 'Fijo Grupos 10,40,41,42,43',
            'tipo_condicion' => 'GRUPO_IN',
            'valor_condicion' => '10,40,41,42,43',
            'porcentaje_comision' => null,
            'monto_fijo' => 10.90,
            'prioridad' => 10,
        ]);

        \App\Models\Ecommerce\ReglaComision::create([
            'plataforma' => 'FALABELLA',
            'nombre_regla' => 'Fijo Categorías 1 y 3',
            'tipo_condicion' => 'CATEGORIA_IN',
            'valor_condicion' => '1,3',
            'porcentaje_comision' => null,
            'monto_fijo' => 10.90,
            'prioridad' => 10,
        ]);

        \App\Models\Ecommerce\ReglaComision::create([
            'plataforma' => 'FALABELLA',
            'nombre_regla' => 'Comisión 8% Grupo 10',
            'tipo_condicion' => 'GRUPO_IN',
            'valor_condicion' => '10',
            'porcentaje_comision' => 0.08,
            'monto_fijo' => null,
            'prioridad' => 10,
        ]);

        \App\Models\Ecommerce\ReglaComision::create([
            'plataforma' => 'FALABELLA',
            'nombre_regla' => 'Comisión Base Falabella',
            'tipo_condicion' => 'DEFAULT',
            'valor_condicion' => null,
            'porcentaje_comision' => 0.10,
            'monto_fijo' => 3.90,
            'prioridad' => 1,
        ]);

        \App\Models\Ecommerce\ReglaComision::create([
            'plataforma' => 'FALABELLA',
            'nombre_regla' => 'Comisión 15% Grupos (155..169)',
            'tipo_condicion' => 'GRUPO_IN',
            'valor_condicion' => '155, 156, 157, 158, 159, 160, 169',
            'porcentaje_comision' => 0.15,
            'monto_fijo' => null,
            'prioridad' => 10,
        ]);

        // --- RIPLEY ---
        \App\Models\Ecommerce\ReglaComision::create([
            'plataforma' => 'RIPLEY',
            'nombre_regla' => 'Recargo items <= 39',
            'tipo_condicion' => 'PRECIO_MENOR_IGUAL',
            'valor_condicion' => '39.00',
            'porcentaje_comision' => null,
            'monto_fijo' => 2.00,
            'prioridad' => 10,
        ]);

        \App\Models\Ecommerce\ReglaComision::create([
            'plataforma' => 'RIPLEY',
            'nombre_regla' => 'Comisión Base Ripley',
            'tipo_condicion' => 'DEFAULT',
            'valor_condicion' => null,
            'porcentaje_comision' => 0.12,
            'monto_fijo' => 0.00,
            'prioridad' => 1,
        ]);
    }
}
