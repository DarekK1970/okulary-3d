<?php

namespace App\Models;

use App\Enums\NewsletterDeliveryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterDelivery extends Model
{
    protected $fillable = [
        'newsletter_campaign_id',
        'newsletter_subscriber_id',
        'email_snapshot',
        'status',
        'attempts',
        'sent_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status' => NewsletterDeliveryStatus::class,
            'attempts' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(
            NewsletterCampaign::class,
            'newsletter_campaign_id'
        );
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(
            NewsletterSubscriber::class,
            'newsletter_subscriber_id'
        );
    }
}
