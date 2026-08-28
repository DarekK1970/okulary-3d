<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('checkout71.mail.shipped_subject', [
                'number' => $this->order->number,
            ])
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.shipped'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
