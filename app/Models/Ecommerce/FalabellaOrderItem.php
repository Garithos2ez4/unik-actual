<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FalabellaOrderItem extends Model
{
    protected $table = 'falabella_order_items';

    protected $fillable = [
        'order_item_id',
        'order_id',
        'order_number',
        'seller_sku',
        'falabella_sku',
        'shop_sku',
        'name',
        'status',
        'price',
        'quantity',
        'tracking_code',
        'package_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(FalabellaOrder::class, 'order_id', 'order_id');
    }
}
