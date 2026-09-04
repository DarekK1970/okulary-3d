<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TokenLensGrant extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'effective_at' => 'datetime', 'expires_at' => 'datetime', 'metadata' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TokenLensTransaction::class);
    }
}
