<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalAnalyticsPageView extends Model
{
    public $timestamps = false;

    protected $table =
        'portal_analytics_page_views';

    protected $fillable = [
        'analytics_session_id',
        'route_name',
        'path',
        'locale',
        'page_type',
        'referrer_domain',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
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
