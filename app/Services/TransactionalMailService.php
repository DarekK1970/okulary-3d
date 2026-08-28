<?php

namespace App\Services;

use App\Mail\OrderPlacedMail;
use App\Mail\OrderShippedMail;
use App\Mail\PaymentConfirmedMail;
use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TransactionalMailService
{
    public function orderPlaced(Order $order): void
    {
        $this->send(
            $order,
            new OrderPlacedMail($order)
        );
    }

    public function paymentConfirmed(Order $order): void
    {
        $this->send(
            $order,
            new PaymentConfirmedMail($order)
        );
    }

    public function orderShipped(Order $order): void
    {
        $this->send(
            $order,
            new OrderShippedMail($order)
        );
    }

    private function send(
        Order $order,
        Mailable $mailable
    ): void {
        try {
            Mail::to($order->customer_email)
                ->send($mailable->locale($order->locale));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
