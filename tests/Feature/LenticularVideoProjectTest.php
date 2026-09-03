<?php

namespace Tests\Feature;

use App\Enums\LenticularJobStatus;
use App\Models\LenticularJob;
use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LenticularVideoProjectTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_video_project_creator(): void
    {
        $this->get('/pl/lab/lenticular/projects/create')->assertRedirect('/pl/login');
    }

    public function test_user_creates_project_with_print_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/pl/lab/lenticular/projects', [
            'name' => 'Pierwszy projekt',
            'print_size' => 'A4', 'printer_dpi' => 1200, 'lpi' => 60,
        ]);

        $project = LenticularProject::query()->sole();
        $response->assertRedirect(route('lab.projects.show', ['locale' => 'pl', 'project' => $project]));
        $this->assertSame($user->id, $project->user_id);
        $this->assertSame(20, $project->settings['max_frames']);
        $this->assertSame(0, LenticularJob::query()->count());
    }

    public function test_user_uploads_video_and_analysis_job_is_queued(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $project = LenticularProject::factory()->for($user)->create();
        $video = UploadedFile::fake()->create('lenticular_test.mp4', 100, 'video/mp4');

        $this->actingAs($user)->post("/pl/lab/lenticular/projects/{$project->id}/video", ['video' => $video])->assertRedirect();

        $this->assertDatabaseHas('lenticular_jobs', ['lenticular_project_id' => $project->id, 'operation' => 'analyze_video', 'status' => LenticularJobStatus::Queued->value]);
        $source = LenticularProjectFile::query()->where('kind', 'source_video')->sole();
        Storage::disk('local')->assertExists($source->path);
    }

    public function test_video_larger_than_one_hundred_megabytes_is_rejected(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $path = tempnam(sys_get_temp_dir(), 'large-video-');
        $handle = fopen($path, 'wb');
        ftruncate($handle, (102400 * 1024) + 1);
        fclose($handle);
        $video = new UploadedFile($path, 'too-large.mp4', 'video/mp4', null, true);

        $project = LenticularProject::factory()->for($user)->create();
        $this->actingAs($user)->post("/pl/lab/lenticular/projects/{$project->id}/video", [
            'video' => $video,
        ])->assertSessionHasErrors('video');

        $this->assertSame(0, LenticularProjectFile::query()->count());
    }

    public function test_user_cannot_view_another_users_project(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = LenticularProject::factory()->for($owner)->create();

        $this->actingAs($otherUser)->get("/pl/lab/lenticular/projects/{$project->id}")->assertNotFound();
    }

    public function test_step_two_upload_view_renders(): void
    {
        $user = User::factory()->create();
        $project = LenticularProject::factory()->for($user)->create(['settings' => ['print_size' => 'A4', 'dpi' => 1200, 'lpi' => 60, 'max_frames' => 20]]);

        $this->actingAs($user)->get("/pl/lab/lenticular/projects/{$project->id}")
            ->assertOk()
            ->assertSee(__('lenticular_projects.upload_video'));
    }

    public function test_step_two_timeline_view_renders(): void
    {
        $user = User::factory()->create();
        $project = LenticularProject::factory()->for($user)->create(['settings' => ['print_size' => 'A4', 'dpi' => 1200, 'lpi' => 60, 'max_frames' => 20]]);
        LenticularProjectFile::factory()->create(['lenticular_project_id' => $project->id, 'metadata' => ['width' => 1178, 'height' => 786, 'frame_count' => 97, 'fps' => 24, 'duration_seconds' => 4.04]]);

        $this->actingAs($user)->get("/pl/lab/lenticular/projects/{$project->id}")
            ->assertOk()
            ->assertSee(__('lenticular_projects.select_range'));
    }

    public function test_step_three_alignment_view_renders(): void
    {
        $user = User::factory()->create();
        $project = LenticularProject::factory()->for($user)->create(['settings' => ['print_size' => 'A4', 'dpi' => 1200, 'lpi' => 60, 'max_frames' => 20, 'selection' => ['start' => 0, 'end' => 19, 'step' => 1, 'jpeg_quality' => 95]]]);
        $source = LenticularProjectFile::factory()->create(['lenticular_project_id' => $project->id, 'metadata' => ['width' => 1178, 'height' => 786, 'frame_count' => 97, 'fps' => 24, 'duration_seconds' => 4.04]]);
        LenticularJob::factory()->create(['lenticular_project_id' => $project->id, 'source_file_id' => $source->id, 'operation' => 'extract_video_frames', 'status' => LenticularJobStatus::Completed]);

        $this->actingAs($user)->get("/pl/lab/lenticular/projects/{$project->id}")
            ->assertOk()
            ->assertSee(__('lenticular_projects.alignment_help'));
    }

    public function test_analyzed_project_accepts_valid_frame_range(): void
    {
        $user = User::factory()->create();
        $project = LenticularProject::factory()->for($user)->create(['settings' => ['max_frames' => 20]]);
        LenticularProjectFile::factory()->create([
            'lenticular_project_id' => $project->id,
            'metadata' => ['width' => 1178, 'height' => 786, 'frame_count' => 97, 'fps' => 24, 'duration_seconds' => 4.041667],
        ]);

        $this->actingAs($user)->post("/pl/lab/lenticular/projects/{$project->id}/frames", [
            'start' => '4',
            'end' => '40',
            'step' => '2',
            'jpeg_quality' => '95',
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('lenticular_jobs', ['lenticular_project_id' => $project->id, 'operation' => 'extract_video_frames']);
        $job = $project->jobs()->where('operation', 'extract_video_frames')->sole();
        $this->assertSame(['selection' => ['start' => 4, 'end' => 40, 'step' => 2, 'jpeg_quality' => 95]], $job->parameters);
    }

    public function test_alignment_is_unlocked_after_frame_extraction(): void
    {
        $user = User::factory()->create();
        $selection = ['start' => 4, 'end' => 40, 'step' => 2, 'jpeg_quality' => 95];
        $project = LenticularProject::factory()->for($user)->create(['settings' => ['selection' => $selection]]);
        $source = LenticularProjectFile::factory()->create(['lenticular_project_id' => $project->id]);
        LenticularJob::factory()->create(['lenticular_project_id' => $project->id, 'source_file_id' => $source->id, 'operation' => 'extract_video_frames', 'status' => LenticularJobStatus::Completed]);

        $this->actingAs($user)->post("/pl/lab/lenticular/projects/{$project->id}/alignment", ['z_center' => '0.4', 'z_width' => '0.05', 'alignment_y' => '0.6'])->assertSessionHas('status');

        $job = $project->jobs()->where('operation', 'align_sequence')->sole();
        $this->assertSame(['selection' => $selection, 'alignment' => ['z_center' => 0.4, 'z_width' => 0.05, 'alignment_y' => 0.6]], $job->parameters);
    }
}
