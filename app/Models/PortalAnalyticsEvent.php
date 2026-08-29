<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalAnalyticsEvent extends Model
{
    public $timestamps = false;

    protected $table =
        'portal_analytics_events';

    protected $fillable = [
        'analytics_session_id',
        'event_name',
        'category',
        'label',
        'value',
        'route_name',
        'path',
        'locale',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function analyticsSession(): BelongsTo
    {
        return $this->belongsTo(
            PortalAnalyticsSession::class,
            'analytics_session_id'
        );
    }
}
