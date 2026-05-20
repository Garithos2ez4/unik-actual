<?php

namespace App\Models;

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
