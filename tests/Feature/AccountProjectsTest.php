<?php

namespace Tests\Feature;

use App\Enums\LenticularJobStatus;
use App\Models\LenticularArtifact;
use App\Models\LenticularJob;
use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use App\Models\User;
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
}
