<?php

namespace App\Models;

use Database\Factories\LenticularArtifactFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LenticularArtifact extends Model
{
    /** @use HasFactory<LenticularArtifactFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function lenticularJob(): BelongsTo
    {
        return $this->belongsTo(LenticularJob::class);
    }
}
