<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscoverySource extends Model
{
    protected $fillable = [
        'discovery_candidate_id',
        'url',
        'url_hash',
        'title',
        'domain',
        'language',
        'published_at',
        'excerpt',
        'source_type',
        'credibility_score',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(
            DiscoveryCandidate::class,
            'discovery_candidate_id'
        );
    }
}
