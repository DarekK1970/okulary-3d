<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageTest extends TestCase
{
    public function test_polish_homepage_renders_core_sections_without_empty_optional_modules(): void
    {
        $response = $this->get('/pl');

        $response
            ->assertOk()
            ->assertSee('home-hero', false)
            ->assertSee('home-latest-publications', false)
            ->assertSee('id="lab"', false)
            ->assertSee('id="shop"', false)
            ->assertSee('id="gallery"', false);

        /*
         * Sekcje zależne od treści są celowo ukrywane, gdy nie ma danych.
         * Ich pojawianie się po dodaniu publikacji jest testowane w osobnych
         * testach funkcjonalnych modułów artykułów i archiwum.
         */
        $response
            ->assertDontSee('Dzisiaj w 3D')
            ->assertDontSee('Z archiwum stereoskopii');
    }

    public function test_english_homepage_renders_core_sections_without_empty_optional_modules(): void
    {
        $response = $this->get('/en');

        $response
            ->assertOk()
            ->assertSee('home-hero', false)
            ->assertSee('home-latest-publications', false)
            ->assertSee('id="lab"', false)
            ->assertSee('id="shop"', false)
            ->assertSee('id="gallery"', false);

        /*
         * Dynamic content modules must not render as empty placeholders.
         */
        $response
            ->assertDontSee('3D today')
            ->assertDontSee('From the stereoscopy archive');
    }
}
