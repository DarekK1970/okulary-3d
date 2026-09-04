<?php

namespace Tests\Feature;

use App\Enums\FalAiJobOperation;
use App\Enums\FalAiJobStatus;
use App\Models\FalAiJob;
use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use App\Services\FalAiJobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class FalAiJobServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_persistent_job_with_a_history_event(): void
    {
        $project = LenticularProject::factory()->create();
        $source = LenticularProjectFile::factory()->for($project)->create();

        $job = app(FalAiJobService::class)->create(
            $project,
            FalAiJobOperation::ImageToVideo,
            'bytedance/seedance-2.5/image-to-video',
            ['resolution' => '720p', 'duration' => 4],
            (string) Str::uuid(),
            $source,
            estimatedCostUsd: 0.48,
        );

        $this->assertSame(FalAiJobStatus::Queued, $job->status);
        $this->assertSame($project->user_id, $job->user_id);
        $this->assertSame($source->id, $job->source_file_id);
        $this->assertSame('0.480000', $job->estimated_cost_usd);
        $this->assertSame('created', $job->events()->sole()->type);
    }

    public function test_idempotency_key_returns_the_original_job(): void
    {
        $project = LenticularProject::factory()->create();
        $key = (string) Str::uuid();
        $service = app(FalAiJobService::class);

        $first = $service->create($project, FalAiJobOperation::ImageToVideo, 'model', ['duration' => 4], $key);
        $second = $service->create($project, FalAiJobOperation::ImageToVideo, 'model', ['duration' => 8], $key);

        $this->assertTrue($first->is($second));
        $this->assertSame(['duration' => 4], $second->parameters);
        $this->assertSame(1, FalAiJob::query()->count());
    }

    public function test_files_from_another_project_are_rejected(): void
    {
        $project = LenticularProject::factory()->create();
        $foreignFile = LenticularProjectFile::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        app(FalAiJobService::class)->create(
            $project,
            FalAiJobOperation::ImageToVideo,
            'model',
            [],
            (string) Str::uuid(),
            $foreignFile,
        );
    }

    public function test_job_lifecycle_records_provider_ids_costs_and_events(): void
    {
        $project = LenticularProject::factory()->create();
        $result = LenticularProjectFile::factory()->for($project)->create(['kind' => 'generated_video']);
        $service = app(FalAiJobService::class);
        $job = $service->create($project, FalAiJobOperation::ImageToVideo, 'model', [], (string) Str::uuid());

        $job = $service->markSubmitted($job, 'fal-request-123', ['status' => 'IN_QUEUE']);
        $job = $service->markProcessing($job, 45, 'rendering');
        $job = $service->markSucceeded($job, $result, 0.725, ['status' => 'COMPLETED']);

        $this->assertSame(FalAiJobStatus::Succeeded, $job->status);
        $this->assertSame('fal-request-123', $job->provider_request_id);
        $this->assertSame($result->id, $job->result_file_id);
        $this->assertSame('0.725000', $job->actual_cost_usd);
        $this->assertSame(100, $job->progress);
        $this->assertNotNull($job->completed_at);
        $this->assertCount(4, $job->events);
    }

    public function test_terminal_job_cannot_be_changed(): void
    {
        $job = FalAiJob::factory()->create(['status' => FalAiJobStatus::Failed]);

        $this->expectException(LogicException::class);
        app(FalAiJobService::class)->markSubmitted($job, 'late-request');
    }
}
