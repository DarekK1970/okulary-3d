<?php

namespace App\Models;

use App\Enums\CatalogTranslationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceProductTranslation extends Model
{
    protected $fillable = ['marketplace_product_id', 'locale', 'name', 'slug', 'short_description', 'description', 'translation_status'];

    protected function casts(): array
    {
        return ['translation_status' => CatalogTranslationStatus::class];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class);
    }

    public function isPubliclyReady(): bool
    {
        return in_array($this->translation_status->value, CatalogTranslationStatus::publicValues(), true);
    }
}
