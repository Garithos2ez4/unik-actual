<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeltronPromocion extends Model
{
    use HasFactory;

    protected $table = 'deltron_promociones';

    protected $fillable = [
        'promocion_id',
        'promocion_descripcion',
        'sku',
        'sku_descripcion',
        'precio_promocion',
        'fecha_fin',
        'principal_json',
        'items_combo_json',
        'stock_promocion_json',
        'stock_total_promo',
        'detalle_cargado',
        'detalle_actualizado_at'
    ];

    protected $casts = [
        'precio_promocion' => 'decimal:2',
        'fecha_fin' => 'datetime',
        'principal_json' => 'array',
        'items_combo_json' => 'array',
        'stock_promocion_json' => 'array',
        'stock_total_promo' => 'integer',
        'detalle_cargado' => 'boolean',
        'detalle_actualizado_at' => 'datetime'
    ];
}
