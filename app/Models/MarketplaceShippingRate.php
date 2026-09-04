<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceShippingRate extends Model
{
    protected $fillable = ['country_code', 'print_size', 'token_cost'];

    protected function casts(): array
    {
        return ['token_cost' => 'integer'];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(MarketplaceShippingProvider::class, 'marketplace_shipping_provider_id');
    }
}
