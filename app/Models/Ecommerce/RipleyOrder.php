<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RipleyOrder extends Model
{
    protected $table = 'ripley_orders';

    protected $fillable = [
        'order_id',
        'order_number',
        'customer_name',
        'customer_email',
        'status',
        'statuses',
        'price',
        'payment_method',
        'shipping_type',
        'delivery_info',
        'items_count',
        'created_at_ripley',
        'updated_at_ripley',
        'promised_shipping_time',
        'shipping_city',
        'shipping_address',
        'payload',
        'synced_at',
        'sync_date',
    ];

    protected $casts = [
        'statuses' => 'array',
        'payload' => 'array',
        'created_at_ripley' => 'datetime',
        'updated_at_ripley' => 'datetime',
        'promised_shipping_time' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(RipleyOrderItem::class, 'order_id', 'order_id');
    }
}
