<?php

namespace App\Models;

use App\Enums\ArticleTranslationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'locale',
        'title',
        'slug',
        'excerpt',
        'body_html',
        'seo_title',
        'seo_description',
        'translation_status',
    ];

    protected function casts(): array
    {
        return [
            'translation_status' => ArticleTranslationStatus::class,
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function isPubliclyReady(): bool
    {
        return in_array(
            $this->translation_status->value,
            ArticleTranslationStatus::publicValues(),
            true
        );
    }
}
