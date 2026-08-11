<?php

namespace App\Models\Web;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalogo\Producto;

class DetallePedidoWeb extends Model
{
    protected $table = 'Detalle_PedidoWeb';
    protected $primaryKey = 'idDetallePedidoWeb';

    protected $fillable = [
        'idPedidoWeb',
        'idProducto',
        'cantidad',
        'precio'
    ];

    public function pedido()
    {
        return $this->belongsTo(PedidoWeb::class, 'idPedidoWeb', 'idPedidoWeb');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'idProducto', 'idProducto');
    }
}
