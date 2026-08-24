<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlataformaMapperSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $idPlataforma = 1; // Falabella

        // Categorías con plantilla express
        $catExpress = [1, 6, 10];
        foreach ($catExpress as $idCat) {
            $mapperClass = match($idCat) {
                1 => \App\Services\Falabella\Mappers\LaptopMapper::class,
                6 => \App\Services\Falabella\Mappers\ImpresoraMapper::class,
                default => \App\Services\Falabella\Mappers\MonitorMapper::class,
            };

            \App\Models\Catalogo\PlataformaMapper::create([
                'idPlataforma' => $idPlataforma,
                'idCategoria' => $idCat,
                'idGrupoProducto' => null,
                'tipo_template' => 'express',
                'mapper_class' => $mapperClass,
            ]);
        }

        // Categoría con plantilla completa (y también habilitamos express según la lógica actual)
        \App\Models\Catalogo\PlataformaMapper::create([
            'idPlataforma' => $idPlataforma,
            'idCategoria' => 3,
            'idGrupoProducto' => null,
            'tipo_template' => 'completo',
            'mapper_class' => \App\Services\Falabella\Mappers\MonitorMapper::class,
        ]);
        
        // El código actual dice in_array($idCat, [1,3,6,10]) para mostrar Falabella y luego idCat === 3 para completo,
        // lo que significa que la 3 tiene AMBOS express y completo. 
        // Agregamos también express para la categoría 3 para que coincida.
        \App\Models\Catalogo\PlataformaMapper::create([
            'idPlataforma' => $idPlataforma,
            'idCategoria' => 3,
            'idGrupoProducto' => null,
            'tipo_template' => 'express',
            'mapper_class' => \App\Services\Falabella\Mappers\MonitorMapper::class,
        ]);

        // Grupos con plantilla express
        $gruposExpress = [24, 25, 49, 117, 155, 156, 157, 158, 159, 160, 169, 68, 71, 72, 73, 74];
        foreach ($gruposExpress as $idGrp) {
            $mapperClass = match(true) {
                in_array($idGrp, [155, 156, 157, 158, 159, 160, 169]) => \App\Services\Falabella\Mappers\SuministrosMapper::class,
                in_array($idGrp, [49]) => \App\Services\Falabella\Mappers\ProcesadorMapper::class,
                in_array($idGrp, [24, 117]) => \App\Services\Falabella\Mappers\TecladoMapper::class,
                in_array($idGrp, [68, 71, 72, 73, 74]) => \App\Services\Falabella\Mappers\CableMapper::class,
                in_array($idGrp, [25]) => \App\Services\Falabella\Mappers\MouseMapper::class,
                default => \App\Services\Falabella\Mappers\MonitorMapper::class,
            };

            \App\Models\Catalogo\PlataformaMapper::create([
                'idPlataforma' => $idPlataforma,
                'idCategoria' => null,
                'idGrupoProducto' => $idGrp,
                'tipo_template' => 'express',
                'mapper_class' => $mapperClass,
            ]);
        }
    }
}
