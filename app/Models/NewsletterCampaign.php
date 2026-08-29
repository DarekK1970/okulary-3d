<?php

namespace App\Models;

use App\Enums\NewsletterCampaignStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsletterCampaign extends Model
{
    protected $fillable = [
        'locale',
        'subject',
        'preheader',
        'body_html',
        'status',
        'scheduled_at',
        'sent_at',
        'recipient_count',
        'sent_count',
        'failed_count',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => NewsletterCampaignStatus::class,
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'recipient_count' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NewsletterDelivery::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
