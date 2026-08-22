<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catalogo\CategoriaProducto;
use App\Models\Catalogo\GrupoProducto;
use App\Models\Precios\Comision;
use App\Models\Precios\RangoPrecio;
use Illuminate\Support\Str;

class InsumosHerramientasSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Categoria
        $catName = 'Insumos / Herramientas de Impresoras';
        $categoria = CategoriaProducto::where('nombreCategoria', $catName)->first();

        if (!$categoria) {
            $nextIdCategoria = CategoriaProducto::max('idCategoria') + 1;
            CategoriaProducto::create([
                'idCategoria' => $nextIdCategoria,
                'nombreCategoria' => $catName,
                'iconCategoria' => 'bi bi-tools',
                'slugCategoria' => Str::slug($catName)
            ]);
            $categoria = CategoriaProducto::where('nombreCategoria', $catName)->first();
        }

        $idCategoriaReal = $categoria->idCategoria;

        $gruposData = [
            ['Bomba Limpia Cabezal', 'bomba-limpia-cabezal.jpg'],
            ['Grasa de Impresora', 'grasa-de-impresora.jpg'],
            ['Liquido de Limpieza de Cabezal / Washing', 'liquido-de-limpieza-de-cabezal.jpg'],
            ['Programador de EEPROM / JIG', 'programador-de-eeprom-jig.jpg'],
            ['Purgador de Cabezal / kit de Limpieza de Cabezal / Cartuchos', 'purgador-de-cabezal.jpg'],
            ['Purgador de aire de las mangueras', 'purgador-de-aire-de-las-mangueras.jpg'],
            ['Reseteador de Impresora Fisicos / Resetter', 'reseteador-de-impresora-fisicos.jpg'],
            ['Reseteador de Impresora Software', 'reseteador-de-impresora-software.jpg'],
            ['Tanque CISS / tanque de sistema continuo', 'tanque-ciss.jpg'],
            ['Tina Ultrasonido / Ultrasonica', 'tina-ultrasonido.jpg']
        ];

        $rangos = RangoPrecio::all();

        foreach ($gruposData as $item) {
            $nombreGrupo = $item[0];
            $imageName = $item[1];
            $slugGrupo = Str::slug($nombreGrupo);
            
            $grupo = GrupoProducto::where('nombreGrupo', $nombreGrupo)->first();
            
            if (!$grupo) {
                $nextIdGrupo = GrupoProducto::max('idGrupoProducto') + 1;
                GrupoProducto::create([
                    'idGrupoProducto' => $nextIdGrupo,
                    'nombreGrupo' => $nombreGrupo,
                    'idCategoria' => $idCategoriaReal,
                    'idTipoProducto' => 1,
                    'slugGrupo' => $slugGrupo,
                    'imagenGrupo' => 'grupos/' . $imageName
                ]);
                $grupo = GrupoProducto::where('nombreGrupo', $nombreGrupo)->first();
            }
            
            $idGrupoReal = $grupo->idGrupoProducto;

            // Create Comisiones if not exist
            foreach ($rangos as $rango) {
                Comision::firstOrCreate([
                    'idGrupoProducto' => $idGrupoReal,
                    'idRango' => $rango->idRango
                ], [
                    'comision' => 0
                ]);
            }
        }
    }
}
