<?php

namespace App\Models\Web;

use Illuminate\Database\Eloquent\Model;

class DireccionPedidoWeb extends Model
{
    protected $table = 'DireccionPedidoWeb';

    protected $fillable = [
        'pedido_web_id',
        'direccion',
        'latitud',
        'longitud',
    ];

    public function pedidoWeb()
    {
        return $this->belongsTo(PedidoWeb::class, 'pedido_web_id', 'idPedidoWeb');
    }
}
