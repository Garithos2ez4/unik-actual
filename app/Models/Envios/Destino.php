<?php

namespace App\Models\Envios;

use Illuminate\Database\Eloquent\Model;

class Destino extends Model
{
    protected $table = 'destinos';
    protected $primaryKey = 'idDestino';

    protected $fillable = [
        'idProvincia',
        'nombre'
    ];

    public function Provincia()
    {
        return $this->belongsTo(Provincia::class, 'idProvincia', 'idProvincia');
    }

    public function SubAgencias()
    {
        return $this->hasMany(SubAgencia::class, 'idDestino', 'idDestino');
    }
}
