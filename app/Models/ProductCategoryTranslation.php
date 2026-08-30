<?php

namespace App\Models;

use App\Enums\CatalogTranslationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCategoryTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_category_id',
        'locale',
        'name',
        'slug',
        'description',
        'content_html',
        'seo_title',
        'seo_description',
        'translation_status',
    ];

    protected function casts(): array
    {
        return ['translation_status' => CatalogTranslationStatus::class];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function isPubliclyReady(): bool
    {
        return in_array(
            $this->translation_status->value,
            CatalogTranslationStatus::publicValues(),
            true
        );
    }
}
