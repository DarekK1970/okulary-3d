<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Enums\ArticleTranslationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use HasFactory;

    /**
     * Legacy localized columns (title/slug/excerpt/body_html) are intentionally
     * still populated for migration compatibility after KROK 66.
     * Public rendering uses article_translations.
     */
    protected $fillable = [
        'category_id',
        'source_locale',
        'title',
        'slug',
        'excerpt',
        'body_html',
        'hero_image_path',
        'hero_media_id',
        'status',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function heroMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'hero_media_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ArticleTranslation::class);
    }

    public function translation(string $locale): ?ArticleTranslation
    {
        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale);
        }

        return $this->translations()
            ->where('locale', $locale)
            ->first();
    }

    public function sourceTranslation(): ?ArticleTranslation
    {
        return $this->translation($this->source_locale);
    }

    public function publicTranslation(string $locale): ?ArticleTranslation
    {
        if ($this->relationLoaded('translations')) {
            return $this->translations
                ->where('locale', $locale)
                ->first(
                    fn (ArticleTranslation $translation) => $translation->isPubliclyReady()
                );
        }

        return $this->translations()
            ->where('locale', $locale)
            ->whereIn(
                'translation_status',
                ArticleTranslationStatus::publicValues()
            )
            ->first();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', ArticleStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
