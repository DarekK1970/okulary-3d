<?php

namespace App\Models;

use Database\Factories\LenticularArtifactFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LenticularArtifact extends Model
{
    /** @use HasFactory<LenticularArtifactFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];
}
