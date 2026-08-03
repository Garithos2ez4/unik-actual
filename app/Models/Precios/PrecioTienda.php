<?php

namespace App\Models\Precios;
use App\Models\Catalogo\Producto;

use Illuminate\Database\Eloquent\Model;

class PrecioTienda extends Model
{
    public $timestamps = false;

    protected $table = 'PrecioTienda';

    protected $primaryKey = 'idPrecioTienda';

    protected $guarded = ['idPrecioTienda'];

    protected $fillable = [
        'idProducto',
        'precioTienda',
        'updated_at',
    ];

    protected $casts = [
        'idPrecioTienda' => 'int',
        'idProducto'     => 'int',
        'precioTienda'   => 'float',
    ];

    public function Producto()
    {
        return $this->belongsTo(Producto::class, 'idProducto', 'idProducto');
    }
}
