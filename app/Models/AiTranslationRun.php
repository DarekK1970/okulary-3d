<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTranslationRun extends Model
{
    protected $fillable = [
        'content_type',
        'content_id',
        'source_locale',
        'target_locale',
        'provider',
        'model',
        'status',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'request_chars',
        'response_chars',
        'error_message',
        'initiated_by',
    ];

    protected function casts(): array
    {
        return [
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'total_tokens' => 'integer',
            'request_chars' => 'integer',
            'response_chars' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'initiated_by'
        );
    }
}
