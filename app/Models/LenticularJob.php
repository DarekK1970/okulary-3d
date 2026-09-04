<?php

namespace App\Models;

use App\Enums\LenticularJobStatus;
use Database\Factories\LenticularJobFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LenticularJob extends Model
{
    /** @use HasFactory<LenticularJobFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => LenticularJobStatus::class,
            'parameters' => 'array',
            'lease_expires_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function sourceFile(): BelongsTo
    {
        return $this->belongsTo(LenticularProjectFile::class, 'source_file_id');
    }

    public function lenticularProject(): BelongsTo
    {
        return $this->belongsTo(LenticularProject::class);
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(LenticularArtifact::class);
    }
}
