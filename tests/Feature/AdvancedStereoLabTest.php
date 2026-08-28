<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdvancedStereoLabTest extends TestCase
{
    public function test_polish_mpo_viewer_is_public(): void
    {
        $this->get('/pl/lab/mpo-viewer')
            ->assertOk()
            ->assertSee('MPO Viewer / Converter')
            ->assertSee('Pobierz lewy JPEG')
            ->assertSee('Eksportuj anaglif PNG')
            ->assertSee('data-mpo-viewer', false);
    }

    public function test_english_mpo_viewer_is_localized(): void
    {
        $this->get('/en/lab/mpo-viewer')
            ->assertOk()
            ->assertSee('Download left JPEG')
            ->assertSee('Export SBS PNG');
    }

    public function test_polish_wigglegram_maker_is_public(): void
    {
        $this->get('/pl/lab/wigglegram-maker')
            ->assertOk()
            ->assertSee('Wigglegram Maker')
            ->assertSee('Pobierz animowany GIF')
            ->assertSee('data-wigglegram', false);
    }

    public function test_english_wigglegram_maker_is_localized(): void
    {
        $this->get('/en/lab/wigglegram-maker')
            ->assertOk()
            ->assertSee('Download animated GIF')
            ->assertSee('Ping-pong — forward / backward');
    }

    public function test_lab_landing_links_to_new_tools(): void
    {
        $this->get('/pl/lab')
            ->assertOk()
            ->assertSee('/pl/lab/mpo-viewer', false)
            ->assertSee('/pl/lab/wigglegram-maker', false);
    }
}
