<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioProveedorDetalle extends Model
{
    use HasFactory;

    protected $table = 'InventarioProveedorDetalle';
    protected $primaryKey = 'idDetalle';

    protected $fillable = [
        'keyword',
        'productos_encontrados',
        'productos_actualizados',
        'fecha_ejecucion',
        'detalles_json',
        'recomendados_json'
    ];

    protected $casts = [
        'fecha_ejecucion' => 'datetime',
        'detalles_json' => 'array',
        'recomendados_json' => 'array',
    ];
}
