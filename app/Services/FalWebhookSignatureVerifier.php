<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FalWebhookSignatureVerifier
{
    private const JWKS_URL = 'https://rest.fal.ai/.well-known/jwks.json';

    public function verify(Request $request): bool
    {
        $requestId = $request->header('X-Fal-Webhook-Request-Id');
        $userId = $request->header('X-Fal-Webhook-User-Id');
        $timestamp = $request->header('X-Fal-Webhook-Timestamp');
        $signatureHex = $request->header('X-Fal-Webhook-Signature');

        if (! $requestId || ! $userId || ! $timestamp || ! $signatureHex || ! ctype_digit($timestamp)) {
            return false;
        }
        if (abs(now()->timestamp - (int) $timestamp) > 300) {
            return false;
        }

        $signature = hex2bin($signatureHex);
        if ($signature === false) {
            return false;
        }

        $message = implode("\n", [$requestId, $userId, $timestamp, hash('sha256', $request->getContent())]);
        foreach ($this->keys() as $key) {
            $publicKey = $this->base64UrlDecode((string) ($key['x'] ?? ''));
            if ($publicKey !== false && strlen($publicKey) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                && sodium_crypto_sign_verify_detached($signature, $message, $publicKey)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, array<string, mixed>> */
    private function keys(): array
    {
        return Cache::remember('fal-ai.webhook-jwks', now()->addHours(23), function (): array {
            $response = Http::acceptJson()->timeout(10)->get(self::JWKS_URL)->throw();

            return $response->json('keys', []);
        });
    }

    private function base64UrlDecode(string $value): string|false
    {
        $padded = str_pad(strtr($value, '-_', '+/'), (int) ceil(strlen($value) / 4) * 4, '=', STR_PAD_RIGHT);

        return base64_decode($padded, true);
    }
}
