<?php

namespace App\Models\Envios;

use Illuminate\Database\Eloquent\Model;

class EnvioProvinciaReceptor extends Model
{
    protected $table = 'envio_provincia_receptores';

    protected $fillable = [
        'id_envio_provincia_detalle',
        'nombre',
        'dni',
        'telefono'
    ];

    public function Detalle()
    {
        return $this->belongsTo(EnvioProvinciaDetalle::class, 'id_envio_provincia_detalle', 'idEnvioProvinciaDetalle');
    }
}
