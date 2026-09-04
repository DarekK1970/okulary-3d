<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FalAiSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FalAiSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_open_fal_ai_settings(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($superAdmin)->get('/admin/settings/fal-ai')
            ->assertOk()->assertSee('Integracja fal.ai');
        $this->actingAs($admin)->get('/admin/settings/fal-ai')->assertForbidden();
    }

    public function test_settings_are_saved_and_api_key_is_encrypted(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($superAdmin)->put('/admin/settings/fal-ai', $this->payload([
            'enabled' => '1',
            'api_key' => 'fal-secret-test-key',
        ]))->assertSessionHasNoErrors();

        $settings = app(FalAiSettingsService::class);
        $this->assertTrue($settings->configured());
        $this->assertSame('fal-secret-test-key', $settings->apiKey());
        $this->assertSame('4k', $settings->upscaleResolution());

        $raw = DB::table('app_settings')->where('group', 'fal_ai')->where('key', 'api_key')->value('value');
        $this->assertNotSame('fal-secret-test-key', $raw);
        $this->assertStringNotContainsString('fal-secret-test-key', (string) $raw);
        $this->assertDatabaseHas('app_settings', ['group' => 'fal_ai', 'key' => 'api_key', 'is_secret' => 1]);
    }

    public function test_blank_key_keeps_existing_secret(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        app(FalAiSettingsService::class)->set('api_key', 'existing-key', true);

        $this->actingAs($superAdmin)->put('/admin/settings/fal-ai', $this->payload(['api_key' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame('existing-key', app(FalAiSettingsService::class)->apiKey());
    }

    public function test_connection_check_reads_pricing_without_starting_generation(): void
    {
        Http::fake(['api.fal.ai/v1/models/pricing*' => Http::response(['prices' => [['unit_price' => 0.1]]])]);
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        app(FalAiSettingsService::class)->set('api_key', 'fal-test-key', true);

        $this->actingAs($superAdmin)->post('/admin/settings/fal-ai/test')
            ->assertSessionHasNoErrors()->assertSessionHas('status');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->hasHeader('Authorization', 'Key fal-test-key')
            && str_contains($request->url(), '/v1/models/pricing')
            && str_contains($request->url(), 'endpoint_id=bytedance%2Fseedance-2.5%2Fimage-to-video')
        );
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'enabled' => '0', 'api_key' => '', 'timeout' => 60,
            'seedance_model' => 'bytedance/seedance-2.5/image-to-video',
            'resolution' => '720p', 'duration' => 4, 'generate_audio' => '0',
            'upscaling_enabled' => '1',
            'upscaler_model' => 'fal-ai/bytedance-upscaler/upscale/video',
            'upscale_resolution' => '4k', 'maximum_job_cost_usd' => '5.00',
            'daily_budget_usd' => '50.00',
        ], $overrides);
    }
}
