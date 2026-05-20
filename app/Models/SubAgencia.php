<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubAgencia extends Model
{
    protected $table = 'sub_agencias';
    protected $primaryKey = 'idSubAgencia';

    protected $fillable = [
        'idAgencia',
        'idDestino',
        'nombre_oficina',
        'direccion',
        'telefono',
        'estado'
    ];

    protected $casts = [
        'idAgencia' => 'int',
        'idDestino' => 'int',
        'estado' => 'int'
    ];

    public function Agencia()
    {
        return $this->belongsTo(Agencia::class, 'idAgencia', 'idAgencia');
    }

    public function Destino()
    {
        return $this->belongsTo(Destino::class, 'idDestino', 'idDestino');
    }
}
