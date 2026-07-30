<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleProducto extends Model
{
    protected $table = 'DetalleProducto';
    protected $primaryKey = 'idDetalleProducto';

    protected $fillable = [
        'idProducto',
        'mostrarPrecioWeb',
        'precio_pase',
        'en_liquidacion',
        'precio_liquidacion',
    ];

    protected $casts = [
        'idDetalleProducto' => 'int',
        'idProducto' => 'int',
        'mostrarPrecioWeb' => 'boolean',
        'precio_pase' => 'float',
        'en_liquidacion' => 'boolean',
        'precio_liquidacion' => 'float',
    ];

    public function Producto()
    {
        return $this->belongsTo(Producto::class, 'idProducto', 'idProducto');
    }
}
