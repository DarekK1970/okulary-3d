<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageTest extends TestCase
{
    public function test_polish_homepage_contains_main_portal_sections(): void
    {
        $response = $this->get('/pl');

        $response
            ->assertOk()
            ->assertSee('Zobacz świat')
            ->assertSee('Najnowsze w świecie 3D')
            ->assertSee('3D LAB')
            ->assertSee('Kategorie sklepu')
            ->assertSee('3D dzisiaj')
            ->assertSee('Galeria społeczności')
            ->assertSee('Z archiwum stereoskopii');
    }

    public function test_english_homepage_contains_translated_main_sections(): void
    {
        $response = $this->get('/en');

        $response
            ->assertOk()
            ->assertSee('See the world')
            ->assertSee('Latest from the 3D world')
            ->assertSee('3D LAB')
            ->assertSee('Shop categories')
            ->assertSee('3D today')
            ->assertSee('Community gallery')
            ->assertSee('From the stereoscopy archive');
    }
}
