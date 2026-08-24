<?php

namespace App\Models\Catalogo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlataformaMapper extends Model
{
    use HasFactory;

    protected $table = 'plataforma_mappers';

    protected $fillable = [
        'idPlataforma',
        'idCategoria',
        'idGrupoProducto',
        'tipo_template',
        'mapper_class'
    ];

    public function plataforma()
    {
        return $this->belongsTo(\App\Models\Empresa\Plataforma::class, 'idPlataforma', 'idPlataforma');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaProducto::class, 'idCategoria', 'idCategoria');
    }

    public function grupoProducto()
    {
        return $this->belongsTo(GrupoProducto::class, 'idGrupoProducto', 'idGrupoProducto');
    }
}
