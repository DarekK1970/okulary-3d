<?php

namespace App\Models;

use App\Enums\NewsletterSubscriberStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email',
        'locale',
        'status',
        'source',
        'confirmation_token',
        'unsubscribe_token',
        'consent_requested_at',
        'confirmed_at',
        'unsubscribed_at',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => NewsletterSubscriberStatus::class,
            'confirmation_token' => 'encrypted',
            'unsubscribe_token' => 'encrypted',
            'consent_requested_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NewsletterDelivery::class);
    }

    public function isActive(): bool
    {
        return $this->status === NewsletterSubscriberStatus::Active;
    }
}
