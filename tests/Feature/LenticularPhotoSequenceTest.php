<?php

namespace Tests\Feature;

use App\Enums\LenticularJobStatus;
use App\Models\LenticularProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LenticularPhotoSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_user_uploads_sequence_and_goes_directly_to_alignment(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/pl/lab/lenticular/studio/photo-sequence', [
            'name' => 'Sekwencja',
            'images' => [UploadedFile::fake()->image('001.jpg', 800, 600), UploadedFile::fake()->image('002.jpg', 800, 600), UploadedFile::fake()->image('003.jpg', 800, 600)],
            'print_size' => 'A5', 'printer_dpi' => 1200, 'lpi' => 60,
        ]);

        $project = LenticularProject::query()->sole();
        $response->assertRedirect(route('lab.projects.show', ['locale' => 'pl', 'project' => $project]));
        $this->assertSame('photo_sequence', $project->settings['workflow']);
        $this->assertDatabaseHas('lenticular_project_files', ['lenticular_project_id' => $project->id, 'kind' => 'source_sequence', 'media_type' => 'application/x-tar']);
        $this->assertDatabaseHas('lenticular_jobs', ['lenticular_project_id' => $project->id, 'operation' => 'import_sequence', 'status' => LenticularJobStatus::Completed->value]);
        $this->actingAs($user)->get(route('lab.projects.show', ['locale' => 'pl', 'project' => $project]))
            ->assertOk()->assertSee('Krok 3: Punkt Z i dopasowanie')->assertDontSee('Wczytaj video');

        $this->actingAs($user)->post(route('lab.projects.alignment.store', ['locale' => 'pl', 'project' => $project]), [
            'z_center' => 0.5, 'z_width' => 0.05, 'alignment_y' => 0.5,
        ])->assertRedirect();
        $this->assertDatabaseHas('lenticular_jobs', [
            'lenticular_project_id' => $project->id,
            'source_file_id' => $project->files()->where('kind', 'source_sequence')->sole()->id,
            'operation' => 'align_sequence',
            'status' => LenticularJobStatus::Queued->value,
        ]);
    }

    public function test_basic_plan_rejects_a4_and_more_than_twelve_images(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $images = array_map(fn (int $index) => UploadedFile::fake()->image("{$index}.jpg"), range(1, 13));

        $this->actingAs($user)->post('/pl/lab/lenticular/studio/photo-sequence', [
            'name' => 'Za duża', 'images' => $images, 'print_size' => 'A4', 'printer_dpi' => 1200, 'lpi' => 60,
        ])->assertSessionHasErrors(['images', 'print_size']);
        $this->assertDatabaseCount('lenticular_projects', 0);
    }

    public function test_sequence_requires_equal_image_dimensions(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/pl/lab/lenticular/studio/photo-sequence', [
            'name' => 'Różne',
            'images' => [UploadedFile::fake()->image('one.jpg', 800, 600), UploadedFile::fake()->image('two.jpg', 900, 600)],
            'print_size' => 'A5', 'printer_dpi' => 1200, 'lpi' => 60,
        ])->assertSessionHasErrors('images');
        $this->assertDatabaseCount('lenticular_projects', 0);
    }

    public function test_technical_print_limit_is_enforced(): void
    {
        Storage::fake('local');
        $premium = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $images = array_map(fn (int $index) => UploadedFile::fake()->image("{$index}.jpg"), range(1, 11));

        $this->actingAs($premium)->post('/pl/lab/lenticular/studio/photo-sequence', [
            'name' => 'Limit', 'images' => $images, 'print_size' => 'A3', 'printer_dpi' => 600, 'lpi' => 60,
        ])->assertUnprocessable();
        $this->assertDatabaseCount('lenticular_projects', 0);
    }
}
