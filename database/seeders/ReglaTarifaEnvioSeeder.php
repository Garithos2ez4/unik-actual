<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReglaTarifaEnvioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tarifasRipley = [
            ['plataforma' => 'RIPLEY', 'peso_maximo' => 0.50, 'monto_fijo' => 4.90],
            ['plataforma' => 'RIPLEY', 'peso_maximo' => 1.00, 'monto_fijo' => 4.90],
            ['plataforma' => 'RIPLEY', 'peso_maximo' => 3.00, 'monto_fijo' => 5.90],
            ['plataforma' => 'RIPLEY', 'peso_maximo' => 8.00, 'monto_fijo' => 9.90],
            ['plataforma' => 'RIPLEY', 'peso_maximo' => 25.00, 'monto_fijo' => 12.90],
            ['plataforma' => 'RIPLEY', 'peso_maximo' => 40.00, 'monto_fijo' => 15.90],
            ['plataforma' => 'RIPLEY', 'peso_maximo' => 150.00, 'monto_fijo' => 28.90],
            ['plataforma' => 'RIPLEY', 'peso_maximo' => 260.00, 'monto_fijo' => 40.90],
            ['plataforma' => 'RIPLEY', 'peso_maximo' => null, 'monto_fijo' => 40.90], // Default (ELSE)
        ];

        foreach ($tarifasRipley as $tarifa) {
            \App\Models\Ecommerce\ReglaTarifaEnvio::create($tarifa);
        }
    }
}
