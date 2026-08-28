<?php

namespace App\Models;

use App\Enums\ArchiveTranslationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ArchiveItem extends Model
{
    protected $fillable = [
        'source_locale',
        'technique',
        'year_from',
        'year_to',
        'circa',
        'creator',
        'publisher',
        'country',
        'collection_name',
        'source_name',
        'source_url',
        'rights_status',
        'rights_note',
        'original_image_path',
        'left_image_path',
        'right_image_path',
        'original_width',
        'original_height',
        'is_published',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'circa' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(
            ArchiveItemTranslation::class
        );
    }

    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updaterUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function translation(
        string $locale
    ): ?ArchiveItemTranslation {
        return $this->relationLoaded('translations')
            ? $this->translations
                ->firstWhere('locale', $locale)
            : $this->translations()
                ->where('locale', $locale)
                ->first();
    }

    public function publicTranslation(
        string $locale
    ): ?ArchiveItemTranslation {
        if ($this->relationLoaded('translations')) {
            return $this->translations
                ->where('locale', $locale)
                ->first(
                    fn ($translation) =>
                        $translation->isPubliclyReady()
                );
        }

        return $this->translations()
            ->where('locale', $locale)
            ->whereIn(
                'translation_status',
                ArchiveTranslationStatus::publicValues()
            )
            ->first();
    }

    public function scopePublished(
        Builder $query
    ): Builder {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at');
    }

    public function originalImageUrl(): string
    {
        return Storage::disk('public')
            ->url($this->original_image_path);
    }

    public function leftImageUrl(): ?string
    {
        return $this->left_image_path
            ? Storage::disk('public')
                ->url($this->left_image_path)
            : null;
    }

    public function rightImageUrl(): ?string
    {
        return $this->right_image_path
            ? Storage::disk('public')
                ->url($this->right_image_path)
            : null;
    }

    public function hasStereoPair(): bool
    {
        return filled($this->left_image_path)
            && filled($this->right_image_path);
    }

    public function yearLabel(): string
    {
        $prefix = $this->circa ? 'ca. ' : '';

        if (
            $this->year_to
            && $this->year_to !== $this->year_from
        ) {
            return $prefix
                . $this->year_from
                . '–'
                . $this->year_to;
        }

        return $prefix . (string) $this->year_from;
    }
}
