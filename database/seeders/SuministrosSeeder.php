<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catalogo\CategoriaProducto;
use App\Models\Catalogo\GrupoProducto;
use App\Models\Precios\Comision;
use App\Models\Precios\RangoPrecio;
use Illuminate\Support\Str;

class SuministrosSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Categoria
        $catName = 'Suministros / Consumibles';
        $categoria = CategoriaProducto::where('nombreCategoria', $catName)->first();

        if (!$categoria) {
            $nextIdCategoria = CategoriaProducto::max('idCategoria') + 1;
            CategoriaProducto::create([
                'idCategoria' => $nextIdCategoria,
                'nombreCategoria' => $catName,
                'iconCategoria' => 'bi bi-box-seam',
                'slugCategoria' => Str::slug($catName)
            ]);
            $categoria = CategoriaProducto::where('nombreCategoria', $catName)->first();
        }

        $idCategoriaReal = $categoria->idCategoria;

        $gruposData = [
            ['Botella de Tinta Compatible / Alternativas / Genericas', 'IMGPRObotella-de-tinta-compatible-alternativas-genericas.png'],
            ['Botella de Tinta Original', 'IMGPRObotella-de-tinta-original.png'],
            ['Cartucho Recargable de Tinta', 'IMGPROcartucho-recargable-de-tinta.png'],
            ['Cartucho Compatible de Tinta', 'IMGPROcartucho-compatible-de-tinta.png'],
            ['Cartucho Original de Tinta', 'IMGPROcartucho-original-de-tinta.png'],
            ['Chip de Cartucho de Tinta', 'IMGPROchip-de-cartucho-de-tinta.png'],
            ['Chip de Cartucho de Toner', 'IMGPROchip-de-cartucho-de-toner.png'],
            ['Cinta para Impresora Matricial / Cinta Matricial', 'IMGPROcinta-para-impresora-matricial-cinta-matricial.png'],
            ['Discos / CD / DVD / Blu-RAY', 'IMGPROdiscos-cd-dvd-blu-ray.png'],
            ['Ribbon / Cinta de Transferencia Termica', 'IMGPROribbon-cinta-de-transferencia-termica.png'],
            ['Cinta Laminada', 'IMGPROcinta-laminada.png'],
            ['Rollo de Etiqueta Adhesiva', 'IMGPROrollo-de-etiqueta-adhesiva.png'],
            ['Rollo de papel termico / Contometro', 'IMGPROrollo-de-papel-termico-contometro.png'],
            ['Porta Fotocheck', 'IMGPROporta-fotocheck.png'],
            ['Tarjetasde PVC InkJet / Fotocheck de Tinta', 'IMGPROtarjetasde-pvc-inkjet-fotocheck-de-tinta.png'],
            ['Tarjeta de PVC Termicas / Fotocheck de Sublimacion', 'IMGPROtarjeta-de-pvc-termicas-fotocheck-de-sublimacion.png'],
            ['Papel Fotografico', 'IMGPROpapel-fotografico.png'], 
            ['Papel para Sublimar', 'IMGPROpapel-para-sublimar.png'],
            ['Toner Original', 'IMGPROtoner-original.png'], 
            ['Toner Compatible', 'IMGPROtoner-compatible.png'], 
            ['Toner Insumo / Polvo', 'IMGPROtoner-insumo-polvo.png'] 
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
