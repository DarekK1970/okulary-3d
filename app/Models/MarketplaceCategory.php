<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'source_locale', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(MarketplaceProduct::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(MarketplaceCategoryTranslation::class);
    }

    public function translation(string $locale): ?MarketplaceCategoryTranslation
    {
        return $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();
    }

    public function sourceTranslation(): ?MarketplaceCategoryTranslation
    {
        return $this->translation($this->source_locale ?? 'pl');
    }

    public function publicTranslation(string $locale): ?MarketplaceCategoryTranslation
    {
        $translation = $this->translation($locale);

        return $translation?->isPubliclyReady() ? $translation : null;
    }
}
