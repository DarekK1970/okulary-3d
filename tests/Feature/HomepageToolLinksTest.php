<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageToolLinksTest extends TestCase
{
    public function test_polish_homepage_links_working_lab_tools(): void
    {
        $this->get('/pl')
            ->assertOk()
            ->assertSee('/pl/lab/anaglyph-maker', false)
            ->assertSee('/pl/lab/lenticular', false)
            ->assertSee('/pl/lab/stereo-alignment', false)
            ->assertSee('/pl/lab/wigglegram-maker', false)
            ->assertSee('/pl/lab/mpo-viewer', false)
            ->assertSee('/pl/shop', false);
    }

    public function test_lenticular_creator_home_card_no_longer_points_to_hash(): void
    {
        $response = $this->get('/pl');

        $response
            ->assertOk()
            ->assertSee('Kreator lenticular 60 LPI')
            ->assertSee('/pl/lab/lenticular', false);
    }

    public function test_unimplemented_stereo_base_calculator_is_marked_coming_soon(): void
    {
        $this->get('/pl')
            ->assertOk()
            ->assertSee('Kalkulator bazy stereo')
            ->assertSee('Wkrótce');
    }
}
