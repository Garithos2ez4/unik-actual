<?php

namespace App\Models\Ventas;
use App\Models\Empresa\CuentasTransferencia;

use Illuminate\Database\Eloquent\Model;

class PagoVenta extends Model
{
    public $timestamps = false;
    
    protected $table = 'PagoVenta';
    
    protected $primaryKey = 'idPagoVenta';

    protected $guarded = ['idPagoVenta'];
    
    protected $fillable = [
        'idVenta',
        'idMetodoPago',
        'idCuentaBancaria',
        'monto',
        'nroOperacion',
        'fechaPago'
    ];

    protected $casts = [
        'idPagoVenta'  => 'int',
        'idVenta'      => 'int',
        'idMetodoPago' => 'int',
        'monto'        => 'float',
        'fechaPago'    => 'datetime'
    ];

    protected static function booted()
    {
        $clearCache = function () {
            \Illuminate\Support\Facades\Cache::increment('analytics_tienda_version');
        };
        static::saved($clearCache);
        static::deleted($clearCache);
    }

    public function Venta()
    {
        return $this->belongsTo(Venta::class, 'idVenta', 'idVenta');
    }

    public function MetodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'idMetodoPago', 'idMetodoPago');
    }

    public function CuentaBancaria()
    {
        return $this->belongsTo(CuentasTransferencia::class, 'idCuentaBancaria', 'idCuentaBancaria');
    }
}
