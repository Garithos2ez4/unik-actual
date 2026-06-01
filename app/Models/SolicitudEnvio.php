<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudEnvio extends Model
{
    protected $table = 'solicitudes_envio';
    protected $primaryKey = 'idSolicitud';

    protected $fillable = [
        'token',
        'idUser',
        'estado',
        'token_expires_at'
    ];

    protected $casts = [
        'idUser' => 'int',
        'token_expires_at' => 'datetime'
    ];

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'idUser', 'idUser');
    }
}
