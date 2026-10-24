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
        // Atributos descriptivos de variante (solo para ficha/selector web)
        'tipo_variante',     // Ej: COLOR, CAPACIDAD
        'valor_variante',    // Ej: Negro / Gris, Rosa, Blanco
        'codigo_color_hex',  // Ej: #212529
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
