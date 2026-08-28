<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocalizationTest extends TestCase
{
    public function test_root_redirects_to_default_locale(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/pl');
    }

    public function test_polish_homepage_uses_polish_locale(): void
    {
        $response = $this->get('/pl');

        $response
            ->assertOk()
            ->assertSee('lang="pl"', false)
            ->assertSee('Zobacz świat')
            ->assertSee('Najnowsze w świecie 3D')
            ->assertSee('Kategorie sklepu');
    }

    public function test_english_homepage_uses_english_locale(): void
    {
        $response = $this->get('/en');

        $response
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('See the world')
            ->assertSee('Latest from the 3D world')
            ->assertSee('Shop categories');
    }

    public function test_unsupported_locale_returns_404(): void
    {
        $response = $this->get('/de');

        $response->assertNotFound();
    }
}
