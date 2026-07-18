<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CategoriaProducto;
use App\Models\GrupoProducto;

class ScannerRepuestosSeeder extends Seeder
{
    public function run()
    {
        $categoria = CategoriaProducto::firstOrCreate(
            ['nombreCategoria' => 'REPUESTOS DE SCANER / ESCANER'],
            [
                'idCategoria' => CategoriaProducto::max('idCategoria') + 1,
                'iconCategoria' => 'bi bi-upc-scan'
            ]
        );

        $maxGrupoId = GrupoProducto::max('idGrupoProducto') ?? 0;

        $grupos = [
            [
                'nombre' => 'Rodillos de recogida',
                'imagen' => 'grupos/rodillos-recogida.webp'
            ],
            [
                'nombre' => 'Rodillos de separacion',
                'imagen' => 'grupos/rodillos-separacion.webp'
            ],
            [
                'nombre' => 'Rodillos de Frenos',
                'imagen' => 'grupos/rodillos-frenos.webp'
            ],
            [
                'nombre' => 'kit Completo de Rodillos / Alimentacion',
                'imagen' => 'grupos/kit-rodillos.webp'
            ]
        ];

        foreach ($grupos as $g) {
            $grupo = GrupoProducto::where('nombreGrupo', $g['nombre'])->first();
            if (!$grupo) {
                $maxGrupoId++;
                GrupoProducto::create([
                    'idGrupoProducto' => $maxGrupoId,
                    'nombreGrupo' => $g['nombre'],
                    'imagenGrupo' => $g['imagen'],
                    'idCategoria' => $categoria->idCategoria,
                    'idTipoProducto' => 7 // Repuesto
                ]);
            }
        }
    }
}
