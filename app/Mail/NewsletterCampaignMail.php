<?php

namespace App\Mail;

use App\Models\NewsletterCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterCampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public NewsletterCampaign $campaign,
        public string $unsubscribeUrl,
        public bool $isTest = false
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isTest
                ? '[TEST] ' . $this->campaign->subject
                : $this->campaign->subject
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter.campaign'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
