<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertaPrecio extends Model
{
    protected $fillable = [
        'modelo',
        'mi_precio',
        'precio_competidor',
        'competidor',
        'diferencia_porcentaje',
        'sugerencia',
        'estado',
    ];
}
