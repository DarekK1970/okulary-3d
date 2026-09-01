<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaticPage extends Model
{
    public const GROUP_CONTENT = 'content';
    public const GROUP_SHOP = 'shop';

    protected $fillable = [
        'key',
        'group',
        'source_locale',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(
            StaticPageTranslation::class
        );
    }

    public function translation(
        string $locale
    ): ?StaticPageTranslation {
        if (
            $this->relationLoaded(
                'translations'
            )
        ) {
            return $this
                ->translations
                ->firstWhere(
                    'locale',
                    $locale
                );
        }

        return $this
            ->translations()
            ->where(
                'locale',
                $locale
            )
            ->first();
    }

    public function sourceTranslation(): ?StaticPageTranslation
    {
        return $this->translation(
            $this->source_locale
        );
    }

    public function translationOrSource(
        string $locale
    ): ?StaticPageTranslation {
        return $this->translation(
            $locale
        ) ?? $this->sourceTranslation();
    }
}
