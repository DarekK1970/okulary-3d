<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaticPageTranslation extends Model
{
    protected $fillable = [
        'static_page_id',
        'locale',
        'title',
        'body_html',
        'seo_title',
        'seo_description',
        'translation_status',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(
            StaticPage::class,
            'static_page_id'
        );
    }

    public function isComplete(): bool
    {
        return filled($this->title)
            && filled(
                strip_tags(
                    (string)
                    $this->body_html
                )
            );
    }
}
