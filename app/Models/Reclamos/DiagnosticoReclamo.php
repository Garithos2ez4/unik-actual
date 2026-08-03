<?php

namespace App\Models\Reclamos;
use App\Models\Usuarios\Usuario;
use App\Models\Inventario\RegistroProducto;

use Illuminate\Database\Eloquent\Model;

class DiagnosticoReclamo extends Model
{
    protected $table = 'diagnosticos_reclamo';
    protected $primaryKey = 'idDiagnostico';

    protected $fillable = [
        'idReclamoPlataforma',
        'idRegistro',
        'idUser',
        'situacionActual',
        'diagnosticoPrevio',
        'descripcionDiagnostico',
        'respuestaSolucion',
        'estadoEvolucion'
    ];

    protected $casts = [
        'idReclamoPlataforma' => 'int',
        'idRegistro' => 'int',
        'idUser' => 'int'
    ];

    public function Reclamo()
    {
        return $this->belongsTo(ReclamoPlataforma::class, 'idReclamoPlataforma', 'idReclamoPlataforma');
    }

    public function Tecnico()
    {
        return $this->belongsTo(Usuario::class, 'idUser', 'idUser');
    }

    public function ProductoFisico()
    {
        return $this->belongsTo(RegistroProducto::class, 'idRegistro', 'idRegistro');
    }
}
