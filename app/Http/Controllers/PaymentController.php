<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\PayNowService;
use App\Services\TransactionalMailService;
use Illuminate\Http\RedirectResponse;
use Throwable;

class PaymentController extends Controller
{
    public function payNowReturn(
        string $locale,
        Order $order,
        PayNowService $payNow,
        TransactionalMailService $mail
    ): RedirectResponse {
        if ($order->payment_method === 'paynow') {
            try {
                $becamePaid = $payNow->refresh($order);

                if ($becamePaid) {
                    $mail->paymentConfirmed($order->fresh());
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return redirect()->route('order.success', [
            'locale' => $locale,
            'order' => $order->public_token,
        ]);
    }

    public function retryPayNow(
        string $locale,
        Order $order,
        PayNowService $payNow
    ): RedirectResponse {
        if (
            $order->payment_method !== 'paynow'
            || $order->isPaid()
            || $order->status !== OrderStatus::Pending
        ) {
            return redirect()->route('order.success', [
                'locale' => $locale,
                'order' => $order->public_token,
            ]);
        }

        try {
            $payment = $payNow->start($order);

            return redirect()->away(
                $payment['redirectUrl']
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'payment' => __(
                    'checkout71.paynow.start_failed'
                ),
            ]);
        }
    }
}
