<?php

namespace Tests\Feature;

use App\Enums\FalAiJobStatus;
use App\Jobs\SubmitFalAiJob;
use App\Models\FalAiJob;
use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use App\Models\User;
use App\Services\FalAiSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiLenticularPairProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_pro_or_premium_user_can_open_two_photo_workflow(): void
    {
        $regularUser = User::factory()->create();
        $premiumUser = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $proUser = User::factory()->create(['lenticular_plan' => 'pro']);

        $this->actingAs($regularUser)->get('/pl/lab/lenticular/studio/two-photos')->assertForbidden();
        $this->actingAs($proUser)->get('/pl/lab/lenticular/studio/two-photos')->assertOk()->assertDontSee('A3');
        $this->actingAs($premiumUser)->get('/pl/lab/lenticular/studio/two-photos')
            ->assertOk()
            ->assertSee('Utwórz ruch kamery między zdjęciami')
            ->assertDontSee('5,00 USD')
            ->assertDontSee('Akceptuję uruchomienie zadania')
            ->assertDontSee('Seedance')
            ->assertDontSee('fal.ai');
    }

    public function test_confirmed_pair_creates_project_files_and_queued_ai_job(): void
    {
        Storage::fake('local');
        Queue::fake();
        $this->configureFal();
        $premiumUser = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $response = $this->actingAs($premiumUser)->post('/pl/lab/lenticular/studio/two-photos', [
            'name' => 'Tramwaj 3D',
            'start_image' => UploadedFile::fake()->image('start.jpg', 1280, 720),
            'end_image' => UploadedFile::fake()->image('end.jpg', 1280, 720),
            'print_size' => 'A4',
            'lpi' => 60,
        ]);

        $project = LenticularProject::query()->sole();
        $job = FalAiJob::query()->sole();
        $response->assertRedirect(route('lab.lenticular.ai.jobs.show', ['locale' => 'pl', 'job' => $job]));
        $this->assertSame($premiumUser->id, $project->user_id);
        $this->assertSame('ai_pair', $project->settings['workflow']);
        $this->assertSame('A4', $project->settings['print_size']);
        $this->assertSame(2, LenticularProjectFile::query()->count());
        $this->assertSame(FalAiJobStatus::Queued, $job->status);
        $this->assertFalse($job->parameters['generate_audio']);
        $this->assertSame($project->id, $job->lenticular_project_id);
        $this->assertNotNull($job->source_file_id);
        $this->assertNotNull($job->end_file_id);
        Queue::assertPushed(SubmitFalAiJob::class, fn (SubmitFalAiJob $queued): bool => $queued->falAiJobId === $job->id);

        LenticularProjectFile::query()->each(fn (LenticularProjectFile $file) => Storage::disk('local')->assertExists($file->path));
    }

    public function test_pair_job_can_be_created_without_separate_usd_cost_confirmation(): void
    {
        Storage::fake('local');
        Queue::fake();
        $this->configureFal();
        $premiumUser = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($premiumUser)->from('/pl/lab/lenticular/studio/two-photos')->post('/pl/lab/lenticular/studio/two-photos', [
            'name' => 'Projekt w planie płatnym',
            'start_image' => UploadedFile::fake()->image('start.jpg'),
            'end_image' => UploadedFile::fake()->image('end.jpg'),
            'print_size' => 'A4',
            'lpi' => 60,
        ])->assertRedirect();

        $this->assertDatabaseCount('lenticular_projects', 1);
        $this->assertDatabaseCount('fal_ai_jobs', 1);
        Queue::assertPushed(SubmitFalAiJob::class);
    }

    public function test_daily_budget_is_checked_before_project_is_created(): void
    {
        Storage::fake('local');
        Queue::fake();
        $this->configureFal();
        app(FalAiSettingsService::class)->set('daily_budget_usd', '5.00');
        FalAiJob::factory()->create(['estimated_cost_usd' => 5, 'status' => FalAiJobStatus::Queued]);
        $existingProjects = LenticularProject::query()->count();
        $premiumUser = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($premiumUser)->post('/pl/lab/lenticular/studio/two-photos', [
            'name' => 'Ponad budżet',
            'start_image' => UploadedFile::fake()->image('start.jpg'),
            'end_image' => UploadedFile::fake()->image('end.jpg'),
            'print_size' => 'A4',
            'lpi' => 60,
        ])->assertTooManyRequests();

        $this->assertSame($existingProjects, LenticularProject::query()->count());
        Queue::assertNothingPushed();
    }

    public function test_user_cannot_view_another_users_ai_job(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $otherUser = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $project = LenticularProject::factory()->for($owner)->create();
        $job = FalAiJob::factory()->for($project)->create(['user_id' => $owner->id]);

        $this->actingAs($otherUser)->get("/pl/lab/lenticular/studio/jobs/{$job->id}")->assertNotFound();
        $this->actingAs($owner)->get("/pl/lab/lenticular/studio/jobs/{$job->id}")->assertOk();
    }

    private function configureFal(): void
    {
        $settings = app(FalAiSettingsService::class);
        $settings->set('enabled', '1');
        $settings->set('api_key', 'test-secret', true);
        $settings->set('maximum_job_cost_usd', '5.00');
        $settings->set('daily_budget_usd', '50.00');
    }
}
