<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrchestratorRun extends Model
{
    protected $fillable = [
        'user_id',
        'orchestrator_plan_id',
        'orchestrator_plan_item_id',
        'action',
        'provider',
        'model',
        'status',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'request_chars',
        'response_chars',
        'started_at',
        'completed_at',
        'error_message',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            OrchestratorPlan::class,
            'orchestrator_plan_id'
        );
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(
            OrchestratorPlanItem::class,
            'orchestrator_plan_item_id'
        );
    }
}
