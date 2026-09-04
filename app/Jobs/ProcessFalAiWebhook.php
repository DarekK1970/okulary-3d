<?php

namespace App\Jobs;

use App\Enums\FalAiJobOperation;
use App\Enums\FalAiJobStatus;
use App\Enums\LenticularJobStatus;
use App\Models\FalAiJob;
use App\Models\LenticularJob;
use App\Services\FalAiJobService;
use App\Services\FalAiResultService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

class ProcessFalAiWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [10, 30, 120, 300];

    /** @param array<string, mixed> $payload */
    public function __construct(public readonly array $payload) {}

    public function handle(FalAiJobService $jobs, FalAiResultService $results): void
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
            $jobs->markSucceeded($job, $resultFile, response: (array) ($this->payload['payload'] ?? []));
            if ($job->operation === FalAiJobOperation::ImageToVideo) {
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
}
