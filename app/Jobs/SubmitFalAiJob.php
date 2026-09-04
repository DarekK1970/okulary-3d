<?php

namespace App\Jobs;

use App\Enums\FalAiJobOperation;
use App\Enums\FalAiJobStatus;
use App\Models\FalAiJob;
use App\Models\LenticularProjectFile;
use App\Services\FalAiClient;
use App\Services\FalAiJobService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\URL;
use Throwable;

class SubmitFalAiJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public readonly string $falAiJobId) {}

    public function handle(FalAiClient $client, FalAiJobService $jobs): void
    {
        $job = FalAiJob::query()->with(['sourceFile', 'endFile'])->findOrFail($this->falAiJobId);
        if ($job->status !== FalAiJobStatus::Queued || filled($job->provider_request_id)) {
            return;
        }

        $parameters = $job->parameters;
        unset($parameters['scene_analysis'], $parameters['analysis_usage']);
        if ($job->sourceFile) {
            $key = $job->operation === FalAiJobOperation::VideoUpscale ? 'video_url' : 'image_url';
            $parameters[$key] = $this->fileUrl($job->sourceFile);
        }
        if ($job->endFile) {
            $parameters['end_image_url'] = $this->fileUrl($job->endFile);
        }
        if ($job->user_id) {
            $parameters['end_user_id'] = hash_hmac('sha256', (string) $job->user_id, (string) config('app.key'));
        }

        $response = $client->submit($job->endpoint, $parameters, route('integrations.fal.webhook'));
        $jobs->markSubmitted($job, (string) $response['request_id'], $response);
    }

    public function failed(?Throwable $exception): void
    {
        $job = FalAiJob::query()->find($this->falAiJobId);
        if ($job?->status === FalAiJobStatus::Queued) {
            app(FalAiJobService::class)->markFailed($job, 'submission_failed', $exception?->getMessage() ?? 'Unknown submission failure.');
        }
    }

    private function fileUrl(LenticularProjectFile $file): string
    {
        return URL::temporarySignedRoute('integrations.fal.input', now()->addHours(2), ['file' => $file->id]);
    }
}
