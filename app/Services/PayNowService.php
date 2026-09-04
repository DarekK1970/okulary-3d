<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PlanPurchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PayNowService
{
    public function __construct(
        private readonly CommerceSettingsService $settings
    ) {}

    public function enabled(): bool
    {
        return $this->settings->payNowEnabled();
    }

    public function startPlanPurchase(PlanPurchase $purchase, string $locale): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException(__('checkout71.paynow.not_configured'));
        }
        if ($purchase->status === 'pending' && $purchase->payment_redirect_url) {
            return ['paymentId' => $purchase->payment_external_id, 'redirectUrl' => $purchase->payment_redirect_url, 'status' => 'PENDING'];
        }

        $externalId = $purchase->payment_merchant_external_id ?: 'plan-'.$purchase->id.'-'.Str::lower(Str::random(10));
        $idempotencyKey = $purchase->payment_idempotency_key ?: (string) Str::uuid();
        $purchase->update(['status' => 'pending', 'payment_merchant_external_id' => $externalId, 'payment_idempotency_key' => $idempotencyKey]);
        $body = ['amount' => $this->moneyToCents((string) $purchase->price), 'currency' => 'PLN', 'externalId' => $externalId, 'description' => strtoupper($purchase->plan).' — 3 months', 'continueUrl' => route('plans.payment.return', ['locale' => $locale, 'purchase' => $purchase->public_token]), 'buyer' => ['email' => Str::limit($purchase->user->email, 50, ''), 'firstName' => Str::limit($purchase->user->name, 50, ''), 'locale' => $locale === 'en' ? 'en-US' : 'pl-PL']];
        $json = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $response = Http::timeout($this->settings->payNowTimeout())->acceptJson()->withHeaders($this->headers($idempotencyKey, $json))->withBody($json, 'application/json')->post($this->baseUrl().'/v3/payments');
        if (! $response->successful() || ! $response->json('paymentId') || ! $response->json('redirectUrl')) {
            $purchase->update(['status' => 'failed', 'payment_error' => Str::limit($response->body(), 2000)]);
            throw new RuntimeException('PayNow API error');
        }
        $purchase->update(['payment_external_id' => $response->json('paymentId'), 'payment_redirect_url' => $response->json('redirectUrl')]);

        return ['paymentId' => $response->json('paymentId'), 'redirectUrl' => $response->json('redirectUrl'), 'status' => $response->json('status', 'NEW')];
    }

    public function refreshPlanPurchase(PlanPurchase $purchase): bool
    {
        if (! $this->enabled() || ! $purchase->payment_external_id) {
            return false;
        }
        $key = (string) Str::uuid();
        $response = Http::timeout($this->settings->payNowTimeout())->acceptJson()->withHeaders($this->headers($key, ''))->get($this->baseUrl().'/v3/payments/'.urlencode($purchase->payment_external_id).'/status');

        return $response->successful() && $this->applyPlanPurchaseStatus($purchase, (string) $response->json('status'), (string) $response->json('paymentId'));
    }

    public function applyPlanPurchaseStatus(PlanPurchase $purchase, string $status, ?string $paymentId = null): bool
    {
        if ($paymentId && $purchase->payment_external_id && $paymentId !== $purchase->payment_external_id) {
            return false;
        }
        if (strtoupper($status) !== 'CONFIRMED' || $purchase->status === 'paid') {
            return false;
        }
        DB::transaction(function () use ($purchase): void {
            $locked = PlanPurchase::query()->lockForUpdate()->findOrFail($purchase->id);
            if ($locked->status === 'paid') {
                return;
            }
            $user = $locked->user;
            $startsAt = $user->plan_expires_at?->isFuture() ? $user->plan_expires_at->copy() : now();
            $expiresAt = $startsAt->addMonths($locked->duration_months);
            $user->update(['lenticular_plan' => $locked->plan, 'plan_expires_at' => $expiresAt]);
            if ($locked->token_lens > 0) {
                app(TokenLensWalletService::class)->grant($user, $locked->token_lens, 'subscription', 'plan-purchase:'.$locked->id, $expiresAt, strtoupper($locked->plan).' plan');
            }
            $locked->update(['status' => 'paid', 'paid_at' => now()]);
        });

        return true;
    }

    /**
     * @return array{paymentId:string, redirectUrl:string, status:string}
     */
    public function start(Order $order): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException(
                __('checkout71.paynow.not_configured')
            );
        }

        if (
            ! $this->settings
                ->payNowSupportsCurrency(
                    $order->currency
                )
        ) {
            throw new RuntimeException(
                __('checkout71.paynow.currency_not_supported')
            );
        }

        if (
            $order->payment_status === PaymentStatus::Pending
            && $order->payment_redirect_url
            && $order->payment_external_id
        ) {
            return [
                'paymentId' => $order->payment_external_id,
                'redirectUrl' => $order->payment_redirect_url,
                'status' => 'PENDING',
            ];
        }

        $reusePendingRequest =
            $order->payment_status === PaymentStatus::Pending
            && filled($order->payment_merchant_external_id)
            && filled($order->payment_idempotency_key);

        $merchantExternalId = $reusePendingRequest
            ? $order->payment_merchant_external_id
            : sprintf(
                'ord-%d-%s',
                $order->id,
                Str::lower(Str::random(10))
            );

        $idempotencyKey = $reusePendingRequest
            ? $order->payment_idempotency_key
            : (string) Str::uuid();

        if (! $reusePendingRequest) {
            $order->update([
                'payment_status' => PaymentStatus::Pending,
                'payment_merchant_external_id' => $merchantExternalId,
                'payment_idempotency_key' => $idempotencyKey,
                'payment_external_id' => null,
                'payment_redirect_url' => null,
                'payment_error' => null,
                'payment_failed_at' => null,
            ]);
        }

        $body = [
            'amount' => $this->moneyToCents(
                (string) $order->total_gross
            ),
            'currency' => $order->currency,
            'externalId' => $merchantExternalId,
            'description' => 'Order '.$order->number,
            'continueUrl' => route('payment.paynow.return', [
                'locale' => $order->locale,
                'order' => $order->public_token,
            ]),
            'buyer' => [
                'email' => Str::limit(
                    $order->customer_email,
                    50,
                    ''
                ),
                'firstName' => Str::limit(
                    $order->customer_first_name,
                    50,
                    ''
                ),
                'lastName' => Str::limit(
                    $order->customer_last_name,
                    50,
                    ''
                ),
                'locale' => $order->locale === 'en'
                    ? 'en-US'
                    : 'pl-PL',
            ],
        ];

        $bodyJson = json_encode(
            $body,
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        $response = Http::timeout(
            $this->settings->payNowTimeout()
        )
            ->acceptJson()
            ->withHeaders(
                $this->headers(
                    $idempotencyKey,
                    $bodyJson
                )
            )
            ->withBody($bodyJson, 'application/json')
            ->post($this->baseUrl().'/v3/payments');

        if (! $response->successful()) {
            $message = $response->json('errors.0.message')
                ?: $response->body()
                ?: 'PayNow API error';

            $order->update([
                'payment_status' => PaymentStatus::Failed,
                'payment_error' => Str::limit($message, 2000),
                'payment_failed_at' => now(),
            ]);

            throw new RuntimeException($message);
        }

        $data = $response->json();

        if (
            empty($data['paymentId'])
            || empty($data['redirectUrl'])
        ) {
            throw new RuntimeException(
                __('checkout71.paynow.invalid_response')
            );
        }

        $order->update([
            'payment_status' => PaymentStatus::Pending,
            'payment_merchant_external_id' => $merchantExternalId,
            'payment_idempotency_key' => $idempotencyKey,
            'payment_external_id' => $data['paymentId'],
            'payment_redirect_url' => $data['redirectUrl'],
            'payment_error' => null,
            'payment_failed_at' => null,
        ]);

        return [
            'paymentId' => $data['paymentId'],
            'redirectUrl' => $data['redirectUrl'],
            'status' => $data['status'] ?? 'NEW',
        ];
    }

    public function refresh(Order $order): bool
    {
        if (
            ! $this->enabled()
            || ! $order->payment_external_id
        ) {
            return false;
        }

        $idempotencyKey = (string) Str::uuid();
        $bodyJson = '';

        $response = Http::timeout(
            $this->settings->payNowTimeout()
        )
            ->acceptJson()
            ->withHeaders(
                $this->headers(
                    $idempotencyKey,
                    $bodyJson
                )
            )
            ->get(
                $this->baseUrl()
                .'/v3/payments/'
                .urlencode($order->payment_external_id)
                .'/status'
            );

        if (! $response->successful()) {
            return false;
        }

        return $this->applyStatus(
            $order,
            (string) $response->json('status'),
            (string) $response->json('paymentId')
        );
    }

    public function verifyNotification(
        string $rawBody,
        ?string $signature
    ): bool {
        $signatureKey = $this->settings->payNowSignatureKey();

        if (! $signature || ! filled($signatureKey)) {
            return false;
        }

        $calculated = base64_encode(
            hash_hmac(
                'sha256',
                $rawBody,
                $signatureKey,
                true
            )
        );

        return hash_equals($calculated, $signature);
    }

    public function applyStatus(
        Order $order,
        string $status,
        ?string $paymentId = null
    ): bool {
        $status = strtoupper($status);
        $becamePaid = false;

        if (
            $paymentId
            && $order->payment_external_id
            && $paymentId !== $order->payment_external_id
        ) {
            return false;
        }

        if (
            $paymentId
            && ! $order->payment_external_id
        ) {
            $order->payment_external_id = $paymentId;
        }

        if ($status === 'CONFIRMED') {
            if (! $order->isPaid()) {
                $order->payment_status = PaymentStatus::Paid;
                $order->paid_at = now();
                $order->payment_failed_at = null;
                $order->payment_error = null;
                $becamePaid = true;
            }
        } elseif (
            in_array(
                $status,
                ['ABANDONED', 'ERROR', 'EXPIRED', 'REJECTED'],
                true
            )
            && ! $order->isPaid()
        ) {
            $order->payment_status = PaymentStatus::Failed;
            $order->payment_failed_at = now();
        } elseif (
            in_array($status, ['NEW', 'PENDING'], true)
            && ! $order->isPaid()
        ) {
            $order->payment_status = PaymentStatus::Pending;
        }

        $order->save();

        return $becamePaid;
    }

    private function baseUrl(): string
    {
        return $this->settings->payNowSandbox()
            ? (string) config('paynow.sandbox_url')
            : (string) config('paynow.production_url');
    }

    /**
     * @return array<string, string>
     */
    private function headers(
        string $idempotencyKey,
        string $bodyJson
    ): array {
        $apiKey = (string) $this->settings->payNowApiKey();
        $signatureKey = (string)
            $this->settings->payNowSignatureKey();

        return [
            'Api-Key' => $apiKey,
            'Idempotency-Key' => $idempotencyKey,
            'Signature' => $this->signature(
                $apiKey,
                $signatureKey,
                $idempotencyKey,
                $bodyJson
            ),
            'Accept' => 'application/json',
        ];
    }

    private function signature(
        string $apiKey,
        string $signatureKey,
        string $idempotencyKey,
        string $bodyJson
    ): string {
        $payload = json_encode([
            'headers' => [
                'Api-Key' => $apiKey,
                'Idempotency-Key' => $idempotencyKey,
            ],
            'parameters' => (object) [],
            'body' => $bodyJson,
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return base64_encode(
            hash_hmac(
                'sha256',
                $payload,
                $signatureKey,
                true
            )
        );
    }

    private function moneyToCents(string $value): int
    {
        return (int) round(((float) $value) * 100);
    }
}
