<?php

namespace App\Models\Ventas;
use App\Models\Inventario\EgresoProducto;
use App\Models\Inventario\RegistroProducto;

use Illuminate\Database\Eloquent\Model;

class Devolucion extends Model
{
    protected $table = 'devoluciones';
    protected $primaryKey = 'idDevolucion';
    protected $guarded = ['idDevolucion'];

    public function Egreso()
    {
        return $this->belongsTo(EgresoProducto::class, 'idEgreso', 'idEgreso');
    }

    public function RegistroProducto()
    {
        return $this->belongsTo(RegistroProducto::class, 'idRegistro', 'idRegistro');
    }
}
