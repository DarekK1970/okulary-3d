<?php

namespace Tests\Feature;

use Tests\TestCase;

class LenticularPdfPrintReadyTest extends TestCase
{
    public function test_polish_lenticular_lab_contains_pdf_exports(): void
    {
        $this->get('/pl/lab/lenticular')
            ->assertOk()
            ->assertSee('Pobierz PDF do druku')
            ->assertSee('Pobierz test PDF')
            ->assertSee('Szerokość wydruku (mm)')
            ->assertSee('Wysokość wydruku (mm)');
    }

    public function test_english_lenticular_lab_contains_pdf_exports(): void
    {
        $this->get('/en/lab/lenticular')
            ->assertOk()
            ->assertSee('Download print-ready PDF')
            ->assertSee('Download test PDF')
            ->assertSee('Print width (mm)')
            ->assertSee('Print height (mm)');
    }

    public function test_lenticular_page_contains_pdf_engine_hooks(): void
    {
        $this->get('/pl/lab/lenticular')
            ->assertOk()
            ->assertSee('data-action="interlace-export-pdf"', false)
            ->assertSee('data-action="pitch-export-pdf"', false)
            ->assertSee('data-lenticular-control="widthMm"', false)
            ->assertSee('data-lenticular-control="heightMm"', false);
    }
}
