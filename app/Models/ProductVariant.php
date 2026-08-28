<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'price_gross',
        'vat_rate',
        'currency',
        'stock_quantity',
        'track_stock',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_gross' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'stock_quantity' => 'integer',
            'track_stock' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inStock(): bool
    {
        return ! $this->track_stock || $this->stock_quantity > 0;
    }
}
