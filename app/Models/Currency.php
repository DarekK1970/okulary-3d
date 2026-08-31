<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name_pl',
        'name_en',
        'symbol',
        'decimal_places',
        'is_enabled',
        'is_base',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_enabled' => 'boolean',
            'is_base' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(CurrencyRate::class);
    }

    public function localizedName(
        ?string $locale = null
    ): string {
        $locale ??= app()->getLocale();

        return $locale === 'en'
            ? $this->name_en
            : $this->name_pl;
    }
}
