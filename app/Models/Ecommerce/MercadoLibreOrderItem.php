<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MercadoLibreOrderItem extends Model
{
    protected $table = 'ml_order_items';

    protected $fillable = [
        'ml_item_id',
        'ml_order_id',
        'seller_sku',
        'ml_sku',
        'title',
        'status',
        'unit_price',
        'quantity',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(MercadoLibreOrder::class, 'ml_order_id', 'ml_order_id');
    }
}
