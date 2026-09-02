<?php

namespace App\Mail;

use App\Models\PartnerLink;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartnerBacklinkStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PartnerLink $partner,
        public string $event
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('partners.monitor.mail.subject.' . $this->event),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.partners.backlink-status',
        );
    }
}
