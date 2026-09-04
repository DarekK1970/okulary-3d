<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class TokenLensTransaction extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'metadata' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('TOKEN_LENS ledger entries are immutable.'));
        static::deleting(fn () => throw new LogicException('TOKEN_LENS ledger entries are immutable.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grant(): BelongsTo
    {
        return $this->belongsTo(TokenLensGrant::class, 'token_lens_grant_id');
    }
}
