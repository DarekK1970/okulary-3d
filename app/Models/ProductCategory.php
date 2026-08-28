<?php

namespace App\Models;

use App\Enums\CatalogTranslationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = ['source_locale', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductCategoryTranslation::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function translation(string $locale): ?ProductCategoryTranslation
    {
        return $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();
    }

    public function sourceTranslation(): ?ProductCategoryTranslation
    {
        return $this->translation($this->source_locale);
    }

    public function publicTranslation(string $locale): ?ProductCategoryTranslation
    {
        if ($this->relationLoaded('translations')) {
            return $this->translations
                ->where('locale', $locale)
                ->first(fn ($item) => $item->isPubliclyReady());
        }

        return $this->translations()
            ->where('locale', $locale)
            ->whereIn('translation_status', CatalogTranslationStatus::publicValues())
            ->first();
    }
}
