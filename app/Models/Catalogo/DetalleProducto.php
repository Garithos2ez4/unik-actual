<?php

namespace App\Models\Catalogo;

use Illuminate\Database\Eloquent\Model;

class DetalleProducto extends Model
{
    protected $table = 'DetalleProducto';
    protected $primaryKey = 'idDetalleProducto';

    protected $fillable = [
        'idProducto',
        'mostrarPrecioWeb',
        'precio_pase',
    ];

    protected $casts = [
        'idDetalleProducto' => 'int',
        'idProducto' => 'int',
        'mostrarPrecioWeb' => 'boolean',
        'precio_pase' => 'float',
    ];

    public function Producto()
    {
        return $this->belongsTo(Producto::class, 'idProducto', 'idProducto');
    }
}
