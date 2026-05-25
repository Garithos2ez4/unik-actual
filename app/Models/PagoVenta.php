<?php

namespace App\Models;

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

    public function Venta()
    {
        return $this->belongsTo(Venta::class, 'idVenta', 'idVenta');
    }

    public function MetodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'idMetodoPago', 'idMetodoPago');
    }
}
