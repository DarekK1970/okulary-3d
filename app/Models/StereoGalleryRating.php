<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StereoGalleryRating extends Model
{
    protected $fillable = [
        'stereo_gallery_item_id',
        'user_id',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function galleryItem(): BelongsTo
    {
        return $this->belongsTo(
            StereoGalleryItem::class,
            'stereo_gallery_item_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
