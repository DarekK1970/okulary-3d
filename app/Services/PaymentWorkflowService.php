<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Validation\ValidationException;

class PaymentWorkflowService
{
    public function __construct(
        private readonly TransactionalMailService $mail
    ) {
    }

    public function markBankTransferPaid(Order $order): void
    {
        if ($order->payment_method !== 'bank_transfer') {
            throw ValidationException::withMessages([
                'payment_status' => __(
                    'checkout71.admin.manual_only_bank_transfer'
                ),
            ]);
        }

        if ($order->isPaid()) {
            return;
        }

        $order->update([
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'payment_failed_at' => null,
            'payment_error' => null,
        ]);

        $this->mail->paymentConfirmed($order->fresh());
    }

    public function markBankTransferUnpaid(Order $order): void
    {
        if ($order->payment_method !== 'bank_transfer') {
            throw ValidationException::withMessages([
                'payment_status' => __(
                    'checkout71.admin.manual_only_bank_transfer'
                ),
            ]);
        }

        if ($order->status !== OrderStatus::Pending) {
            throw ValidationException::withMessages([
                'payment_status' => __(
                    'checkout71.admin.cannot_revert_after_processing'
                ),
            ]);
        }

        $order->update([
            'payment_status' => PaymentStatus::Unpaid,
            'paid_at' => null,
        ]);
    }
}
