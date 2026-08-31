<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrencyRate extends Model
{
    protected $fillable = [
        'currency_id',
        'rate_to_base',
        'effective_date',
        'source',
        'is_manual',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'rate_to_base' => 'decimal:8',
            'effective_date' => 'date',
            'is_manual' => 'boolean',
            'fetched_at' => 'datetime',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
