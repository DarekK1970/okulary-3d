<?php

namespace Tests\Feature;

use App\Enums\LenticularJobStatus;
use App\Models\LenticularArtifact;
use App\Models\LenticularJob;
use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use App\Models\User;
use App\Services\LenticularProjectArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_lists_only_the_users_projects_and_final_downloads(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $project = LenticularProject::factory()->for($user)->create(['name' => 'Mój tramwaj']);
        LenticularProject::factory()->for($other)->create(['name' => 'Cudzy projekt']);
        $job = LenticularJob::factory()->for($project)->create([
            'source_file_id' => null,
            'operation' => 'finalize_sequence',
            'status' => LenticularJobStatus::Completed,
        ]);
        Storage::disk('local')->put('lenticular/results/final.zip', 'final');
        LenticularArtifact::factory()->for($job, 'lenticularJob')->create([
            'kind' => 'final',
            'disk' => 'local',
            'path' => 'lenticular/results/final.zip',
        ]);

        $this->actingAs($user)->get('/pl/account')
            ->assertOk()
            ->assertSee('Moje projekty')
            ->assertSee('Mój tramwaj')
            ->assertDontSee('Cudzy projekt')
            ->assertSee(route('lab.projects.download', ['locale' => 'pl', 'project' => $project]), false)
            ->assertSee('Pobierz finalny plik')
            ->assertSee('Edytuj projekt')
            ->assertSee('Zamów wydruk UV')
            ->assertSee('Usuń projekt');
        $this->actingAs($user)->get('/pl/account')
            ->assertSee(route('lab.projects.files', ['locale' => 'pl', 'project' => $project]), false)
            ->assertSee(route('lab.projects.archive', ['locale' => 'pl', 'project' => $project]), false)
            ->assertSee('Otwórz pliki projektu')
            ->assertSee('Pobierz cały projekt jako ZIP');
    }

    public function test_english_account_uses_english_project_labels(): void
    {
        $user = User::factory()->create();
        LenticularProject::factory()->for($user)->create(['name' => 'My tram']);

        $this->actingAs($user)->get('/en/account')
            ->assertOk()
            ->assertSee('My projects')
            ->assertSee('Edit project')
            ->assertSee('Order a UV print')
            ->assertSee('Delete project');
    }

    public function test_owner_can_delete_a_project_and_its_stored_files(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $project = LenticularProject::factory()->for($user)->create();
        Storage::disk('local')->put('lenticular/source.jpg', 'source');
        LenticularProjectFile::factory()->for($project)->create([
            'disk' => 'local',
            'path' => 'lenticular/source.jpg',
        ]);
        $job = LenticularJob::factory()->for($project)->create(['source_file_id' => null]);
        Storage::disk('local')->put('lenticular/final.zip', 'final');
        LenticularArtifact::factory()->for($job, 'lenticularJob')->create([
            'kind' => 'final',
            'disk' => 'local',
            'path' => 'lenticular/final.zip',
        ]);

        $this->actingAs($user)
            ->delete(route('lab.projects.destroy', ['locale' => 'pl', 'project' => $project]))
            ->assertRedirect(route('account', ['locale' => 'pl']))
            ->assertSessionHas('status', 'Projekt został usunięty.');

        $this->assertDatabaseMissing('lenticular_projects', ['id' => $project->id]);
        Storage::disk('local')->assertMissing('lenticular/source.jpg');
        Storage::disk('local')->assertMissing('lenticular/final.zip');
    }

    public function test_user_cannot_delete_another_users_project(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $project = LenticularProject::factory()->for($owner)->create();

        $this->actingAs($other)
            ->delete(route('lab.projects.destroy', ['locale' => 'pl', 'project' => $project]))
            ->assertNotFound();

        $this->assertDatabaseHas('lenticular_projects', ['id' => $project->id]);
    }

    public function test_owner_can_browse_and_download_project_files_but_other_user_cannot(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $project = LenticularProject::factory()->for($owner)->create(['name' => 'Moje pliki']);
        Storage::disk('local')->put('lenticular/source.jpg', 'source');
        $file = LenticularProjectFile::factory()->for($project)->create([
            'disk' => 'local',
            'path' => 'lenticular/source.jpg',
            'original_name' => 'source.jpg',
            'media_type' => 'image/jpeg',
        ]);

        $this->actingAs($owner)
            ->get(route('lab.projects.files', ['locale' => 'pl', 'project' => $project]))
            ->assertOk()
            ->assertSee('Pliki projektu')
            ->assertSee('source.jpg');

        $this->actingAs($owner)
            ->get(route('lab.projects.files.show', ['locale' => 'pl', 'project' => $project, 'file' => $file, 'download' => 1]))
            ->assertOk()
            ->assertDownload('source.jpg');

        $this->actingAs($other)
            ->get(route('lab.projects.files', ['locale' => 'pl', 'project' => $project]))
            ->assertNotFound();
    }

    public function test_project_archive_contains_source_files_and_artifacts(): void
    {
        Storage::fake('local');
        $project = LenticularProject::factory()->create(['name' => 'Projekt ZIP']);
        Storage::disk('local')->put('lenticular/source.jpg', 'source');
        LenticularProjectFile::factory()->for($project)->create([
            'kind' => 'source_image',
            'disk' => 'local',
            'path' => 'lenticular/source.jpg',
            'original_name' => 'source.jpg',
        ]);
        $job = LenticularJob::factory()->for($project)->create(['source_file_id' => null]);
        Storage::disk('local')->put('lenticular/final.pdf', 'pdf');
        LenticularArtifact::factory()->for($job, 'lenticularJob')->create([
            'kind' => 'final',
            'disk' => 'local',
            'path' => 'lenticular/final.pdf',
        ]);

        $result = app(LenticularProjectArchiveService::class)->create($project);
        $archive = new \PharData($result['path']);

        $this->assertSame('projekt-zip-files.zip', $result['name']);
        $this->assertTrue(isset($archive['project-files/source-image/source.jpg']));
        $this->assertTrue(isset($archive['artifacts/final/final.pdf']));

        unset($archive);
        @unlink($result['path']);
    }
}
