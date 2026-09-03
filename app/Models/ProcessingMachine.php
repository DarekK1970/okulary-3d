<?php

namespace App\Models;

use Database\Factories\ProcessingMachineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessingMachine extends Model
{
    /** @use HasFactory<ProcessingMachineFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['api_secret'];

    protected function casts(): array
    {
        return ['api_secret' => 'encrypted', 'capabilities' => 'array', 'is_active' => 'boolean', 'last_seen_at' => 'datetime'];
    }
}
