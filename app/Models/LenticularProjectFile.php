<?php

namespace App\Models;

use Database\Factories\LenticularProjectFileFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LenticularProjectFile extends Model
{
    /** @use HasFactory<LenticularProjectFileFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function lenticularProject(): BelongsTo
    {
        return $this->belongsTo(LenticularProject::class);
    }

    public function falAiSourceJobs(): HasMany
    {
        return $this->hasMany(FalAiJob::class, 'source_file_id');
    }

    public function falAiEndJobs(): HasMany
    {
        return $this->hasMany(FalAiJob::class, 'end_file_id');
    }

    public function falAiResultJobs(): HasMany
    {
        return $this->hasMany(FalAiJob::class, 'result_file_id');
    }
}
