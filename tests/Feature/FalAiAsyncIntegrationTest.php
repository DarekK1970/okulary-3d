<?php

namespace Tests\Feature;

use App\Enums\FalAiJobOperation;
use App\Enums\FalAiJobStatus;
use App\Jobs\ProcessFalAiWebhook;
use App\Jobs\SubmitFalAiJob;
use App\Models\FalAiJob;
use App\Models\LenticularJob;
use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use App\Services\FalAiClient;
use App\Services\FalAiJobService;
use App\Services\FalAiResultService;
use App\Services\FalAiSettingsService;
use App\Services\FalWebhookSignatureVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class FalAiAsyncIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_is_submitted_with_private_signed_input_and_webhook_urls(): void
    {
        Storage::fake('local');
        Http::fake(['queue.fal.run/*' => Http::response(['request_id' => 'fal-request-1', 'status_url' => 'https://queue.fal.run/status'])]);
        $this->configureFal();
        $project = LenticularProject::factory()->create();
        $source = LenticularProjectFile::factory()->for($project)->create(['disk' => 'local']);
        $job = app(FalAiJobService::class)->create($project, FalAiJobOperation::ImageToVideo, 'bytedance/seedance-2.5/image-to-video', ['prompt' => 'Static scene'], (string) Str::uuid(), $source);

        (new SubmitFalAiJob($job->id))->handle(app(FalAiClient::class), app(FalAiJobService::class));

        $job->refresh();
        $this->assertSame(FalAiJobStatus::Submitted, $job->status);
        $this->assertSame('fal-request-1', $job->provider_request_id);
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($source): bool {
            $data = $request->data();

            return str_starts_with($request->url(), 'https://queue.fal.run/bytedance/seedance-2.5/image-to-video?')
                && str_contains($request->url(), 'fal_webhook=')
                && str_contains((string) ($data['image_url'] ?? ''), "/integrations/fal/input/{$source->id}")
                && filled($data['end_user_id'] ?? null);
        });
    }

    public function test_queue_method_dispatches_submission_job(): void
    {
        Queue::fake();
        $job = FalAiJob::factory()->create();
        app(FalAiJobService::class)->queueForSubmission($job);
        Queue::assertPushed(SubmitFalAiJob::class, fn (SubmitFalAiJob $queued): bool => $queued->falAiJobId === $job->id);
    }

    public function test_valid_ed25519_webhook_signature_is_accepted(): void
    {
        Cache::forget('fal-ai.webhook-jwks');
        $keyPair = sodium_crypto_sign_keypair();
        $publicKey = sodium_crypto_sign_publickey($keyPair);
        $secretKey = sodium_crypto_sign_secretkey($keyPair);
        Http::fake(['rest.fal.ai/.well-known/jwks.json' => Http::response(['keys' => [['x' => rtrim(strtr(base64_encode($publicKey), '+/', '-_'), '=')]]])]);
        $body = json_encode(['request_id' => 'req-1', 'status' => 'OK', 'payload' => []], JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $message = implode("\n", ['hook-1', 'fal-user', $timestamp, hash('sha256', $body)]);
        $signature = bin2hex(sodium_crypto_sign_detached($message, $secretKey));
        $request = Request::create('/integrations/fal/webhook', 'POST', [], [], [], [
            'HTTP_X_FAL_WEBHOOK_REQUEST_ID' => 'hook-1',
            'HTTP_X_FAL_WEBHOOK_USER_ID' => 'fal-user',
            'HTTP_X_FAL_WEBHOOK_TIMESTAMP' => $timestamp,
            'HTTP_X_FAL_WEBHOOK_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $this->assertTrue(app(FalWebhookSignatureVerifier::class)->verify($request));
    }

    public function test_webhook_downloads_result_once_and_completes_job(): void
    {
        Storage::fake('local');
        Http::fake(['https://v3.fal.media/*' => Http::response('video-bytes', 200, ['Content-Type' => 'video/mp4'])]);
        $project = LenticularProject::factory()->create();
        $service = app(FalAiJobService::class);
        $job = $service->create($project, FalAiJobOperation::ImageToVideo, 'model/path', [], (string) Str::uuid());
        $job = $service->markSubmitted($job, 'fal-result-1');
        $payload = ['request_id' => 'fal-result-1', 'status' => 'OK', 'payload' => ['video' => ['url' => 'https://v3.fal.media/result.mp4', 'content_type' => 'video/mp4']]];

        $handler = new ProcessFalAiWebhook($payload);
        $handler->handle($service, app(FalAiResultService::class));
        $handler->handle($service, app(FalAiResultService::class));

        $job->refresh();
        $this->assertSame(FalAiJobStatus::Succeeded, $job->status);
        $this->assertNotNull($job->result_file_id);
        $this->assertSame(1, LenticularProjectFile::query()->where('kind', 'source_video')->count());
        $this->assertSame(1, LenticularJob::query()->where('operation', 'analyze_video')->count());
        Storage::disk('local')->assertExists($job->resultFile->path);
    }

    public function test_unsigned_private_input_is_rejected(): void
    {
        $file = LenticularProjectFile::factory()->create();
        $this->get("/integrations/fal/input/{$file->id}")->assertForbidden();
    }

    public function test_webhook_endpoint_rejects_missing_signature(): void
    {
        $this->postJson('/integrations/fal/webhook', [
            'request_id' => 'request-1',
            'status' => 'OK',
            'payload' => [],
        ])->assertUnauthorized();
    }

    public function test_verified_webhook_is_acknowledged_and_queued(): void
    {
        Queue::fake();
        $verifier = $this->mock(FalWebhookSignatureVerifier::class);
        $verifier->shouldReceive('verify')->once()->andReturnTrue();

        $this->postJson('/integrations/fal/webhook', [
            'request_id' => 'request-1',
            'status' => 'OK',
            'payload' => ['video' => ['url' => 'https://v3.fal.media/result.mp4']],
        ])->assertOk()->assertJson(['accepted' => true]);

        Queue::assertPushed(ProcessFalAiWebhook::class);
    }

    private function configureFal(): void
    {
        $settings = app(FalAiSettingsService::class);
        $settings->set('enabled', '1');
        $settings->set('api_key', 'fal-test-key', true);
    }
}
