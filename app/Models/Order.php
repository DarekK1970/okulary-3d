<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'public_token',
        'user_id',
        'locale',
        'status',
        'currency',
        'subtotal_gross',
        'shipping_gross',
        'shipping_method',
        'shipping_name_snapshot',
        'payment_method',
        'payment_status',
        'payment_merchant_external_id',
        'payment_idempotency_key',
        'payment_external_id',
        'payment_redirect_url',
        'payment_error',
        'total_gross',
        'customer_email',
        'customer_first_name',
        'customer_last_name',
        'customer_phone',
        'billing_company',
        'billing_tax_id',
        'billing_address_line1',
        'billing_address_line2',
        'billing_postal_code',
        'billing_city',
        'billing_country_code',
        'shipping_same_as_billing',
        'shipping_first_name',
        'shipping_last_name',
        'shipping_company',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_postal_code',
        'shipping_city',
        'shipping_country_code',
        'shipping_point',
        'customer_note',
        'placed_at',
        'paid_at',
        'payment_failed_at',
        'cancelled_at',
        'stock_released_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal_gross' => 'decimal:2',
            'shipping_gross' => 'decimal:2',
            'total_gross' => 'decimal:2',
            'shipping_same_as_billing' => 'boolean',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'payment_failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'stock_released_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function salesDocuments(): HasMany
    {
        return $this->hasMany(SalesDocument::class);
    }

    public function customerName(): string
    {
        return trim(
            $this->customer_first_name . ' ' . $this->customer_last_name
        );
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }
}
