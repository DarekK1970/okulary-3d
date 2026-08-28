<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderWorkflowService
{
    public function __construct(
        private readonly TransactionalMailService $mail
    ) {
    }

    public function transition(
        Order $order,
        OrderStatus $target
    ): Order {
        if ($order->status === $target) {
            return $order;
        }

        if (! $order->status->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'status' => __('cart.admin.invalid_transition'),
            ]);
        }

        if (
            $target === OrderStatus::Processing
            && ! $order->isPaid()
        ) {
            throw ValidationException::withMessages([
                'status' => __(
                    'checkout71.admin.payment_required_for_processing'
                ),
            ]);
        }

        if (
            $target === OrderStatus::Cancelled
            && $order->payment_status === PaymentStatus::Paid
        ) {
            throw ValidationException::withMessages([
                'status' => __(
                    'checkout71.admin.cannot_cancel_paid_order'
                ),
            ]);
        }

        if (
            $target === OrderStatus::Cancelled
            && $order->payment_method === 'paynow'
            && $order->payment_status === PaymentStatus::Pending
        ) {
            throw ValidationException::withMessages([
                'status' => __(
                    'checkout71.admin.cannot_cancel_pending_online'
                ),
            ]);
        }

        $updated = DB::transaction(function () use (
            $order,
            $target
        ) {
            $lockedOrder = Order::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($order->id);

            if (! $lockedOrder->status->canTransitionTo($target)) {
                throw ValidationException::withMessages([
                    'status' => __('cart.admin.invalid_transition'),
                ]);
            }

            if (
                $target === OrderStatus::Cancelled
                && ! $lockedOrder->stock_released_at
            ) {
                foreach ($lockedOrder->items as $item) {
                    if (! $item->product_variant_id) {
                        continue;
                    }

                    $variant = ProductVariant::query()
                        ->lockForUpdate()
                        ->find($item->product_variant_id);

                    if ($variant && $variant->track_stock) {
                        $variant->increment(
                            'stock_quantity',
                            $item->quantity
                        );
                    }
                }

                $lockedOrder->stock_released_at = now();
                $lockedOrder->cancelled_at = now();
            }

            $lockedOrder->status = $target;
            $lockedOrder->save();

            return $lockedOrder->fresh(['items']);
        });

        if ($target === OrderStatus::Shipped) {
            $this->mail->orderShipped($updated);
        }

        return $updated;
    }
}
