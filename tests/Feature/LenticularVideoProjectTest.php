<?php

namespace Tests\Feature;

use App\Enums\LenticularJobStatus;
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

    public function test_user_uploads_video_and_analysis_job_is_queued(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $video = UploadedFile::fake()->create('lenticular_test.mp4', 100, 'video/mp4');

        $response = $this->actingAs($user)->post('/pl/lab/lenticular/projects', [
            'name' => 'Pierwszy projekt',
            'video' => $video,
        ]);

        $project = LenticularProject::query()->sole();
        $response->assertRedirect(route('lab.projects.show', ['locale' => 'pl', 'project' => $project]));
        $this->assertSame($user->id, $project->user_id);
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

        $this->actingAs($user)->post('/pl/lab/lenticular/projects', [
            'name' => 'Za duży projekt',
            'video' => $video,
        ])->assertSessionHasErrors('video');

        $this->assertSame(0, LenticularProject::query()->count());
    }

    public function test_user_cannot_view_another_users_project(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = LenticularProject::factory()->for($owner)->create();

        $this->actingAs($otherUser)->get("/pl/lab/lenticular/projects/{$project->id}")->assertNotFound();
    }

    public function test_analyzed_project_accepts_valid_frame_range(): void
    {
        $user = User::factory()->create();
        $project = LenticularProject::factory()->for($user)->create();
        LenticularProjectFile::factory()->create([
            'lenticular_project_id' => $project->id,
            'metadata' => ['width' => 1178, 'height' => 786, 'frame_count' => 97, 'fps' => 24, 'duration_seconds' => 4.041667],
        ]);

        $this->actingAs($user)->post("/pl/lab/lenticular/projects/{$project->id}/frames", [
            'start' => 4,
            'end' => 40,
            'step' => 2,
            'jpeg_quality' => 95,
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('lenticular_jobs', ['lenticular_project_id' => $project->id, 'operation' => 'extract_video_frames']);
        $job = $project->jobs()->where('operation', 'extract_video_frames')->sole();
        $this->assertSame(['selection' => ['start' => 4, 'end' => 40, 'step' => 2, 'jpeg_quality' => 95]], $job->parameters);
    }
}
