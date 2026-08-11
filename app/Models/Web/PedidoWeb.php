<?php

namespace App\Models\Web;

use Illuminate\Database\Eloquent\Model;
use App\Models\Usuarios\Cliente;

class PedidoWeb extends Model
{
    protected $table = 'PedidoWeb';
    protected $primaryKey = 'idPedidoWeb';

    protected $fillable = [
        'idCliente',
        'codigoTransaccion',
        'pasarela',
        'total',
        'estado'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idCliente', 'idCliente');
    }

    public function detalles()
    {
        return $this->hasMany(DetallePedidoWeb::class, 'idPedidoWeb', 'idPedidoWeb');
    }
}
