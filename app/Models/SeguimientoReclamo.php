<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguimientoReclamo extends Model
{
    protected $table = 'seguimientos_reclamo';
    protected $primaryKey = 'idSeguimiento';

    protected $fillable = [
        'idReclamoPlataforma',
        'idUser',
        'contactoRealizado',
        'respondioCanal',
        'graboVideo',
        'tomoFoto'
    ];

    protected $casts = [
        'idReclamoPlataforma' => 'int',
        'idUser' => 'int',
        'graboVideo' => 'boolean',
        'tomoFoto' => 'boolean'
    ];

    public function Reclamo()
    {
        return $this->belongsTo(ReclamoPlataforma::class, 'idReclamoPlataforma', 'idReclamoPlataforma');
    }

    public function Operador()
    {
        return $this->belongsTo(Usuario::class, 'idUser', 'idUser');
    }
}
