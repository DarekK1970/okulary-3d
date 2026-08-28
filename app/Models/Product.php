<?php

namespace App\Models;

use App\Enums\CatalogTranslationStatus;
use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'source_locale',
        'status',
        'brand',
        'is_featured',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(MediaAsset::class, 'product_media')
            ->withPivot(['is_primary', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function translation(string $locale): ?ProductTranslation
    {
        return $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();
    }

    public function sourceTranslation(): ?ProductTranslation
    {
        return $this->translation($this->source_locale);
    }

    public function publicTranslation(string $locale): ?ProductTranslation
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

    public function primaryMedia(): ?MediaAsset
    {
        if ($this->relationLoaded('media')) {
            return $this->media->first(
                fn ($media) => (bool) $media->pivot->is_primary
            ) ?? $this->media->first();
        }

        return $this->media()->wherePivot('is_primary', true)->first()
            ?? $this->media()->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active->value);
    }
}
