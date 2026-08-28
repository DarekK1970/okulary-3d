<?php

namespace App\Models;

use App\Enums\DiscoveryRunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscoveryRun extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'model',
        'status',
        'topic',
        'query',
        'freshness_days',
        'requested_candidates',
        'saved_candidates',
        'skipped_candidates',
        'duplicate_candidates',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'started_at',
        'completed_at',
        'error_message',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'status' => DiscoveryRunStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(DiscoveryCandidate::class);
    }
}
