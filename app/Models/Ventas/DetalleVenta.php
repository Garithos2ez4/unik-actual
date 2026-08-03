<?php

namespace App\Models\Ventas;
use App\Models\Inventario\EgresoProducto;
use App\Models\Catalogo\Producto;
use App\Models\Catalogo\Publicacion;

use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    public $timestamps = false;

    protected $table = 'DetalleVenta';

    protected $primaryKey = 'idDetalleVenta';

    protected $guarded = ['idDetalleVenta'];

    protected $fillable = [
        'idVenta',
        'idEgreso',       // FK → EgresoProducto (el ítem de inventario que salió)
        'idProducto',
        'idPublicacion',  // Opcional: si vino de una plataforma
        'precioVenta',    // El precio real cobrado
        'cantidad',
        'origenPrecio',   // 'TIENDA' | 'PUBLICACION' | 'NEGOCIADO'
        'estado',         // 'COMPLETADO' | 'DEVUELTO'
    ];

    protected static function booted()
    {
        static::addGlobalScope('completado', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('DetalleVenta.estado', '=', 'COMPLETADO');
        });

        $clearCache = function () {
            \Illuminate\Support\Facades\Cache::increment('analytics_tienda_version');
        };
        static::saved($clearCache);
        static::deleted($clearCache);
    }

    protected $casts = [
        'idDetalleVenta'  => 'int',
        'idVenta'         => 'int',
        'idEgreso'        => 'int',
        'idProducto'      => 'int',
        'idPublicacion'   => 'int',
        'precioVenta'     => 'float',
        'cantidad'        => 'int',
    ];

    // ─── Relaciones ────────────────────────────────────────────

    public function Venta()
    {
        return $this->belongsTo(Venta::class, 'idVenta', 'idVenta');
    }

    public function EgresoProducto()
    {
        return $this->belongsTo(EgresoProducto::class, 'idEgreso', 'idEgreso');
    }

    public function Producto()
    {
        return $this->belongsTo(Producto::class, 'idProducto', 'idProducto');
    }

    public function Publicacion()
    {
        return $this->belongsTo(Publicacion::class, 'idPublicacion', 'idPublicacion');
    }
}
