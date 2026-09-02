<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalAnalyticsBotRequest extends Model
{
    public $timestamps = false;

    protected $table =
        'portal_analytics_bot_requests';

    protected $fillable = [
        'bot_name',
        'category',
        'route_name',
        'path',
        'method',
        'status_code',
        'locale',
        'user_agent_hash',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }
}
