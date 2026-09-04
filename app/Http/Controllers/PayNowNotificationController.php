<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PlanPurchase;
use App\Services\PayNowService;
use App\Services\TransactionalMailService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PayNowNotificationController extends Controller
{
    public function __invoke(
        Request $request,
        PayNowService $payNow,
        TransactionalMailService $mail
    ): Response {
        $rawBody = $request->getContent();
        $signature = $request->header('Signature');

        if (! $payNow->verifyNotification(
            $rawBody,
            $signature
        )) {
            return response('', 401);
        }

        $payload = json_decode(
            $rawBody,
            true
        );

        if (
            ! is_array($payload)
            || empty($payload['externalId'])
            || empty($payload['status'])
        ) {
            return response('', 202);
        }

        $order = Order::query()
            ->where(
                'payment_merchant_external_id',
                $payload['externalId']
            )
            ->first();

        if (! $order) {
            $purchase = PlanPurchase::query()->where('payment_merchant_external_id', $payload['externalId'])->first();
            if ($purchase) {
                $payNow->applyPlanPurchaseStatus($purchase, (string) $payload['status'], isset($payload['paymentId']) ? (string) $payload['paymentId'] : null);
            }

            return response('', 202);
        }

        $becamePaid = $payNow->applyStatus(
            $order,
            (string) $payload['status'],
            isset($payload['paymentId'])
                ? (string) $payload['paymentId']
                : null
        );

        if ($becamePaid) {
            $mail->paymentConfirmed($order->fresh());
        }

        return response('', 202);
    }
}
