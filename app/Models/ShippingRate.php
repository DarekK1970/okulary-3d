<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    protected $fillable = [
        'shipping_country_id',
        'shipping_method_id',
        'weight_from_grams',
        'weight_to_grams',
        'price_pln',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'weight_from_grams' => 'integer',
            'weight_to_grams' => 'integer',
            'price_pln' => 'decimal:2',
            'is_enabled' => 'boolean',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(
            ShippingCountry::class,
            'shipping_country_id'
        );
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(
            ShippingMethod::class,
            'shipping_method_id'
        );
    }

    public function weightFromKg(): string
    {
        return number_format(
            $this->weight_from_grams / 1000,
            3,
            '.',
            ''
        );
    }

    public function weightToKg(): string
    {
        return number_format(
            $this->weight_to_grams / 1000,
            3,
            '.',
            ''
        );
    }
}
