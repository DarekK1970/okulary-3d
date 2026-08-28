<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'disk',
        'path',
        'original_name',
        'stored_name',
        'mime_type',
        'extension',
        'size_bytes',
        'width',
        'height',
        'title',
        'alt_text',
        'caption',
        'folder',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function heroArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'hero_media_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_media')
            ->withTimestamps();
    }

    public function url(): string
    {
        return \Storage::disk($this->disk)->url($this->path);
    }

    public function humanSize(): string
    {
        if (! $this->size_bytes) {
            return '—';
        }

        if ($this->size_bytes >= 1024 * 1024) {
            return number_format($this->size_bytes / 1024 / 1024, 1, ',', ' ') . ' MB';
        }

        return number_format($this->size_bytes / 1024, 0, ',', ' ') . ' KB';
    }
}
