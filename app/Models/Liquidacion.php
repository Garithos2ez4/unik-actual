<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Liquidacion extends Model
{
    protected $table = 'Liquidacion';
    protected $primaryKey = 'idLiquidacion';

    protected $fillable = [
        'idProducto',
        'precio_liquidacion',
    ];

    protected $casts = [
        'idLiquidacion' => 'int',
        'idProducto' => 'int',
        'precio_liquidacion' => 'float',
    ];

    public function Producto()
    {
        return $this->belongsTo(Producto::class, 'idProducto', 'idProducto');
    }
}
