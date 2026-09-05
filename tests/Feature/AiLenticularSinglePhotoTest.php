<?php

namespace Tests\Feature;

use App\Jobs\PrepareSinglePhotoLenticularJob;
use App\Jobs\SubmitFalAiJob;
use App\Models\FalAiJob;
use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use App\Models\User;
use App\Services\AiTranslationSettingsService;
use App\Services\FalAiJobService;
use App\Services\FalAiSettingsService;
use App\Services\LenticularPromptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiLenticularSinglePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_premium_user_can_create_single_photo_project_without_immediate_paid_call(): void
    {
        Storage::fake('local');
        Queue::fake();
        $this->configureServices();
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $response = $this->actingAs($user)->post('/pl/lab/lenticular/studio/one-photo', [
            'name' => 'Jedno zdjęcie',
            'source_image' => UploadedFile::fake()->image('scene.jpg', 1280, 720),
            'print_size' => 'A4',
            'printer_dpi' => 4000,
            'lpi' => 60,
        ]);

        $project = LenticularProject::query()->sole();
        $job = FalAiJob::query()->sole();
        $response->assertRedirect(route('lab.lenticular.ai.jobs.show', ['locale' => 'pl', 'job' => $job]));
        $this->assertSame('ai_single', $project->settings['workflow']);
        $this->assertSame(4000, $project->settings['dpi']);
        $this->assertSame(1, LenticularProjectFile::query()->count());
        $this->assertArrayNotHasKey('prompt', $job->parameters);
        Queue::assertPushed(PrepareSinglePhotoLenticularJob::class);
        Queue::assertNotPushed(SubmitFalAiJob::class);
    }

    public function test_scene_agent_builds_print_focused_prompt_and_then_queues_generation(): void
    {
        Storage::fake('local');
        Queue::fake();
        $this->configureServices();
        Http::fake(['https://api.openai.com/v1/responses' => Http::response($this->agentResponse())]);
        $project = LenticularProject::factory()->create();
        $path = UploadedFile::fake()->image('tram.jpg')->store("lenticular/sources/{$project->id}", 'local');
        $source = LenticularProjectFile::factory()->for($project)->create(['disk' => 'local', 'path' => $path, 'media_type' => 'image/jpeg']);
        $job = FalAiJob::factory()->for($project)->create(['source_file_id' => $source->id, 'parameters' => ['duration' => '4']]);

        (new PrepareSinglePhotoLenticularJob($job->id))->handle(app(LenticularPromptService::class), app(FalAiJobService::class));

        $parameters = $job->fresh()->parameters;
        $this->assertStringContainsString('yellow historic tram', $parameters['prompt']);
        $this->assertStringContainsString('10–12 degree', $parameters['prompt']);
        $this->assertStringContainsString('Only the camera moves', $parameters['prompt']);
        $this->assertSame('the man beside the tram', $parameters['scene_analysis']['pivot_description']);
        Queue::assertPushed(SubmitFalAiJob::class);
        Http::assertSent(fn ($request): bool => data_get($request->data(), 'input.0.content.1.type') === 'input_image'
            && str_starts_with(data_get($request->data(), 'input.0.content.1.image_url'), 'data:image/jpeg;base64,'));
    }

    public function test_single_photo_workflow_requires_both_services_and_paid_access(): void
    {
        $regular = User::factory()->create();
        $premium = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $pro = User::factory()->create(['lenticular_plan' => 'pro']);

        $this->actingAs($regular)->get('/pl/lab/lenticular/studio/one-photo')->assertForbidden();
        $this->actingAs($pro)->get('/pl/lab/lenticular/studio/one-photo')->assertOk()->assertDontSee('A3');
        $this->actingAs($premium)->get('/pl/lab/lenticular/studio/one-photo')
            ->assertOk()->assertSee('Zamień zdjęcie w przestrzenną sekwencję')->assertDontSee('Seedance')->assertDontSee('fal.ai');
    }

    /** @return array<string, mixed> */
    private function agentResponse(): array
    {
        return ['output' => [['content' => [['type' => 'output_text', 'text' => json_encode([
            'scene_description' => 'A yellow historic tram in a city street.',
            'pivot_description' => 'the man beside the tram',
            'preservation_details' => 'Tram markings, people, poles, wires and brick buildings.',
        ], JSON_THROW_ON_ERROR)]]]], 'usage' => ['input_tokens' => 100, 'output_tokens' => 50, 'total_tokens' => 150]];
    }

    private function configureServices(): void
    {
        $fal = app(FalAiSettingsService::class);
        $fal->set('enabled', '1');
        $fal->set('api_key', 'fal-test', true);
        $agent = app(AiTranslationSettingsService::class);
        $agent->set('enabled', '1');
        $agent->set('openai.api_key', 'openai-test', true);
        $agent->set('openai.model', 'vision-test');
    }
}
