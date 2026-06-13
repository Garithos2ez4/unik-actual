<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvioDimension extends Model
{
    protected $table = 'envio_dimensiones';
    protected $primaryKey = 'idDimension';

    protected $fillable = [
        'idEnvioProvincia',
        'idTipoPaquete',
        'largo_final',
        'ancho_final',
        'alto_final',
        'peso_final',
        'precio_calculado'
    ];

    public function tipoPaquete()
    {
        return $this->belongsTo(TipoPaqueteEnvio::class, 'idTipoPaquete', 'idTipoPaquete');
    }
}
