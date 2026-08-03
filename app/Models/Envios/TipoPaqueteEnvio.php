<?php

namespace App\Models\Envios;

use Illuminate\Database\Eloquent\Model;

class TipoPaqueteEnvio extends Model
{
    protected $table = 'tipo_paquete_envios';
    protected $primaryKey = 'idTipoPaquete';

    protected $fillable = [
        'nombre',
        'icono',
        'clave_api',
        'idAgencia',
        'largo_defecto',
        'ancho_defecto',
        'alto_defecto',
        'peso_maximo',
        'estado'
    ];

    public function dimensiones()
    {
        return $this->hasOne(EnvioDimension::class, 'idTipoPaquete', 'idTipoPaquete');
    }
}
