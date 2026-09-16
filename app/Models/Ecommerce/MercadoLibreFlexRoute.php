<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MercadoLibreFlexRoute extends Model
{
    use HasFactory;

    protected $table = 'ml_flex_routes';

    protected $fillable = [
        'ml_order_id',
        'zona',
        'route_order'
    ];
}
