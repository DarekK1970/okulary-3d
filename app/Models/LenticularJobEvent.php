<?php

namespace App\Models;

use Database\Factories\LenticularJobEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LenticularJobEvent extends Model
{
    /** @use HasFactory<LenticularJobEventFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
