<?php

namespace App\Models\Catalogo;

use Illuminate\Database\Eloquent\Model;

class ProductoPack extends Model
{
    public $timestamps = false;

    protected $table = 'ProductoPack';

    protected $primaryKey = 'idPack';

    protected $guarded = ['idPack'];

    protected $fillable = [
        'idPack',
        'idProductoPack',
        'idProductoHijo',
        'cantidad',
        'porcentaje_costo'
    ];

    protected $casts = [
        'idPack' => 'int',
        'idProductoPack' => 'int',
        'idProductoHijo' => 'int',
        'cantidad' => 'int',
        'porcentaje_costo' => 'decimal:2'
    ];

    /**
     * El producto pack (padre)
     */
    public function ProductoPadre()
    {
        return $this->belongsTo(Producto::class, 'idProductoPack', 'idProducto');
    }

    /**
     * El producto componente (hijo)
     */
    public function ProductoHijo()
    {
        return $this->belongsTo(Producto::class, 'idProductoHijo', 'idProducto');
    }
}
