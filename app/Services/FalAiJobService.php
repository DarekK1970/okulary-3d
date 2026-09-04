<?php

namespace App\Services;

use App\Enums\FalAiJobOperation;
use App\Enums\FalAiJobStatus;
use App\Jobs\SubmitFalAiJob;
use App\Models\FalAiJob;
use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class FalAiJobService
{
    public function queueForSubmission(FalAiJob $job): void
    {
        if ($job->status !== FalAiJobStatus::Queued || filled($job->provider_request_id)) {
            throw new LogicException('Only a new queued fal.ai job can be submitted.');
        }

        SubmitFalAiJob::dispatch($job->id);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function create(
        LenticularProject $project,
        FalAiJobOperation $operation,
        string $endpoint,
        array $parameters,
        string $idempotencyKey,
        ?LenticularProjectFile $sourceFile = null,
        ?LenticularProjectFile $endFile = null,
        ?float $estimatedCostUsd = null,
    ): FalAiJob {
        if (! Str::isUuid($idempotencyKey)) {
            throw new InvalidArgumentException('The fal.ai idempotency key must be a UUID.');
        }

        $this->assertFileBelongsToProject($project, $sourceFile);
        $this->assertFileBelongsToProject($project, $endFile);

        return DB::transaction(function () use ($project, $operation, $endpoint, $parameters, $idempotencyKey, $sourceFile, $endFile, $estimatedCostUsd): FalAiJob {
            LenticularProject::query()->whereKey($project->getKey())->lockForUpdate()->firstOrFail();

            $existing = FalAiJob::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                if ($existing->lenticular_project_id !== $project->getKey()) {
                    throw new LogicException('The fal.ai idempotency key belongs to another project.');
                }

                return $existing;
            }

            $job = FalAiJob::query()->create([
                'user_id' => $project->user_id,
                'lenticular_project_id' => $project->getKey(),
                'source_file_id' => $sourceFile?->getKey(),
                'end_file_id' => $endFile?->getKey(),
                'operation' => $operation,
                'status' => FalAiJobStatus::Queued,
                'idempotency_key' => $idempotencyKey,
                'endpoint' => trim($endpoint),
                'parameters' => $parameters,
                'estimated_cost_usd' => $estimatedCostUsd,
            ]);
            $job->events()->create(['type' => 'created', 'payload' => ['status' => FalAiJobStatus::Queued->value]]);

            return $job;
        });
    }

    public function markSubmitted(FalAiJob $job, string $providerRequestId, ?array $response = null): FalAiJob
    {
        return $this->transition($job, FalAiJobStatus::Submitted, [
            'provider_request_id' => $providerRequestId,
            'provider_response' => $response,
            'submitted_at' => now(),
            'stage' => 'submitted',
        ]);
    }

    public function markProcessing(FalAiJob $job, int $progress = 0, ?string $stage = null): FalAiJob
    {
        return $this->transition($job, FalAiJobStatus::Processing, [
            'progress' => max(0, min(99, $progress)),
            'stage' => $stage ?? 'processing',
            'started_at' => $job->started_at ?? now(),
        ]);
    }

    public function markSucceeded(
        FalAiJob $job,
        LenticularProjectFile $resultFile,
        ?float $actualCostUsd = null,
        ?array $response = null,
    ): FalAiJob {
        $this->assertFileBelongsToProject($job->lenticularProject, $resultFile);

        return $this->transition($job, FalAiJobStatus::Succeeded, [
            'result_file_id' => $resultFile->getKey(),
            'actual_cost_usd' => $actualCostUsd,
            'provider_response' => $response ?? $job->provider_response,
            'progress' => 100,
            'stage' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markFailed(FalAiJob $job, string $errorCode, string $errorMessage): FalAiJob
    {
        return $this->transition($job, FalAiJobStatus::Failed, [
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'stage' => 'failed',
            'completed_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function transition(FalAiJob $job, FalAiJobStatus $status, array $attributes): FalAiJob
    {
        return DB::transaction(function () use ($job, $status, $attributes): FalAiJob {
            $locked = FalAiJob::query()->whereKey($job->getKey())->lockForUpdate()->firstOrFail();
            if (! $locked->status->canTransitionTo($status)) {
                throw new LogicException("Invalid fal.ai job transition: {$locked->status->value} -> {$status->value}.");
            }

            $from = $locked->status;
            $locked->update([...$attributes, 'status' => $status]);
            $locked->events()->create([
                'type' => 'status_changed',
                'payload' => ['from' => $from->value, 'to' => $status->value, 'stage' => $locked->stage],
            ]);

            return $locked->fresh();
        });
    }

    private function assertFileBelongsToProject(LenticularProject $project, ?LenticularProjectFile $file): void
    {
        if ($file && $file->lenticular_project_id !== $project->getKey()) {
            throw new InvalidArgumentException('The fal.ai job file must belong to the selected project.');
        }
    }
}
