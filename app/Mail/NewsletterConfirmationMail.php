<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public NewsletterSubscriber $subscriber,
        public string $token
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('newsletter.mail.confirm_subject')
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter.confirm'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
