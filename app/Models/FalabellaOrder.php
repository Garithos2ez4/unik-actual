<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FalabellaOrder extends Model
{
    protected $table = 'falabella_orders';

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
        'created_at_falabella',
        'updated_at_falabella',
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
        'created_at_falabella' => 'datetime',
        'updated_at_falabella' => 'datetime',
        'promised_shipping_time' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(FalabellaOrderItem::class, 'order_id', 'order_id');
    }
}
