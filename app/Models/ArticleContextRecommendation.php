<?php

namespace App\Models;

use App\Enums\ContextRecommendationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleContextRecommendation extends Model
{
    protected $fillable = [
        'article_id',
        'target_type',
        'tool_key',
        'product_id',
        'position',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'target_type' =>
                ContextRecommendationType::class,
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}
