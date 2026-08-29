<?php

namespace App\Models;

use App\Enums\OrchestratorPlanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrchestratorPlan extends Model
{
    protected $fillable = [
        'week_start',
        'week_end',
        'status',
        'provider',
        'model',
        'editorial_summary',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',
            'status' => OrchestratorPlanStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            OrchestratorPlanItem::class
        )->orderBy('position');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function runs(): HasMany
    {
        return $this->hasMany(
            OrchestratorRun::class
        );
    }
}
