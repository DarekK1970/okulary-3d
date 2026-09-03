<?php

namespace Tests\Feature;

use Tests\TestCase;

class LenticularLabTest extends TestCase
{
    public function test_polish_lenticular_lab_is_public(): void
    {
        $this->get('/pl/lab/lenticular')
            ->assertOk()
            ->assertSee('Lenticular LAB')
            ->assertSee('Lenticular Interlacer')
            ->assertSee('Pitch Test Generator')
            ->assertSee('Lenticular Calculator')
            ->assertSee('A4 Lenticular Wizard')
            ->assertSee('Kierunek soczewek')
            ->assertSee('value="vertical" selected', false)
            ->assertSee('Poziome');
    }

    public function test_english_lenticular_lab_is_localized(): void
    {
        $this->get('/en/lab/lenticular')
            ->assertOk()
            ->assertSee('Source files stay on your device')
            ->assertSee('Download print-ready PDF')
            ->assertSee('Download PNG')
            ->assertSee('Apply to Interlacer')
            ->assertSee('Lens orientation')
            ->assertSee('Horizontal');
    }

    public function test_lab_landing_contains_lenticular_tool(): void
    {
        $this->get('/pl/lab')
            ->assertOk()
            ->assertSee('Lenticular LAB')
            ->assertSee(
                '/pl/lab/lenticular',
                false
            );
    }

    public function test_lenticular_workspace_contains_browser_hooks(): void
    {
        $this->get('/pl/lab/lenticular')
            ->assertOk()
            ->assertSee(
                'data-lenticular-lab',
                false
            )
            ->assertSee(
                'data-interlacer-canvas',
                false
            )
            ->assertSee(
                'data-pitch-canvas',
                false
            )
            ->assertSee(
                'data-calc-control',
                false
            )
            ->assertSee(
                'data-wizard-control',
                false
            );
    }
}
