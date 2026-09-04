<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessFalAiWebhook;
use App\Services\FalWebhookSignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FalAiWebhookController extends Controller
{
    public function __invoke(Request $request, FalWebhookSignatureVerifier $verifier): JsonResponse
    {
        abort_unless($verifier->verify($request), 401);
        $payload = $request->validate([
            'request_id' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:OK,ERROR'],
            'payload' => ['nullable', 'array'],
            'error' => ['nullable', 'string', 'max:5000'],
        ]);

        ProcessFalAiWebhook::dispatch($payload);

        return response()->json(['accepted' => true]);
    }
}
