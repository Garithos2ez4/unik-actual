<?php

namespace App\Models\Precios;

use Illuminate\Database\Eloquent\Model;

class HistorialTipoCambio extends Model
{
    protected $table = 'historial_tipo_cambio';

    protected $fillable = [
        'fecha',
        'tasa_cambio'
    ];

    protected $casts = [
        'fecha' => 'date',
        'tasa_cambio' => 'float',
    ];
}
