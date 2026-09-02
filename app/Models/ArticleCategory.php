<?php

namespace App\Models;

use App\Enums\ArticlePortalSection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'portal_section',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'portal_section' => ArticlePortalSection::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'category_id');
    }

    public function publicIndexUrl(string $locale): string
    {
        $section = $this->portal_section ?? ArticlePortalSection::Articles;

        if ($section === ArticlePortalSection::Articles) {
            return route('articles.index', [
                'locale' => $locale,
                'category' => $this->slug,
            ]);
        }

        return route('articles.index', [
            'locale' => $locale,
            'section' => $section->value,
        ]);
    }
}
