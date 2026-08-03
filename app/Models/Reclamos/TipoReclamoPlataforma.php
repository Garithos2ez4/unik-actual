<?php

namespace App\Models\Reclamos;

use Illuminate\Database\Eloquent\Model;

class TipoReclamoPlataforma extends Model
{
    protected $table = 'Tipo_reclamos_plataforma';
    protected $primaryKey = 'idTipoReclamoPlataforma';

    protected $fillable = [
        'nombreTipoReclamo',
        'estado'
    ];

    public function Reclamos()
    {
        return $this->hasMany(ReclamoPlataforma::class, 'idTipoReclamoPlataforma', 'idTipoReclamoPlataforma');
    }
}
