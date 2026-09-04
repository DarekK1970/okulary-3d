<?php

namespace Tests\Feature;

use App\Enums\LenticularJobStatus;
use App\Models\LenticularJob;
use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use App\Models\ProcessingMachine;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LenticularWorkerApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const SECRET = '0123456789abcdef0123456789abcdef';

    public function test_claim_rejects_missing_signature(): void
    {
        $this->postJson('/api/worker/v1/jobs/claim', [
            'lease_seconds' => 120,
            'capabilities' => ['extract_video_frames:v1'],
        ])->assertUnauthorized();
    }

    public function test_claim_rejects_incorrect_signature(): void
    {
        $machine = ProcessingMachine::factory()->create(['api_secret' => str_repeat('x', 32)]);

        $this->signedJson('POST', '/api/worker/v1/jobs/claim', [
            'lease_seconds' => 120,
            'capabilities' => ['extract_video_frames:v1'],
        ], $machine)->assertUnauthorized();
    }

    public function test_valid_machine_claims_job_and_downloads_source(): void
    {
        Storage::fake('local');
        [$machine, $job] = $this->queuedJob('video-content');

        $response = $this->signedJson('POST', '/api/worker/v1/jobs/claim', [
            'lease_seconds' => 120,
            'capabilities' => ['extract_video_frames:v1'],
        ], $machine);

        $response->assertOk()->assertJsonPath('job_id', $job->id)
            ->assertJsonPath('operation', 'extract_video_frames')
            ->assertJsonPath('source.size_bytes', 13)
            ->assertJsonPath('selection.end', 12);
        $this->assertSame(64, strlen($response->json('lease_token')));
        $this->assertSame(LenticularJobStatus::Leased, $job->fresh()->status);
        $download = $this->get($this->localUrl($response->json('source.url')));
        $download->assertOk()->assertHeader('content-type', 'video/mp4');
        $this->assertSame('video-content', file_get_contents($download->baseResponse->getFile()->getPathname()));
    }

    public function test_nonce_cannot_be_replayed(): void
    {
        $machine = ProcessingMachine::factory()->create(['api_secret' => self::SECRET]);
        $payload = ['lease_seconds' => 120, 'capabilities' => ['extract_video_frames:v1']];

        $this->signedJson('POST', '/api/worker/v1/jobs/claim', $payload, $machine, 'fixed-nonce')->assertNoContent();
        $this->signedJson('POST', '/api/worker/v1/jobs/claim', $payload, $machine, 'fixed-nonce')->assertUnauthorized();
    }

    public function test_worker_uploads_and_completes_artifact_idempotently(): void
    {
        Storage::fake('local');
        [$machine, $job] = $this->queuedJob('video-content');
        $claim = $this->signedJson('POST', '/api/worker/v1/jobs/claim', [
            'lease_seconds' => 120,
            'capabilities' => ['extract_video_frames:v1'],
        ], $machine);
        $artifact = 'zip-result';

        $this->call('PUT', $this->localUrl($claim->json('upload_url')), [], [], [], [
            'CONTENT_TYPE' => 'application/zip',
        ], $artifact)->assertCreated();

        $completePayload = [
            'lease_token' => $claim->json('lease_token'),
            'artifact' => [
                'sha256' => hash('sha256', $artifact),
                'size_bytes' => strlen($artifact),
                'media_type' => 'application/zip',
            ],
        ];
        $path = "/api/worker/v1/jobs/{$job->id}/complete";
        $this->signedJson('POST', $path, $completePayload, $machine)->assertOk()->assertJsonPath('status', 'completed');
        $this->signedJson('POST', $path, $completePayload, $machine)->assertOk()->assertJsonPath('status', 'completed');

        $job->refresh();
        $this->assertSame(LenticularJobStatus::Completed, $job->status);
        $this->assertSame(100, $job->progress);
        $this->assertDatabaseHas('lenticular_artifacts', [
            'lenticular_job_id' => $job->id,
            'sha256' => hash('sha256', $artifact),
            'size_bytes' => strlen($artifact),
        ]);
        $this->assertDatabaseHas('lenticular_job_events', [
            'lenticular_job_id' => $job->id,
            'type' => 'completed',
        ]);
    }

    public function test_expired_lease_cannot_report_progress(): void
    {
        $machine = ProcessingMachine::factory()->create(['api_secret' => self::SECRET]);
        $job = LenticularJob::factory()->create([
            'processing_machine_id' => $machine->id,
            'status' => LenticularJobStatus::Leased,
            'lease_token' => str_repeat('l', 64),
            'lease_expires_at' => now()->subSecond(),
        ]);

        $this->signedJson('POST', "/api/worker/v1/jobs/{$job->id}/progress", [
            'lease_token' => str_repeat('l', 64),
            'percent' => 50,
            'stage' => 'extracting_frames',
        ], $machine)->assertConflict();

        $this->assertSame(LenticularJobStatus::Leased, $job->fresh()->status);
    }

    public function test_expired_processing_job_can_be_claimed_again(): void
    {
        Storage::fake('local');
        [$machine, $job] = $this->queuedJob('video-content');
        $job->update([
            'processing_machine_id' => $machine->id,
            'status' => LenticularJobStatus::Processing,
            'lease_token' => str_repeat('o', 64),
            'lease_expires_at' => now()->subSecond(),
        ]);

        $response = $this->signedJson('POST', '/api/worker/v1/jobs/claim', [
            'lease_seconds' => 120,
            'capabilities' => ['extract_video_frames:v1'],
        ], $machine);

        $response->assertOk()->assertJsonPath('job_id', $job->id);
        $this->assertSame(1, $job->fresh()->attempts);
        $this->assertNotSame(str_repeat('o', 64), $job->fresh()->lease_token);
    }

    public function test_analysis_completion_stores_metadata_and_three_previews(): void
    {
        Storage::fake('local');
        [$machine, $job] = $this->queuedJob('video-content');
        $job->update(['operation' => 'analyze_video', 'parameters' => []]);
        $claim = $this->signedJson('POST', '/api/worker/v1/jobs/claim', ['lease_seconds' => 120, 'capabilities' => ['analyze_video:v1']], $machine);
        $artifact = 'analysis-zip';
        $this->call('PUT', $this->localUrl($claim->json('upload_url')), [], [], [], ['CONTENT_TYPE' => 'application/zip'], $artifact)->assertCreated();
        $preview = base64_encode("\xFF\xD8\xFFpreview");
        $payload = [
            'lease_token' => $claim->json('lease_token'),
            'artifact' => ['sha256' => hash('sha256', $artifact), 'size_bytes' => strlen($artifact), 'media_type' => 'application/zip'],
            'result' => [
                'video' => ['width' => 1178, 'height' => 786, 'frame_count' => 97, 'fps' => 24, 'duration_seconds' => 4.041667],
                'thumbnails' => [$preview, $preview, $preview],
                'timeline' => [['frame_index' => 0, 'image' => $preview], ['frame_index' => 96, 'image' => $preview]],
            ],
        ];

        $this->signedJson('POST', "/api/worker/v1/jobs/{$job->id}/complete", $payload, $machine)->assertOk();

        $this->assertSame(97, $job->sourceFile->fresh()->metadata['frame_count']);
        $this->assertSame(3, LenticularProjectFile::query()->where('lenticular_project_id', $job->lenticular_project_id)->where('kind', 'like', 'analysis_thumbnail_%')->count());
        $this->assertSame(2, LenticularProjectFile::query()->where('lenticular_project_id', $job->lenticular_project_id)->where('kind', 'like', 'timeline_thumbnail_%')->count());
    }

    public function test_alignment_completion_stores_transforms_and_previews(): void
    {
        Storage::fake('local');
        [$machine, $job] = $this->queuedJob('video-content');
        $job->update([
            'operation' => 'align_sequence',
            'parameters' => [
                'selection' => ['start' => 0, 'end' => 12, 'step' => 2, 'jpeg_quality' => 95],
                'alignment' => ['z_center' => 0.5, 'z_width' => 0.05, 'alignment_y' => 0.5],
            ],
        ]);
        $claim = $this->signedJson('POST', '/api/worker/v1/jobs/claim', ['lease_seconds' => 120, 'capabilities' => ['align_sequence:v1']], $machine);
        $claim->assertOk()->assertJsonPath('alignment.z_width', 0.05);
        $artifact = 'aligned-zip';
        $this->call('PUT', $this->localUrl($claim->json('upload_url')), [], [], [], ['CONTENT_TYPE' => 'application/zip'], $artifact)->assertCreated();
        $preview = base64_encode("\xFF\xD8\xFFpreview");
        $payload = [
            'lease_token' => $claim->json('lease_token'),
            'artifact' => ['sha256' => hash('sha256', $artifact), 'size_bytes' => strlen($artifact), 'media_type' => 'application/zip'],
            'result' => [
                'alignment' => [
                    'crop' => [2, 3, 1170, 780],
                    'transforms' => [
                        ['filename' => 'frame_000001.jpg', 'x' => 0, 'y' => 0, 'score' => 1],
                        ['filename' => 'frame_000002.jpg', 'x' => -2.5, 'y' => 1.25, 'score' => 0.9],
                    ],
                ],
                'previews' => [$preview, $preview],
                'animation_frames' => [$preview, $preview],
            ],
        ];

        $this->signedJson('POST', "/api/worker/v1/jobs/{$job->id}/complete", $payload, $machine)->assertOk();

        $this->assertSame(1170, $job->lenticularProject->fresh()->settings['alignment']['crop'][2]);
        $this->assertSame(2, LenticularProjectFile::query()->where('lenticular_project_id', $job->lenticular_project_id)->where('kind', 'like', 'alignment_preview_%')->count());
        $this->assertSame(2, LenticularProjectFile::query()->where('lenticular_project_id', $job->lenticular_project_id)->where('kind', 'like', 'alignment_frame_%')->count());
    }

    public function test_finalization_completion_stores_metadata_and_previews(): void
    {
        Storage::fake('local');
        [$machine, $job] = $this->queuedJob('video-content');
        $job->update(['operation' => 'finalize_sequence', 'parameters' => ['selection' => ['start' => 0, 'end' => 2, 'step' => 1, 'jpeg_quality' => 95], 'alignment' => ['z_center' => 0.5], 'finalization' => ['crop' => ['x' => 0, 'y' => 0, 'width' => 1, 'height' => 1], 'reverse' => true, 'basename' => 'Projekt']]]);
        $claim = $this->signedJson('POST', '/api/worker/v1/jobs/claim', ['lease_seconds' => 120, 'capabilities' => ['finalize_sequence:v1']], $machine);
        $claim->assertOk()->assertJsonPath('finalization.reverse', true);
        $artifact = 'final-zip';
        $this->call('PUT', $this->localUrl($claim->json('upload_url')), [], [], [], ['CONTENT_TYPE' => 'application/zip'], $artifact)->assertCreated();
        $preview = base64_encode("\xFF\xD8\xFFpreview");
        $payload = ['lease_token' => $claim->json('lease_token'), 'artifact' => ['sha256' => hash('sha256', $artifact), 'size_bytes' => strlen($artifact), 'media_type' => 'application/zip'], 'result' => ['finalization' => ['frame_count' => 2, 'width' => 800, 'height' => 600], 'previews' => [$preview, $preview]]];

        $this->signedJson('POST', "/api/worker/v1/jobs/{$job->id}/complete", $payload, $machine)->assertOk();

        $this->assertSame(800, $job->lenticularProject->fresh()->settings['finalization']['width']);
        $this->assertSame(2, LenticularProjectFile::query()->where('lenticular_project_id', $job->lenticular_project_id)->where('kind', 'like', 'final_preview_%')->count());
    }

    /** @return array{ProcessingMachine, LenticularJob} */
    private function queuedJob(string $contents): array
    {
        $machine = ProcessingMachine::factory()->create(['api_secret' => self::SECRET]);
        $project = LenticularProject::factory()->create();
        $path = "lenticular/sources/{$project->id}/source.mp4";
        Storage::disk('local')->put($path, $contents);
        $file = LenticularProjectFile::factory()->create([
            'lenticular_project_id' => $project->id,
            'path' => $path,
            'size_bytes' => strlen($contents),
            'sha256' => hash('sha256', $contents),
        ]);
        $job = LenticularJob::factory()->create([
            'lenticular_project_id' => $project->id,
            'source_file_id' => $file->id,
        ]);

        return [$machine, $job];
    }

    private function signedJson(string $method, string $path, array $payload, ProcessingMachine $machine, ?string $nonce = null): TestResponse
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $timestamp = (string) now()->timestamp;
        $nonce ??= fake()->unique()->regexify('[a-z0-9]{32}');
        $contentHash = hash('sha256', $body);
        $canonical = implode("\n", [$method, $path, $timestamp, $nonce, $contentHash]);

        return $this->call($method, $path, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_MACHINE_ID' => $machine->machine_id,
            'HTTP_X_API_KEY_ID' => $machine->api_key_id,
            'HTTP_X_TIMESTAMP' => $timestamp,
            'HTTP_X_NONCE' => $nonce,
            'HTTP_X_CONTENT_SHA256' => $contentHash,
            'HTTP_X_SIGNATURE' => hash_hmac('sha256', $canonical, self::SECRET),
        ], $body);
    }

    private function localUrl(string $url): string
    {
        $parts = parse_url($url);

        return $parts['path'].(isset($parts['query']) ? '?'.$parts['query'] : '');
    }
}
