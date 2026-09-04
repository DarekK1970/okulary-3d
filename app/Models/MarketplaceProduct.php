<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MarketplaceProduct extends Model
{
    public const PRINT_SIZES = ['15x10', 'A5', 'A4', 'A3'];

    protected $fillable = ['marketplace_category_id', 'name', 'slug', 'short_description', 'description', 'image_path', 'print_size', 'token_cost', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['token_cost' => 'integer', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'marketplace_category_id');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
