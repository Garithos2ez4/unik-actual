<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;

class MercadoLibreCredential extends Model
{
    protected $table = 'ml_credentials';

    protected $fillable = [
        'seller_id',
        'seller_nickname',
        'access_token',
        'refresh_token',
        'token_expires_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        if (!$this->token_expires_at) return true;
        return now()->gte($this->token_expires_at->subMinutes(5));
    }
}
