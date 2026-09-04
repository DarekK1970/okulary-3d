<?php

namespace App\Models;

use App\Enums\CatalogTranslationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceCategoryTranslation extends Model
{
    protected $fillable = [
        'marketplace_category_id',
        'locale',
        'name',
        'slug',
        'description',
        'translation_status',
    ];

    protected function casts(): array
    {
        return ['translation_status' => CatalogTranslationStatus::class];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'marketplace_category_id');
    }

    public function isPubliclyReady(): bool
    {
        return in_array($this->translation_status->value, CatalogTranslationStatus::publicValues(), true);
    }
}
