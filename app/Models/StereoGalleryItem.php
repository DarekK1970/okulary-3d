<?php

namespace App\Models;

use App\Enums\GalleryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StereoGalleryItem extends Model
{
    protected $fillable = [
        'user_id',
        'slug',
        'title',
        'description',
        'author_name',
        'license',
        'status',
        'left_image_path',
        'right_image_path',
        'stereo_pair_path',
        'left_width',
        'left_height',
        'right_width',
        'right_height',
        'rights_confirmed_at',
        'published_at',
        'moderated_by',
        'moderated_at',
        'moderation_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => GalleryStatus::class,
            'rights_confirmed_at' => 'datetime',
            'published_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'moderated_by'
        );
    }

    public function scopePublished(
        Builder $query
    ): Builder {
        return $query
            ->where('status', GalleryStatus::Published)
            ->whereNotNull('published_at');
    }

    public function leftImageUrl(): string
    {
        return Storage::disk('public')
            ->url($this->left_image_path);
    }

    public function rightImageUrl(): string
    {
        return Storage::disk('public')
            ->url($this->right_image_path);
    }

    public function stereoPairUrl(): string
    {
        return Storage::disk('public')
            ->url($this->stereo_pair_path ?? $this->left_image_path);
    }

    public function canBeDeletedBy(
        User $user
    ): bool {
        return $this->user_id === $user->id
            && $this->status !== GalleryStatus::Published;
    }
}
