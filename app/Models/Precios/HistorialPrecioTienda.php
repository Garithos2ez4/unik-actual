<?php

namespace App\Models\Precios;
use App\Models\Catalogo\Producto;
use App\Models\Usuarios\Usuario;

use Illuminate\Database\Eloquent\Model;

class HistorialPrecioTienda extends Model
{
    public $timestamps = false;

    protected $table = 'HistorialPrecioTienda';

    protected $primaryKey = 'idHistorial';

    protected $guarded = ['idHistorial'];

    protected $fillable = [
        'idProducto',
        'precioAnterior',
        'precioNuevo',
        'idUser',
        'created_at',
    ];

    protected $casts = [
        'idHistorial'    => 'int',
        'idProducto'     => 'int',
        'precioAnterior' => 'float',
        'precioNuevo'    => 'float',
        'idUser'         => 'int',
        'created_at'     => 'datetime',
    ];

    public function Producto()
    {
        return $this->belongsTo(Producto::class, 'idProducto', 'idProducto');
    }

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'idUser', 'idUser');
    }
}
