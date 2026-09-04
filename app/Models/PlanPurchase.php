<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanPurchase extends Model
{
    protected $fillable = ['public_token', 'user_id', 'plan', 'duration_months', 'token_lens', 'price', 'currency', 'auto_renew', 'status', 'payment_merchant_external_id', 'payment_idempotency_key', 'payment_external_id', 'payment_redirect_url', 'payment_error', 'paid_at'];

    protected function casts(): array
    {
        return ['auto_renew' => 'boolean', 'paid_at' => 'datetime', 'price' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_token';
    }
}
