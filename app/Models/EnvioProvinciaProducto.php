<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvioProvinciaProducto extends Model
{
    protected $table = 'envio_provincia_productos';
    protected $primaryKey = 'idEnvioProvinciaProducto';

    protected $fillable = [
        'idEnvioProvincia',
        'idProducto',
        'cantidad',
        'nota_producto'
    ];

    protected $casts = [
        'idEnvioProvinciaProducto' => 'int',
        'idEnvioProvincia' => 'int',
        'idProducto' => 'int',
        'cantidad' => 'int'
    ];

    public function EnvioProvincia()
    {
        return $this->belongsTo(EnvioProvincia::class, 'idEnvioProvincia', 'idEnvioProvincia');
    }

    public function Producto()
    {
        return $this->belongsTo(Producto::class, 'idProducto', 'idProducto');
    }
}
