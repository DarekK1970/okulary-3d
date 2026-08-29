<?php

namespace App\Models;

use App\Enums\OrchestratorItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrchestratorPlanItem extends Model
{
    protected $fillable = [
        'orchestrator_plan_id',
        'discovery_candidate_id',
        'article_id',
        'position',
        'planned_for',
        'planned_title',
        'editorial_angle',
        'rationale',
        'suggested_section',
        'status',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'planned_for' => 'datetime',
            'status' => OrchestratorItemStatus::class,
            'generated_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            OrchestratorPlan::class,
            'orchestrator_plan_id'
        );
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(
            DiscoveryCandidate::class,
            'discovery_candidate_id'
        );
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(
            Article::class
        );
    }
}
