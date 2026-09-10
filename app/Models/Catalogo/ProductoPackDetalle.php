<?php

namespace App\Models\Catalogo;

use Illuminate\Database\Eloquent\Model;

class ProductoPackDetalle extends Model
{
    public $timestamps = false;
    
    protected $table = 'ProductoPackDetalle';
    
    protected $primaryKey = 'idDetalle';

    protected $fillable = [
        'idGrupoProducto'
    ];

    /**
     * El grupo de producto al que pertenece este detalle de pack
     */
    public function GrupoProducto()
    {
        return $this->belongsTo(GrupoProducto::class, 'idGrupoProducto', 'idGrupoProducto');
    }
}
