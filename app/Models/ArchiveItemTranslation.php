<?php

namespace App\Models;

use App\Enums\ArchiveTranslationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveItemTranslation extends Model
{
    protected $fillable = [
        'archive_item_id',
        'locale',
        'title',
        'slug',
        'description',
        'historical_note',
        'seo_title',
        'seo_description',
        'translation_status',
    ];

    protected function casts(): array
    {
        return [
            'translation_status' =>
                ArchiveTranslationStatus::class,
        ];
    }

    public function archiveItem(): BelongsTo
    {
        return $this->belongsTo(
            ArchiveItem::class
        );
    }

    public function isPubliclyReady(): bool
    {
        return in_array(
            $this->translation_status->value,
            ArchiveTranslationStatus::publicValues(),
            true
        );
    }
}
