<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesDocument extends Model
{
    use HasFactory;

    public const TYPE_ORDER_CONFIRMATION = 'order_confirmation';

    protected $fillable = [
        'order_id',
        'type',
        'number',
        'currency',
        'subtotal_gross',
        'shipping_gross',
        'total_gross',
        'buyer_name',
        'buyer_email',
        'billing_company',
        'billing_tax_id',
        'billing_address',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_gross' => 'decimal:2',
            'shipping_gross' => 'decimal:2',
            'total_gross' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
