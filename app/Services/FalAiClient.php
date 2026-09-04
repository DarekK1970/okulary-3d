<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FalAiClient
{
    private const PRICING_URL = 'https://api.fal.ai/v1/models/pricing';

    private const QUEUE_URL = 'https://queue.fal.run';

    public function __construct(private readonly FalAiSettingsService $settings) {}

    /** @return array<string, mixed> */
    public function testConnection(): array
    {
        if (blank($this->settings->apiKey())) {
            throw new RuntimeException(__('fal_ai.messages.missing_key'));
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Key '.$this->settings->apiKey(),
                'Accept' => 'application/json',
            ])->timeout($this->settings->timeout())->get(self::PRICING_URL, [
                'endpoint_id' => $this->settings->seedanceModel(),
            ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException(__('fal_ai.messages.connection_error'), previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException(__('fal_ai.messages.test_failed', ['status' => $response->status()]));
        }

        return $response->json();
    }

    /** @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function submit(string $endpoint, array $parameters, string $webhookUrl): array
    {
        $this->assertConfigured();
        $this->assertEndpoint($endpoint);

        $response = $this->request()->post(
            self::QUEUE_URL.'/'.$endpoint.'?'.http_build_query(['fal_webhook' => $webhookUrl]),
            $parameters
        );

        if (! $response->successful() || blank($response->json('request_id'))) {
            throw new RuntimeException('fal.ai queue submission failed (HTTP '.$response->status().').');
        }

        return $response->json();
    }

    /** @return array<string, mixed> */
    public function status(string $endpoint, string $requestId): array
    {
        $this->assertConfigured();
        $this->assertEndpoint($endpoint);

        $response = $this->request()->get(self::QUEUE_URL.'/'.$endpoint.'/requests/'.rawurlencode($requestId).'/status');
        if (! $response->successful()) {
            throw new RuntimeException('fal.ai status request failed (HTTP '.$response->status().').');
        }

        return $response->json();
    }

    /** @return array<string, mixed> */
    public function result(string $endpoint, string $requestId): array
    {
        $this->assertConfigured();
        $this->assertEndpoint($endpoint);

        $response = $this->request()->get(self::QUEUE_URL.'/'.$endpoint.'/requests/'.rawurlencode($requestId));
        if (! $response->successful()) {
            throw new RuntimeException('fal.ai result request failed (HTTP '.$response->status().').');
        }

        return $response->json();
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Key '.$this->settings->apiKey(),
            'Accept' => 'application/json',
        ])->asJson()->timeout($this->settings->timeout());
    }

    private function assertConfigured(): void
    {
        if (! $this->settings->configured()) {
            throw new RuntimeException('fal.ai integration is not configured.');
        }
    }

    private function assertEndpoint(string $endpoint): void
    {
        if (! preg_match('/^[a-zA-Z0-9._-]+\/[a-zA-Z0-9._\/-]+$/', $endpoint)) {
            throw new RuntimeException('Invalid fal.ai endpoint.');
        }
    }
}
