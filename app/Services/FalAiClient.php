<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FalAiClient
{
    private const PRICING_URL = 'https://api.fal.ai/v1/models/pricing';

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
}
