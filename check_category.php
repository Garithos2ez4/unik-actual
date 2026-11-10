<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$p = \App\Models\Catalogo\Producto::find(2006);
$grupo = \App\Models\Catalogo\GrupoProducto::find($p->idGrupo);
$cat = $grupo ? \App\Models\Catalogo\CategoriaProducto::find($grupo->idCategoria) : null;

echo "PRODUCTO 2006:\n";
echo "idGrupo: " . $p->idGrupo . "\n";
echo "Grupo actual: " . ($grupo ? $grupo->nombreGrupo : 'NULL') . "\n";
echo "Categoria actual: " . ($cat ? $cat->nombreCategoria : 'NULL') . " (ID: " . ($cat ? $cat->idCategoria : 'NULL') . ")\n";

echo "\n--- GRUPOS DISPONIBLES RELACIONADOS A AUDIO / AURICULARES / AUDIFONOS / HEADSET ---\n";
$grupos = \App\Models\Catalogo\GrupoProducto::with('CategoriaProducto')
    ->where(function($q) {
        $q->where('nombreGrupo', 'like', '%audio%')
          ->orWhere('nombreGrupo', 'like', '%audif%')
          ->orWhere('nombreGrupo', 'like', '%auric%')
          ->orWhere('nombreGrupo', 'like', '%head%')
          ->orWhere('nombreGrupo', 'like', '%headset%')
          ->orWhere('nombreGrupo', 'like', '%perif%');
    })
    ->get();

foreach ($grupos as $g) {
    echo "- idGrupo: {$g->idGrupoProducto} | Grupo: '{$g->nombreGrupo}' | Categoria: '{$g->CategoriaProducto?->nombreCategoria}' (ID Cat: {$g->idCategoria})\n";
}

echo "\n--- TODAS LAS CATEGORIAS Y SUS GRUPOS ---\n";
$todasCat = \App\Models\Catalogo\CategoriaProducto::with('GrupoProducto')->get();
foreach ($todasCat as $c) {
    echo "Categoria [{$c->idCategoria}] {$c->nombreCategoria}:\n";
    foreach ($c->GrupoProducto as $gr) {
        echo "   -> [idGrupo: {$gr->idGrupoProducto}] {$gr->nombreGrupo}\n";
    }
}
