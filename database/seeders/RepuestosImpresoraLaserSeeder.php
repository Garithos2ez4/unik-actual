<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catalogo\CategoriaProducto;
use App\Models\Catalogo\GrupoProducto;
use App\Models\Precios\Comision;
use App\Models\Precios\RangoPrecio;
use Illuminate\Support\Str;

class RepuestosImpresoraLaserSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Categoria
        $catName = 'Repuestos de Impresora Laser';
        $categoria = CategoriaProducto::where('nombreCategoria', $catName)->first();

        if (!$categoria) {
            $nextIdCategoria = CategoriaProducto::max('idCategoria') + 1;
            CategoriaProducto::create([
                'idCategoria' => $nextIdCategoria,
                'nombreCategoria' => $catName,
                'iconCategoria' => 'bi bi-printer',
                'slugCategoria' => Str::slug($catName)
            ]);
            $categoria = CategoriaProducto::where('nombreCategoria', $catName)->first();
        }

        $idCategoriaReal = $categoria->idCategoria;

        $gruposData = [
            ['Caja de Toner Residual / Waste Toner BOX', 'caja-de-toner-residual.jpg'],
            ['Cuchilla de Limpieza', 'cuchilla-de-limpieza.jpg'],
            ['kit de Mantenimiento', 'kit-de-mantenimiento.jpg'],
            ['Drum / Tambor OPC / Cilindro de Imagen', 'drum-tambor-opc.jpg'],
            ['Rodillo Fusor / Unidad de Fusor / Rodillo Termico', 'rodillo-fusor.jpg'],
            ['Rodillo de Presion', 'rodillo-de-presion.jpg'],
            ['rodillo termico', 'rodillo-termico.jpg'],
            ['Rodillo Magnetico / Mag Roller', 'rodillo-magnetico.jpg'],
            ['Rodillo de Carga Principal PCR', 'rodillo-de-carga-principal-pcr.jpg'],
            ['Rodillos de Impresora / Pickup Roller', 'rodillos-de-impresora.jpg'],
            ['Rodillos de Duplex de Impresora', 'rodillos-de-duplex-de-impresora.jpg'],
            ['Rodillos de ADF de Impresora', 'rodillos-de-adf-de-impresora.jpg'],
            ['Rodillos de ADF de Escaner', 'rodillos-de-adf-de-escaner.jpg'],
            ['Fuente de Alimentacion de Impresora', 'fuente-de-alimentacion-de-impresora.jpg'],
            ['Teflon de Impresora', 'teflon-de-impresora.jpg'],
            ['Unidad Laser', 'unidad-laser.jpg'],
            ['Panel de Control de Impresora Laser', 'panel-de-control-de-impresora-laser.jpg'],
            ['Motor de Impresora Laser', 'motor-de-impresora-laser.jpg'],
            ['Scanner / Escaner / ADF', 'scanner-escaner-adf.jpg']
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
