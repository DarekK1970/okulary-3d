<?php

namespace App\Models;

use App\Enums\CatalogTranslationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'locale',
        'name',
        'slug',
        'short_description',
        'description_html',
        'seo_title',
        'seo_description',
        'translation_status',
    ];

    protected function casts(): array
    {
        return ['translation_status' => CatalogTranslationStatus::class];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
