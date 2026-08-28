<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocalizationTest extends TestCase
{
    public function test_root_redirects_to_default_locale(): void
    {
        $this->get('/')->assertRedirect('/pl');
    }

    public function test_polish_homepage_uses_polish_locale(): void
    {
        $this->get('/pl')
            ->assertOk()
            ->assertSee('Zobacz świat w trzech wymiarach')
            ->assertSee('Aktualny język')
            ->assertSee('pl');
    }

    public function test_english_homepage_uses_english_locale(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('See the world in three dimensions')
            ->assertSee('Current language')
            ->assertSee('en');
    }

    public function test_unsupported_locale_returns_404(): void
    {
        $this->get('/de')->assertNotFound();
    }
}
