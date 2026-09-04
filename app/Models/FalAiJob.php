<?php

namespace App\Models;

use App\Enums\FalAiJobOperation;
use App\Enums\FalAiJobStatus;
use Database\Factories\FalAiJobFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FalAiJob extends Model
{
    /** @use HasFactory<FalAiJobFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'operation' => FalAiJobOperation::class,
            'status' => FalAiJobStatus::class,
            'parameters' => 'array',
            'provider_response' => 'array',
            'estimated_cost_usd' => 'decimal:6',
            'actual_cost_usd' => 'decimal:6',
            'submitted_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'result_claimed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lenticularProject(): BelongsTo
    {
        return $this->belongsTo(LenticularProject::class);
    }

    public function sourceFile(): BelongsTo
    {
        return $this->belongsTo(LenticularProjectFile::class, 'source_file_id');
    }

    public function endFile(): BelongsTo
    {
        return $this->belongsTo(LenticularProjectFile::class, 'end_file_id');
    }

    public function resultFile(): BelongsTo
    {
        return $this->belongsTo(LenticularProjectFile::class, 'result_file_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(FalAiJobEvent::class);
    }
}
