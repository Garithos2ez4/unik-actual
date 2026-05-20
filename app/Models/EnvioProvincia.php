<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvioProvincia extends Model
{
    protected $table = 'envio_provincias';
    protected $primaryKey = 'idEnvioProvincia';

    protected $fillable = [
        'idUser',
        'idCliente',
        'idPlataforma',
        'idCuentaPlataforma',
        'idAgencia',
        'idSubAgencia',
        'idDestino',
        'dato_adicional',
        'numero_guia',
        'clave',
        'pago_destino',
        'fecha_envio'
    ];

    protected $casts = [
        'fecha_envio' => 'date',
        'idUser' => 'int',
        'idCliente' => 'int',
        'idPlataforma' => 'int',
        'idCuentaPlataforma' => 'int',
        'idAgencia' => 'int',
        'idSubAgencia' => 'int',
        'idDestino' => 'int',
        'pago_destino' => 'boolean'
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

    public function Agencia()
    {
        return $this->belongsTo(Agencia::class, 'idAgencia', 'idAgencia');
    }

    public function SubAgencia()
    {
        return $this->belongsTo(SubAgencia::class, 'idSubAgencia', 'idSubAgencia');
    }

    public function Destino()
    {
        return $this->belongsTo(Destino::class, 'idDestino', 'idDestino');
    }

    public function Productos()
    {
        return $this->hasMany(EnvioProvinciaProducto::class, 'idEnvioProvincia', 'idEnvioProvincia');
    }

    public function Detalle()
    {
        return $this->hasOne(EnvioProvinciaDetalle::class, 'idEnvioProvincia', 'idEnvioProvincia');
    }
}
