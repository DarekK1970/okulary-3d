<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingCountry extends Model
{
    protected $fillable = [
        'code',
        'name_pl',
        'name_en',
        'is_enabled',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_default' => 'boolean',
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
