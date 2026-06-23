<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UbicacionAlmacen extends Model
{
    protected $table = 'UbicacionAlmacen';
    protected $primaryKey = 'idUbicacion';
    
    protected $fillable = [
        'idAlmacen',
        'nombre',
        'descripcion'
    ];

    public function Almacen()
    {
        return $this->belongsTo(Almacen::class, 'idAlmacen', 'idAlmacen');
    }
}
