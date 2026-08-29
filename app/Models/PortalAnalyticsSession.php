<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortalAnalyticsSession extends Model
{
    public $incrementing = false;

    protected $table =
        'portal_analytics_sessions';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'browser_session_hash',
        'started_at',
        'last_seen_at',
        'landing_path',
        'landing_locale',
        'source_group',
        'source_name',
        'referrer_domain',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'device_type',
        'is_authenticated',
        'pageviews_count',
        'events_count',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'is_authenticated' => 'boolean',
            'pageviews_count' => 'integer',
            'events_count' => 'integer',
        ];
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(
            PortalAnalyticsPageView::class,
            'analytics_session_id'
        );
    }

    public function events(): HasMany
    {
        return $this->hasMany(
            PortalAnalyticsEvent::class,
            'analytics_session_id'
        );
    }
}
