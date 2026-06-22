<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvioProvinciaDetalle extends Model
{
    protected $table = 'envio_provincia_detalles';
    protected $primaryKey = 'idEnvioProvinciaDetalle';

    protected $fillable = [
        'idEnvioProvincia',
        'entrega_domicilio',
        'dir',
        'ref',
        'origen'
    ];

    public function EnvioProvincia()
    {
        return $this->belongsTo(EnvioProvincia::class, 'idEnvioProvincia', 'idEnvioProvincia');
    }
}
