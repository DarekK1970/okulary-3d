<?php

namespace Tests\Feature;

use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_users_by_email_name_plan_and_status(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create([
            'name' => 'Klient Premium',
            'email' => 'premium@example.com',
            'lenticular_plan' => 'premium',
        ]);
        User::factory()->create([
            'name' => 'Inny klient',
            'email' => 'free@example.com',
            'lenticular_plan' => 'free',
            'suspended_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/users?email=premium%40example.com&name=Premium&plan=premium&status=active')
            ->assertOk()
            ->assertSee('Klient Premium')
            ->assertDontSee('free@example.com');
    }

    public function test_admin_can_change_user_plan_and_expiry_date(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['lenticular_plan' => 'free']);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'lenticular_plan' => 'pro',
                'plan_expires_at' => now()->addMonth()->toDateString(),
            ])
            ->assertRedirect(route('admin.users.edit', $user));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'lenticular_plan' => 'pro',
        ]);
        $this->assertSame(now()->addMonth()->toDateString(), $user->fresh()->plan_expires_at?->toDateString());
    }

    public function test_admin_can_suspend_and_restore_regular_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.users.suspend', $user))->assertRedirect();
        $this->assertNotNull($user->fresh()->suspended_at);

        $this->actingAs($admin)->patch(route('admin.users.restore', $user))->assertRedirect();
        $this->assertNull($user->fresh()->suspended_at);
    }

    public function test_admin_cannot_suspend_self_or_super_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($admin)->patch(route('admin.users.suspend', $admin))->assertUnprocessable();
        $this->actingAs($admin)->patch(route('admin.users.suspend', $superAdmin))->assertForbidden();
    }

    public function test_suspended_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123',
            'suspended_at' => now(),
        ]);

        $this->post('/pl/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_activity_and_language_are_recorded_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'preferred_locale' => 'pl',
            'last_activity_at' => null,
        ]);

        $this->actingAs($user)->get('/en/account')->assertOk();

        $user->refresh();
        $this->assertSame('en', $user->preferred_locale);
        $this->assertNotNull($user->last_activity_at);
    }

    public function test_user_table_displays_and_filters_by_effective_plan(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'lenticular_plan' => 'free',
            'email' => 'premium-admin@example.com',
        ]);

        $this->actingAs($admin)
            ->get('/admin/users?plan=premium')
            ->assertOk()
            ->assertSee('premium-admin@example.com')
            ->assertSee('PREMIUM');

        $expired = User::factory()->create([
            'lenticular_plan' => 'premium',
            'plan_expires_at' => now()->subDay(),
            'email' => 'expired@example.com',
        ]);

        $this->actingAs($admin)
            ->get('/admin/users?email=expired%40example.com')
            ->assertOk()
            ->assertSee('expired@example.com')
            ->assertSee('FREE')
            ->assertDontSee('admin-user-plan is-premium', false);
    }

    public function test_admin_can_browse_a_users_projects_and_download_their_files(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();
        $project = LenticularProject::factory()->for($user)->create(['name' => 'Projekt klienta']);
        Storage::disk('local')->put('lenticular/source.jpg', 'image-content');
        $file = LenticularProjectFile::factory()->for($project)->create([
            'kind' => 'source_image',
            'disk' => 'local',
            'path' => 'lenticular/source.jpg',
            'original_name' => 'source.jpg',
            'media_type' => 'image/jpeg',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.projects', $user))
            ->assertOk()
            ->assertSee('Projekt klienta')
            ->assertSee(route('admin.users.projects.files', [$user, $project]), false)
            ->assertSee('Otwórz pliki projektu');

        $this->actingAs($admin)
            ->get(route('admin.users.projects.archive', [$user, $project]))
            ->assertOk()
            ->assertDownload('projekt-klienta-files.zip');

        $this->actingAs($admin)
            ->get(route('admin.users.projects.files', [$user, $project]))
            ->assertOk()
            ->assertSee('source.jpg')
            ->assertSee('Pobierz plik');

        $this->actingAs($admin)
            ->get(route('admin.users.projects.files.show', [$user, $project, $file, 'download' => 1]))
            ->assertOk()
            ->assertDownload('source.jpg');
    }

    public function test_admin_project_file_routes_validate_user_and_project_relationships(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = LenticularProject::factory()->for($owner)->create();
        $otherProject = LenticularProject::factory()->for($otherUser)->create();
        Storage::disk('local')->put('lenticular/other.jpg', 'other');
        $otherFile = LenticularProjectFile::factory()->for($otherProject)->create([
            'disk' => 'local',
            'path' => 'lenticular/other.jpg',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.projects.files', [$otherUser, $project]))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.users.projects.files.show', [$owner, $project, $otherFile]))
            ->assertNotFound();
    }
}
