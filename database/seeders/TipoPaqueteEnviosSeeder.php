<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoPaqueteEnviosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paquetes = [
            [
                'nombre' => 'Sobre (Máx 0.25 kg)',
                'icono' => '✉️',
                'clave_api' => 'sobre',
                'dimensiones' => ['largo' => 0.1, 'ancho' => 0.15, 'alto' => 0.1, 'peso_maximo' => 0.25]
            ],
            [
                'nombre' => 'Caja Paquete XXS (15x10x10 - Máx 0.25 kg)',
                'icono' => '📦',
                'clave_api' => 'cajapaquetexxs',
                'dimensiones' => ['largo' => 0.1, 'ancho' => 0.15, 'alto' => 0.1, 'peso_maximo' => 0.25]
            ],
            [
                'nombre' => 'Caja Paquete XS (15x20x12 - Máx 0.5 kg)',
                'icono' => '📦',
                'clave_api' => 'cajapaquetexs',
                'dimensiones' => ['largo' => 0.2, 'ancho' => 0.15, 'alto' => 0.2, 'peso_maximo' => 0.5]
            ],
            [
                'nombre' => 'Caja Paquete S (20x30x12 - Máx 2 kg)',
                'icono' => '📦',
                'clave_api' => 'cajapaquetes',
                'dimensiones' => ['largo' => 0.3, 'ancho' => 0.2, 'alto' => 0.12, 'peso_maximo' => 2]
            ],
            [
                'nombre' => 'Caja Paquete M (24x30x20 - Máx 5 kg)',
                'icono' => '📦',
                'clave_api' => 'cajapaquetem',
                'dimensiones' => ['largo' => 0.2, 'ancho' => 0.24, 'alto' => 0.3, 'peso_maximo' => 5]
            ],
            [
                'nombre' => 'Caja Paquete L (42x30x23 - Máx 10 kg)',
                'icono' => '📦',
                'clave_api' => 'cajapaquetel',
                'dimensiones' => ['largo' => 0.3, 'ancho' => 0.42, 'alto' => 0.23, 'peso_maximo' => 10]
            ],
        ];

        // Buscar el ID de Shalom si existe, o dejarlo nulo
        $shalom = \App\Models\Agencia::where('nombre', 'SHALOM')->first();
        $idAgencia = $shalom ? $shalom->idAgencia : null;

        foreach ($paquetes as $pkg) {
            \App\Models\TipoPaqueteEnvio::create([
                'nombre' => $pkg['nombre'],
                'icono' => $pkg['icono'],
                'clave_api' => $pkg['clave_api'],
                'idAgencia' => $idAgencia,
                'largo_defecto' => $pkg['dimensiones']['largo'],
                'ancho_defecto' => $pkg['dimensiones']['ancho'],
                'alto_defecto' => $pkg['dimensiones']['alto'],
                'peso_maximo' => $pkg['dimensiones']['peso_maximo'],
                'estado' => 1
            ]);
        }
    }
}
