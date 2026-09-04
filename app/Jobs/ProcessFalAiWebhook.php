<?php

namespace App\Jobs;

use App\Enums\FalAiJobOperation;
use App\Enums\FalAiJobStatus;
use App\Enums\LenticularJobStatus;
use App\Models\FalAiJob;
use App\Models\LenticularJob;
use App\Models\LenticularProjectFile;
use App\Services\FalAiJobService;
use App\Services\FalAiResultService;
use App\Services\FalAiSettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProcessFalAiWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [10, 30, 120, 300];

    /** @param array<string, mixed> $payload */
    public function __construct(public readonly array $payload) {}

    public function handle(FalAiJobService $jobs, FalAiResultService $results, FalAiSettingsService $settings): void
    {
        $job = FalAiJob::query()->where('provider_request_id', $this->payload['request_id'])->first();
        if (! $job) {
            throw new RuntimeException('Unknown fal.ai request ID.');
        }
        if ($job->status->isTerminal()) {
            return;
        }
        if ($this->payload['status'] === 'ERROR') {
            $jobs->markFailed($job, 'provider_error', (string) ($this->payload['error'] ?? 'fal.ai returned an error.'));

            return;
        }

        $claimed = FalAiJob::query()->whereKey($job->id)->whereNull('result_claimed_at')->update(['result_claimed_at' => now()]);
        if ($claimed !== 1) {
            return;
        }

        try {
            $resultFile = $results->store($job, (array) ($this->payload['payload'] ?? []));
            $upscale = $this->requiresUpscaling($job, $settings)
                ? $this->prepareUpscaling($job, $resultFile, $jobs, $settings)
                : null;
            $jobs->markSucceeded($job, $resultFile, response: (array) ($this->payload['payload'] ?? []));
            if ($upscale) {
                $jobs->queueForSubmission($upscale);
            } elseif (in_array($job->operation, [FalAiJobOperation::ImageToVideo, FalAiJobOperation::VideoUpscale], true)) {
                LenticularJob::query()->firstOrCreate(
                    ['source_file_id' => $resultFile->id, 'operation' => 'analyze_video'],
                    ['lenticular_project_id' => $job->lenticular_project_id, 'status' => LenticularJobStatus::Queued, 'parameters' => []],
                );
            }
        } catch (Throwable $exception) {
            FalAiJob::query()->whereKey($job->id)
                ->whereNotIn('status', [FalAiJobStatus::Succeeded, FalAiJobStatus::Failed, FalAiJobStatus::Cancelled])
                ->update(['result_claimed_at' => null]);
            throw $exception;
        }
    }

    private function requiresUpscaling(FalAiJob $job, FalAiSettingsService $settings): bool
    {
        return $job->operation === FalAiJobOperation::ImageToVideo
            && $settings->upscalingEnabled()
            && data_get($job->lenticularProject->settings, 'print_size') === 'A3';
    }

    private function prepareUpscaling(
        FalAiJob $sourceJob,
        LenticularProjectFile $sourceFile,
        FalAiJobService $jobs,
        FalAiSettingsService $settings,
    ): FalAiJob {
        $existing = FalAiJob::query()
            ->where('lenticular_project_id', $sourceJob->lenticular_project_id)
            ->where('operation', FalAiJobOperation::VideoUpscale)
            ->first();
        if ($existing) {
            return $existing;
        }

        $reservedToday = (float) FalAiJob::query()
            ->whereDate('created_at', today())
            ->whereNotIn('status', [FalAiJobStatus::Failed, FalAiJobStatus::Cancelled])
            ->sum(DB::raw('COALESCE(actual_cost_usd, estimated_cost_usd, 0)'));

        if ($reservedToday + $settings->maximumJobCost() > $settings->dailyBudget()) {
            throw new RuntimeException('Daily AI budget does not allow the required upscale step.');
        }

        return $jobs->create(
            $sourceJob->lenticularProject,
            FalAiJobOperation::VideoUpscale,
            $settings->upscalerModel(),
            [
                'target_resolution' => $settings->upscaleResolution(),
                'target_fps' => 30,
                'enhancement_preset' => 'aigc',
                'enhancement_tier' => 'standard',
                'fidelity' => 'high',
                'bit_depth' => 8,
            ],
            (string) Str::uuid(),
            $sourceFile,
            estimatedCostUsd: $settings->maximumJobCost(),
        );
    }
}
