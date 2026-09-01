<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingMethod extends Model
{
    protected $fillable = [
        'code',
        'name_pl',
        'name_en',
        'requires_pickup_point',
        'is_enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_pickup_point' => 'boolean',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(
            ShippingRate::class
        );
    }

    public function name(
        string $locale = 'pl'
    ): string {
        return $locale === 'en'
            ? $this->name_en
            : $this->name_pl;
    }
}
