<?php

namespace App\Models\Reclamos;
use App\Models\Usuarios\Usuario;
use App\Models\Usuarios\Cliente;
use App\Models\Empresa\Plataforma;
use App\Models\Empresa\CuentasPlataforma;
use App\Models\Catalogo\Publicacion;
use App\Models\Inventario\RegistroProducto;

use Illuminate\Database\Eloquent\Model;

class ReclamoPlataforma extends Model
{
    protected $table = 'reclamos_plataforma';
    protected $primaryKey = 'idReclamoPlataforma';

    protected $fillable = [
        'codigoReclamo',
        'idUser',
        'idCliente',
        'idPublicacion',
        'idRegistro',
        'numeroCaso',
        'fechaReclamo',
        'fechaMaxRespuesta',
        'idPlataforma',
        'idCuentaPlataforma',
        'ordenCompra',
        'estadoGeneral',
        'resultadoReclamo',
        'idTipoReclamoPlataforma',
        'detalleReclamo'
    ];

    protected $casts = [
        'fechaReclamo' => 'date',
        'fechaMaxRespuesta' => 'date',
        'idUser' => 'int',
        'idCliente' => 'int',
        'idPublicacion' => 'int',
        'idRegistro' => 'int',
        'idPlataforma' => 'int',
        'idCuentaPlataforma' => 'int',
        'idTipoReclamoPlataforma' => 'int'
    ];

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'idUser', 'idUser');
    }

    public function Cliente()
    {
        return $this->belongsTo(Cliente::class, 'idCliente', 'idCliente');
    }

    public function Plataforma()
    {
        return $this->belongsTo(Plataforma::class, 'idPlataforma', 'idPlataforma');
    }

    public function CuentaPlataforma()
    {
        return $this->belongsTo(CuentasPlataforma::class, 'idCuentaPlataforma', 'idCuentaPlataforma');
    }

    public function TipoReclamo()
    {
        return $this->belongsTo(TipoReclamoPlataforma::class, 'idTipoReclamoPlataforma', 'idTipoReclamoPlataforma');
    }

    public function Publicacion()
    {
        return $this->belongsTo(Publicacion::class, 'idPublicacion', 'idPublicacion');
    }

    public function ProductoFisico()
    {
        return $this->belongsTo(RegistroProducto::class, 'idRegistro', 'idRegistro');
    }

    public function Seguimientos()
    {
        return $this->hasMany(SeguimientoReclamo::class, 'idReclamoPlataforma', 'idReclamoPlataforma');
    }

    public function Diagnosticos()
    {
        return $this->hasMany(DiagnosticoReclamo::class, 'idReclamoPlataforma', 'idReclamoPlataforma');
    }
}
