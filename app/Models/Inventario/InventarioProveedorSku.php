<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Empresa\Preveedor;

class InventarioProveedorSku extends Model
{
    use HasFactory;

    protected $table = 'inventario_proveedor_skus';

    protected $fillable = [
        'idInventarioProveedor',
        'idProveedor',
        'sku_proveedor',
        'mini_codigo',
        'part_number',
        'precio_b2b',
        'precio_promo',
        'hay_mas',
        'datos_extra'
    ];

    protected $casts = [
        'idInventarioProveedor' => 'int',
        'idProveedor' => 'int',
        'precio_b2b' => 'float',
        'precio_promo' => 'float',
        'hay_mas' => 'boolean',
        'datos_extra' => 'array',
    ];

    public function inventarioProveedor()
    {
        return $this->belongsTo(Inventario_Proveedor::class, 'idInventarioProveedor', 'idInventarioProveedor');
    }

    public function proveedor()
    {
        return $this->belongsTo(Preveedor::class, 'idProveedor', 'idProveedor');
    }
}
