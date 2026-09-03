<?php

namespace App\Http\Middleware;

use App\Models\ProcessingMachine;
use App\Models\ProcessingMachineNonce;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyProcessingMachineSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $machineId = (string) $request->header('X-Machine-Id', '');
        $keyId = (string) $request->header('X-Api-Key-Id', '');
        $timestamp = (string) $request->header('X-Timestamp', '');
        $nonce = (string) $request->header('X-Nonce', '');
        $contentHash = strtolower((string) $request->header('X-Content-SHA256', ''));
        $signature = strtolower((string) $request->header('X-Signature', ''));

        if (! ctype_digit($timestamp) || ! preg_match('/^[a-f0-9]{64}$/', $contentHash) || ! preg_match('/^[a-f0-9]{64}$/', $signature) || ! preg_match('/^[A-Za-z0-9_-]{1,64}$/', $nonce)) {
            return $this->unauthorized();
        }

        if (abs(now()->timestamp - (int) $timestamp) > config('lenticular_machine.signature_tolerance_seconds')) {
            return $this->unauthorized();
        }

        $machine = ProcessingMachine::query()
            ->where('machine_id', $machineId)
            ->where('api_key_id', $keyId)
            ->where('is_active', true)
            ->first();

        $body = $request->getContent();
        if ($machine === null || ! hash_equals(hash('sha256', $body), $contentHash)) {
            return $this->unauthorized();
        }

        $canonical = implode("\n", [$request->method(), $request->getPathInfo(), $timestamp, $nonce, $contentHash]);
        $expected = hash_hmac('sha256', $canonical, $machine->api_secret);
        if (! hash_equals($expected, $signature)) {
            return $this->unauthorized();
        }

        try {
            ProcessingMachineNonce::query()->create([
                'processing_machine_id' => $machine->id,
                'nonce' => $nonce,
                'expires_at' => now()->addSeconds(config('lenticular_machine.signature_tolerance_seconds')),
            ]);
        } catch (QueryException) {
            return $this->unauthorized();
        }

        $machine->forceFill(['last_seen_at' => now()])->save();
        $request->attributes->set('processingMachine', $machine);

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response()->json(['message' => 'Unauthorized.'], 401);
    }
}
