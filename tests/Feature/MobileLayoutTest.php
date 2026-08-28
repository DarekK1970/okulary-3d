<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobileLayoutTest extends TestCase
{
    public function test_mobile_navigation_controls_are_present_in_polish_layout(): void
    {
        $response = $this->get('/pl');

        $response
            ->assertOk()
            ->assertSee('data-menu-toggle', false)
            ->assertSee('data-menu-close', false)
            ->assertSee('data-menu-backdrop', false)
            ->assertSee('Polski')
            ->assertSee('English');
    }

    public function test_mobile_navigation_controls_are_present_in_english_layout(): void
    {
        $response = $this->get('/en');

        $response
            ->assertOk()
            ->assertSee('data-menu-toggle', false)
            ->assertSee('data-menu-close', false)
            ->assertSee('data-menu-backdrop', false)
            ->assertSee('Polski')
            ->assertSee('English');
    }
}
