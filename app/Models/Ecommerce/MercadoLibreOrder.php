<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MercadoLibreOrder extends Model
{
    protected $table = 'ml_orders';

    protected $fillable = [
        'ml_order_id',
        'seller_id',
        'buyer_name',
        'buyer_nickname',
        'status',
        'total_amount',
        'currency',
        'shipping_id',
        'logistic_type',
        'logistic_label',
        'shipping_status',
        'shipping_substatus',
        'driver_id',
        'shipping_mode',
        'carrier_name',
        'tracking_number',
        'seller_shipping_cost',
        'receiver_shipping_cost',
        'promoted_shipping_amount',
        'date_handled',
        'date_shipped',
        'date_delivered',
        'return_status',
        'return_reason',
        'items_count',
        'payload',
        'created_at_ml',
        'synced_at',
        'sync_date',
    ];

    protected $casts = [
        'payload'        => 'array',
        'created_at_ml'  => 'datetime',
        'date_handled'   => 'datetime',
        'date_shipped'   => 'datetime',
        'date_delivered' => 'datetime',
        'synced_at'      => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MercadoLibreOrderItem::class, 'ml_order_id', 'ml_order_id');
    }

}
