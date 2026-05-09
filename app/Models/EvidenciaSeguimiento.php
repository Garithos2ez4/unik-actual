<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenciaSeguimiento extends Model
{
    protected $table = 'evidencias_seguimiento';
    protected $primaryKey = 'idEvidencia';

    protected $fillable = [
        'idSeguimiento',
        'tipoEvidencia',
        'urlArchivo'
    ];

    public function Seguimiento()
    {
        return $this->belongsTo(SeguimientoReclamo::class, 'idSeguimiento', 'idSeguimiento');
    }
}
