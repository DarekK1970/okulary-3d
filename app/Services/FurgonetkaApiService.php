<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderShipment;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class FurgonetkaApiService
{
    public const API_BASE = 'https://api.furgonetka.pl';

    public function __construct(
        private readonly FurgonetkaSettingsService $settings
    ) {
    }

    public function authorizationUrl(
        string $state
    ): string {
        $this->assertClientCredentials();

        return self::API_BASE
            . '/oauth/authorize?'
            . http_build_query([
                'response_type' => 'code',
                'client_id' =>
                    $this->settings->clientId(),
                'redirect_uri' =>
                    $this->settings
                        ->authorizationCallbackUrl(),
                'state' => $state,
                'scope' => 'api',
            ]);
    }

    public function exchangeCode(
        string $code
    ): void {
        $this->assertClientCredentials();

        $response = Http::asForm()
            ->withBasicAuth(
                (string) $this->settings
                    ->clientId(),
                (string) $this->settings
                    ->clientSecret()
            )
            ->timeout(20)
            ->post(
                self::API_BASE . '/oauth/token',
                [
                    'grant_type' =>
                        'authorization_code',
                    'code' => urldecode($code),
                    'redirect_uri' =>
                        $this->settings
                            ->authorizationCallbackUrl(),
                ]
            );

        $this->assertOk(
            $response,
            'OAuth token exchange'
        );

        $this->settings->saveTokens(
            (string) $response->json(
                'access_token'
            ),
            $response->json(
                'refresh_token'
            ),
            (int) $response->json(
                'expires_in',
                2678400
            )
        );
    }

    public function services(): array
    {
        $response = $this->request(
            'GET',
            '/account/services'
        );

        return array_values(
            $response->json(
                'services',
                []
            )
        );
    }

    public function testConnection(): array
    {
        return $this->services();
    }

    public function validatePackage(
        array $payload
    ): void {
        $response = $this->request(
            'POST',
            '/packages/validate',
            $payload,
            'v2'
        );

        if (! in_array(
            $response->status(),
            [200, 204],
            true
        )) {
            $this->assertOk(
                $response,
                'Package validation'
            );
        }
    }

    public function createPackage(
        Order $order,
        int $serviceId
    ): array {
        if ($order->shipping_method === 'pickup') {
            throw new RuntimeException(
                __('furgonetka.errors.local_pickup')
            );
        }

        if ($order->shipping_method === 'parcel_locker') {
            throw new RuntimeException(
                __('furgonetka.errors.point_shipment_guard')
            );
        }

        $payload = $this->packagePayload(
            $order,
            $serviceId
        );

        $this->validatePackage($payload);

        $response = $this->request(
            'POST',
            '/packages',
            $payload,
            'v2'
        );

        $data = $response->json();

        if (blank($data['package_id'] ?? null)) {
            throw new RuntimeException(
                __('furgonetka.errors.package_id_missing')
            );
        }

        return [
            'payload' => $payload,
            'response' => $data,
        ];
    }

    public function orderPackage(
        OrderShipment $shipment
    ): array {
        if (blank($shipment->external_package_id)) {
            throw new RuntimeException(
                __('furgonetka.errors.package_id_missing')
            );
        }

        $uuid = (string) Str::uuid();

        $payload = [
            'packages' => [
                [
                    'id' =>
                        $shipment
                            ->external_package_id,
                ],
            ],
            'skip_email_send' => true,
            'label' => [
                'file_format' =>
                    $this->settings
                        ->labelFormat(),
            ],
        ];

        if (
            $this->settings->labelFormat()
            === 'pdf'
        ) {
            $payload['label'][
                'page_format'
            ] = $this->settings
                ->labelPageFormat();
        }

        $response = $this->request(
            'PUT',
            '/order-commands/' . $uuid,
            $payload
        );

        return [
            'uuid' => $uuid,
            'response' =>
                $response->json(),
        ];
    }

    public function orderCommandStatus(
        string $uuid
    ): array {
        return $this->request(
            'GET',
            '/order-commands/' . $uuid
        )->json();
    }

    public function waitForOrderCommand(
        string $uuid,
        int $attempts = 8
    ): array {
        $last = [];

        for (
            $attempt = 0;
            $attempt < max(1, $attempts);
            $attempt++
        ) {
            if ($attempt > 0) {
                usleep(350000);
            }

            $last = $this
                ->orderCommandStatus($uuid);

            if (
                ! in_array(
                    $last['status']
                        ?? null,
                    [
                        'queueing',
                        'running',
                    ],
                    true
                )
            ) {
                return $last;
            }
        }

        return $last;
    }

    public function packageDetails(
        string $packageId
    ): array {
        return $this->request(
            'GET',
            '/packages/' . rawurlencode(
                $packageId
            )
        )->json();
    }

    public function tracking(
        string $packageId
    ): array {
        return $this->request(
            'GET',
            '/packages/' . rawurlencode(
                $packageId
            ) . '/tracking'
        )->json();
    }

    public function label(
        string $packageId
    ): Response {
        $query = [];

        if (
            $this->settings->labelFormat()
            === 'pdf'
        ) {
            $query[
                'label[page_format]'
            ] = $this->settings
                ->labelPageFormat();
        }

        $path = '/packages/'
            . rawurlencode($packageId)
            . '/label';

        if ($query !== []) {
            $path .= '?'
                . http_build_query($query);
        }

        return $this->request(
            'GET',
            $path,
            [],
            'v1',
            expectJson: false
        );
    }

    public function syncShipment(
        OrderShipment $shipment
    ): OrderShipment {
        if (blank($shipment->external_package_id)) {
            return $shipment;
        }

        $details = $this->packageDetails(
            $shipment->external_package_id
        );

        $tracking = $this->tracking(
            $shipment->external_package_id
        );

        $events = $tracking[
            'tracking'
        ] ?? [];

        $latest = is_array($events)
            && $events !== []
            ? end($events)
            : null;

        $shipment->update([
            'carrier' =>
                $details['service']
                ?? $shipment->carrier,
            'state' =>
                $details['state']
                ?? $shipment->state,
            'tracking_number' =>
                $details['tracking_number']
                ?? $details['number']
                ?? $shipment
                    ->tracking_number,
            'tracking_url' =>
                $details['tracking_url']
                ?? $shipment
                    ->tracking_url,
            'last_tracking_state' =>
                $latest['state']
                ?? $shipment
                    ->last_tracking_state,
            'last_tracking_status' =>
                $latest['status']
                ?? $shipment
                    ->last_tracking_status,
            'last_tracking_at' =>
                filled(
                    $latest['datetime']
                    ?? null
                )
                    ? $latest['datetime']
                    : $shipment
                        ->last_tracking_at,
            'response_snapshot' =>
                $details,
        ]);

        return $shipment->fresh();
    }

    private function packagePayload(
        Order $order,
        int $serviceId
    ): array {
        $sender = $this->settings
            ->sender();

        foreach (
            [
                'name',
                'email',
                'phone',
                'street',
                'city',
                'postcode',
            ]
            as $required
        ) {
            if (blank($sender[$required] ?? null)) {
                throw new RuntimeException(
                    __('furgonetka.errors.sender_incomplete')
                );
            }
        }

        $dimensions = $this->settings
            ->parcelDefaults();

        return [
            'pickup' => [
                'name' => $sender['name'],
                'company' =>
                    $sender['company'] ?? '',
                'email' => $sender['email'],
                'phone' => $sender['phone'],
                'street' => $sender['street'],
                'city' => $sender['city'],
                'country_code' =>
                    $sender['country_code'],
                'postcode' =>
                    $sender['postcode'],
                'county' =>
                    $sender['county'] ?? '',
            ],
            'receiver' => [
                'name' => trim(
                    $order->shipping_first_name
                    . ' '
                    . $order->shipping_last_name
                ),
                'company' =>
                    $order->shipping_company
                    ?? '',
                'email' =>
                    $order->customer_email,
                'phone' =>
                    $order->customer_phone
                    ?? '',
                'street' =>
                    $order
                        ->shipping_address_line1,
                'city' =>
                    $order->shipping_city,
                'country_code' =>
                    $order
                        ->shipping_country_code,
                'postcode' =>
                    $order
                        ->shipping_postal_code,
                'county' => '',
            ],
            'service_id' => $serviceId,
            'additional_services' => [],
            'parcels' => [
                [
                    'height' =>
                        $dimensions['height'],
                    'width' =>
                        $dimensions['width'],
                    'depth' =>
                        $dimensions['depth'],
                    'weight' => max(
                        0.01,
                        round(
                            ((int) $order->shipping_weight_grams) / 1000,
                            3
                        )
                    ),
                    'quantity' => 1,
                    'type' => 'package',
                ],
            ],
            'user_reference_number' =>
                $order->number,
        ];
    }

    private function request(
        string $method,
        string $path,
        array $payload = [],
        string $version = 'v1',
        bool $expectJson = true
    ): Response {
        $token = $this->validAccessToken();

        $request = Http::withToken($token)
            ->timeout(25)
            ->withHeaders([
                'Accept' =>
                    'application/vnd.furgonetka.'
                    . $version
                    . '+json',
            ]);

        if (
            in_array(
                strtoupper($method),
                ['POST', 'PUT', 'PATCH'],
                true
            )
        ) {
            $request = $request
                ->withHeaders([
                    'Content-Type' =>
                        'application/vnd.furgonetka.'
                        . $version
                        . '+json',
                ]);
        }

        $url = self::API_BASE . $path;

        $response = match (
            strtoupper($method)
        ) {
            'GET' =>
                $request->get($url),
            'POST' =>
                $request->post(
                    $url,
                    $payload
                ),
            'PUT' =>
                $request->put(
                    $url,
                    $payload
                ),
            default =>
                throw new RuntimeException(
                    'Unsupported HTTP method.'
                ),
        };

        if (
            $expectJson
            && ! $response->successful()
        ) {
            $this->assertOk(
                $response,
                $path
            );
        }

        if (
            ! $expectJson
            && $response->status() >= 400
        ) {
            $this->assertOk(
                $response,
                $path
            );
        }

        return $response;
    }

    private function validAccessToken(): string
    {
        $this->assertClientCredentials();

        $accessToken =
            $this->settings
                ->accessToken();

        if (blank($accessToken)) {
            throw new RuntimeException(
                __('furgonetka.errors.not_connected')
            );
        }

        $expiresAt =
            $this->settings
                ->tokenExpiresAt();

        if (
            filled($expiresAt)
            && now()->addMinutes(5)
                ->greaterThanOrEqualTo(
                    \Illuminate\Support\Carbon::parse(
                        $expiresAt
                    )
                )
        ) {
            $this->refreshAccessToken();

            $accessToken =
                $this->settings
                    ->accessToken();
        }

        return (string) $accessToken;
    }

    private function refreshAccessToken(): void
    {
        $refreshToken =
            $this->settings
                ->refreshToken();

        if (blank($refreshToken)) {
            throw new RuntimeException(
                __('furgonetka.errors.reconnect_required')
            );
        }

        $response = Http::asForm()
            ->withBasicAuth(
                (string) $this->settings
                    ->clientId(),
                (string) $this->settings
                    ->clientSecret()
            )
            ->timeout(20)
            ->post(
                self::API_BASE . '/oauth/token',
                [
                    'grant_type' =>
                        'refresh_token',
                    'refresh_token' =>
                        $refreshToken,
                ]
            );

        $this->assertOk(
            $response,
            'OAuth refresh'
        );

        $this->settings->saveTokens(
            (string) $response->json(
                'access_token'
            ),
            $response->json(
                'refresh_token'
            ),
            (int) $response->json(
                'expires_in',
                2678400
            )
        );
    }

    private function assertClientCredentials(): void
    {
        if (
            blank(
                $this->settings
                    ->clientId()
            )
            || blank(
                $this->settings
                    ->clientSecret()
            )
        ) {
            throw new RuntimeException(
                __('furgonetka.errors.credentials_missing')
            );
        }
    }

    private function assertOk(
        Response $response,
        string $context
    ): void {
        if ($response->successful()) {
            return;
        }

        $message = $response->json(
            'error_description'
        )
            ?? $response->json(
                'message'
            )
            ?? collect(
                $response->json(
                    'errors',
                    []
                )
            )
                ->map(
                    fn ($error) =>
                        ($error['path'] ?? '?')
                        . ': '
                        . ($error['message']
                            ?? 'API error')
                )
                ->implode('; ')
            ?: 'HTTP ' . $response->status();

        throw new RuntimeException(
            $context . ': ' . $message
        );
    }
}
