<?php

namespace App\Models;

use App\Enums\DiscoveryDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscoveryCandidate extends Model
{
    protected $fillable = [
        'discovery_run_id',
        'fingerprint',
        'cluster_key',
        'title',
        'angle',
        'summary',
        'suggested_section',
        'relevance_score',
        'novelty_score',
        'confidence_score',
        'facts',
        'keywords',
        'decision',
        'decision_by',
        'decided_at',
        'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'facts' => 'array',
            'keywords' => 'array',
            'decision' => DiscoveryDecision::class,
            'decided_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(
            DiscoveryRun::class,
            'discovery_run_id'
        );
    }

    public function sources(): HasMany
    {
        return $this->hasMany(DiscoverySource::class);
    }

    public function decisionUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'decision_by'
        );
    }

    public function orchestratorPlanItems(): HasMany
    {
        return $this->hasMany(
            OrchestratorPlanItem::class
        );
    }
}
