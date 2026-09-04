<?php

namespace App\Jobs;

use App\Enums\FalAiJobStatus;
use App\Models\FalAiJob;
use App\Services\FalAiJobService;
use App\Services\LenticularPromptService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class PrepareSinglePhotoLenticularJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public array $backoff = [30, 120];

    public function __construct(public readonly string $falAiJobId) {}

    public function handle(LenticularPromptService $prompts, FalAiJobService $jobs): void
    {
        $job = FalAiJob::query()->with('sourceFile')->findOrFail($this->falAiJobId);
        if ($job->status !== FalAiJobStatus::Queued || filled($job->provider_request_id)) {
            return;
        }

        if (blank($job->parameters['prompt'] ?? null)) {
            $result = $prompts->build($job->sourceFile);
            $job->update(['parameters' => [...$job->parameters, 'prompt' => $result['prompt'], 'scene_analysis' => $result['analysis'], 'analysis_usage' => $result['usage']]]);
        }
        $jobs->queueForSubmission($job->fresh());
    }

    public function failed(?Throwable $exception): void
    {
        $job = FalAiJob::query()->find($this->falAiJobId);
        if ($job?->status === FalAiJobStatus::Queued) {
            app(FalAiJobService::class)->markFailed($job, 'scene_analysis_failed', $exception?->getMessage() ?? 'Unknown scene analysis failure.');
        }
    }
}
