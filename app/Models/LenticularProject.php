<?php

namespace App\Models;

use App\Enums\LenticularProjectStatus;
use Database\Factories\LenticularProjectFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LenticularProject extends Model
{
    /** @use HasFactory<LenticularProjectFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => LenticularProjectStatus::class, 'settings' => 'array'];
    }

    public function files(): HasMany
    {
        return $this->hasMany(LenticularProjectFile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(LenticularJob::class);
    }

    public function falAiJobs(): HasMany
    {
        return $this->hasMany(FalAiJob::class);
    }
}
