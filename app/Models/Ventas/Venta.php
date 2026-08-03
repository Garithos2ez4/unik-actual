<?php

namespace App\Models\Ventas;
use App\Models\Usuarios\Cliente;
use App\Models\Usuarios\Usuario;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    public $timestamps = false;

    protected $table = 'Venta';

    protected $primaryKey = 'idVenta';

    protected $guarded = ['idVenta'];

    protected $fillable = [
        'idCliente',
        'idUser',
        'canal',          // 'TIENDA' | 'PLATAFORMA'
        'numeroOrden',
        'fechaVenta',
        'totalVenta',
        'observacion',
    ];

    protected $casts = [
        'idVenta'     => 'int',
        'idCliente'   => 'int',
        'idUser'      => 'int',
        'totalVenta'  => 'float',
        'fechaVenta'  => 'datetime',
    ];

    protected static function booted()
    {
        $clearCache = function () {
            \Illuminate\Support\Facades\Cache::increment('analytics_tienda_version');
        };
        static::saved($clearCache);
        static::deleted($clearCache);
    }

    // ─── Relaciones ────────────────────────────────────────────

    public function Cliente()
    {
        return $this->belongsTo(Cliente::class, 'idCliente', 'idCliente');
    }

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'idUser', 'idUser');
    }

    public function DetallesVenta()
    {
        return $this->hasMany(DetalleVenta::class, 'idVenta', 'idVenta')->withoutGlobalScope('completado');
    }
}
