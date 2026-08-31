<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CurrencyPreMigrationFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_layout_renders_without_currency_tables(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists(
            'currency_rates'
        );

        Schema::dropIfExists(
            'currencies'
        );

        Schema::enableForeignKeyConstraints();

        $this->get('/en')
            ->assertOk()
            ->assertSee('PLN')
            ->assertSee(
                'data-menu-toggle',
                false
            );
    }
}
