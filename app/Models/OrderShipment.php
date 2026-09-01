<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShipment extends Model
{
    protected $fillable = [
        'order_id',
        'provider',
        'external_package_id',
        'service_id',
        'carrier',
        'state',
        'order_command_uuid',
        'tracking_number',
        'tracking_url',
        'last_tracking_state',
        'last_tracking_status',
        'last_tracking_at',
        'label_format',
        'label_page_format',
        'request_snapshot',
        'response_snapshot',
        'ordered_at',
    ];

    protected function casts(): array
    {
        return [
            'service_id' => 'integer',
            'request_snapshot' => 'array',
            'response_snapshot' => 'array',
            'last_tracking_at' => 'datetime',
            'ordered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
