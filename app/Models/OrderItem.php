<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'sku_snapshot',
        'product_name_snapshot',
        'variant_name_snapshot',
        'quantity',
        'unit_price_gross',
        'base_unit_price_gross',
        'vat_rate',
        'line_total_gross',
        'base_line_total_gross',
        'currency',
        'base_currency',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_gross' => 'decimal:2',
            'base_unit_price_gross' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'line_total_gross' => 'decimal:2',
            'base_line_total_gross' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(
            ProductVariant::class,
            'product_variant_id'
        );
    }
}
