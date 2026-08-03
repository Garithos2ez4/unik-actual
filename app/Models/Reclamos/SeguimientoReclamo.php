<?php

namespace App\Models\Reclamos;
use App\Models\Envios\EvidenciaSeguimiento;
use App\Models\Usuarios\Usuario;

use Illuminate\Database\Eloquent\Model;

class SeguimientoReclamo extends Model
{
    protected $table = 'seguimientos_reclamo';
    protected $primaryKey = 'idSeguimiento';

    protected $fillable = [
        'idReclamoPlataforma',
        'idUser',
        'respondioCanal',
        'mensajeRespuesta' // <-- Nuevo campo agregado
    ];

    // Relación: Un seguimiento tiene MUCHAS evidencias
    public function Evidencias()
    {
        return $this->hasMany(EvidenciaSeguimiento::class, 'idSeguimiento', 'idSeguimiento');
    }

    public function Reclamo()
    {
        return $this->belongsTo(ReclamoPlataforma::class, 'idReclamoPlataforma', 'idReclamoPlataforma');
    }

    public function Operador()
    {
        return $this->belongsTo(Usuario::class, 'idUser', 'idUser');
    }
}
