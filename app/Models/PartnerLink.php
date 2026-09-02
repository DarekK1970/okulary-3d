<?php

namespace App\Models;

use App\Enums\PartnerLinkStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PartnerLink extends Model
{
    protected $fillable = [
        'source_locale',
        'name',
        'website_url',
        'domain',
        'backlink_url',
        'description',
        'logo_path',
        'email',
        'commercial',
        'contact_person',
        'phone',
        'status',
        'backlink_commitment_at',
        'privacy_accepted_at',
        'verification_token_hash',
        'verification_sent_at',
        'email_verified_at',
        'approved_at',
        'approved_by',
        'rejected_at',
        'banned_at',
        'banned_by',
        'banned_reason',
        'last_checked_at',
        'last_http_status',
        'last_backlink_found_at',
        'consecutive_failures',
        'last_check_error',
        'click_count',
    ];

    protected function casts(): array
    {
        return [
            'commercial' => 'boolean',
            'status' => PartnerLinkStatus::class,
            'backlink_commitment_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'verification_sent_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'banned_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'last_backlink_found_at' => 'datetime',
            'consecutive_failures' => 'integer',
            'click_count' => 'integer',
        ];
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function banner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }
}
