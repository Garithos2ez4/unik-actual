<?php

namespace App\Models\Inventario;
use App\Models\Catalogo\Producto;

use Illuminate\Database\Eloquent\Model;

class DivisionPackDetalle extends Model
{
    public $timestamps = false;

    protected $table = 'DivisionPackDetalle';

    protected $primaryKey = 'idDivisionDetalle';

    protected $guarded = ['idDivisionDetalle'];

    protected $fillable = [
        'idDivisionDetalle',
        'idDivision',
        'idRegistroHijo',
        'idProductoHijo',
        'costo_asignado'
    ];

    protected $casts = [
        'idDivisionDetalle' => 'int',
        'idDivision' => 'int',
        'idRegistroHijo' => 'int',
        'idProductoHijo' => 'int',
        'costo_asignado' => 'decimal:2'
    ];

    public function DivisionPack()
    {
        return $this->belongsTo(DivisionPack::class, 'idDivision', 'idDivision');
    }

    public function RegistroProducto()
    {
        return $this->belongsTo(RegistroProducto::class, 'idRegistroHijo', 'idRegistro');
    }

    public function Producto()
    {
        return $this->belongsTo(Producto::class, 'idProductoHijo', 'idProducto');
    }
}
