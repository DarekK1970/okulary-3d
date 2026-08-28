<?php

namespace Tests\Feature;

use Tests\TestCase;

class LayoutTest extends TestCase
{
    public function test_polish_homepage_contains_global_layout(): void
    {
        $response = $this->get('/pl');

        $response
            ->assertOk()
            ->assertSee('Wortal Okulary 3D')
            ->assertSee('Artykuły')
            ->assertSee('3D LAB')
            ->assertSee('Dołącz do społeczności')
            ->assertSee('kontakt@okulary-3d.pl');
    }

    public function test_english_homepage_contains_translated_global_layout(): void
    {
        $response = $this->get('/en');

        $response
            ->assertOk()
            ->assertSee('3D Glasses Portal')
            ->assertSee('Articles')
            ->assertSee('3D LAB')
            ->assertSee('Join the community')
            ->assertSee('kontakt@okulary-3d.pl');
    }
}
