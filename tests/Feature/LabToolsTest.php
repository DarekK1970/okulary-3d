<?php

namespace Tests\Feature;

use Tests\TestCase;

class LabToolsTest extends TestCase
{
    public function test_polish_lab_landing_page_is_public(): void
    {
        $this->get('/pl/lab')
            ->assertOk()
            ->assertSee('3D LAB')
            ->assertSee('Anaglyph Maker')
            ->assertSee('Stereo Alignment / Converter')
            ->assertSee('/pl/lab/anaglyph-maker', false)
            ->assertSee('/pl/lab/stereo-alignment', false);
    }

    public function test_english_lab_landing_page_is_localized(): void
    {
        $this->get('/en/lab')
            ->assertOk()
            ->assertSee('Your images stay on your device.')
            ->assertSee('Open tool');
    }

    public function test_anaglyph_maker_contains_browser_workspace(): void
    {
        $this->get('/pl/lab/anaglyph-maker')
            ->assertOk()
            ->assertSee('Anaglyph Maker')
            ->assertSee('data-stereo-lab', false)
            ->assertSee('data-tool="anaglyph"', false)
            ->assertSee('Eksportuj PNG');
    }

    public function test_stereo_alignment_contains_all_preview_modes(): void
    {
        $this->get('/pl/lab/stereo-alignment')
            ->assertOk()
            ->assertSee('Stereo Alignment / Converter')
            ->assertSee('Parallel — para równoległa')
            ->assertSee('Cross-eye — para krzyżowa')
            ->assertSee('Anaglif czerwono-cyjanowy')
            ->assertSee('Overlay 50%')
            ->assertSee('Blink — naprzemiennie L/R');
    }

    public function test_lab_navigation_points_to_real_lab_route(): void
    {
        $this->get('/pl')
            ->assertOk()
            ->assertSee('/pl/lab', false);
    }
}
