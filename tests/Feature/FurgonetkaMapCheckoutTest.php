<?php

namespace Tests\Feature;

use App\Services\FurgonetkaSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FurgonetkaMapCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_is_disabled_without_integration(): void
    {
        $settings = app(FurgonetkaSettingsService::class);
        $settings->set('map_api_key', 'map-key', true);

        $this->assertFalse($settings->mapEnabled());
    }

    public function test_map_requires_enabled_integration_and_key(): void
    {
        $settings = app(FurgonetkaSettingsService::class);
        $settings->set('enabled', '1');
        $settings->set('map_api_key', 'map-key', true);

        $this->assertTrue($settings->mapEnabled());
        $this->assertSame('map-key', $settings->mapApiKey());
    }
}
