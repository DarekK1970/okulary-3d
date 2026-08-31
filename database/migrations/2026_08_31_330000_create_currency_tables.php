<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('currencies')) {
            Schema::create('currencies', function (Blueprint $table) {
                $table->id();
                $table->char('code', 3)->unique();
                $table->string('name_pl', 80);
                $table->string('name_en', 80);
                $table->string('symbol', 8);
                $table->unsignedTinyInteger('decimal_places')
                    ->default(2);
                $table->boolean('is_enabled')
                    ->default(true)
                    ->index();
                $table->boolean('is_base')
                    ->default(false)
                    ->index();
                $table->unsignedSmallInteger('sort_order')
                    ->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('currency_rates')) {
            Schema::create('currency_rates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('currency_id')
                    ->constrained('currencies')
                    ->cascadeOnDelete();

                /*
                 * 1 unit of foreign currency = rate_to_base PLN.
                 * Example: 1 EUR = 4.25000000 PLN.
                 */
                $table->decimal('rate_to_base', 18, 8);
                $table->date('effective_date')->index();
                $table->string('source', 32)
                    ->default('manual');
                $table->boolean('is_manual')
                    ->default(false)
                    ->index();
                $table->dateTime('fetched_at')->nullable();
                $table->timestamps();

                $table->unique(
                    [
                        'currency_id',
                        'effective_date',
                        'source',
                    ],
                    'currency_rate_day_source_uq'
                );

                $table->index(
                    [
                        'currency_id',
                        'effective_date',
                    ],
                    'currency_rate_lookup_idx'
                );
            });
        }

        $now = now();

        foreach ($this->defaults() as $currency) {
            DB::table('currencies')->insertOrIgnore([
                ...$currency,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        /*
         * Enforce the intended base currency for a fresh/partial
         * deployment without touching enabled foreign currencies.
         */
        DB::table('currencies')
            ->where('code', '<>', 'PLN')
            ->update([
                'is_base' => false,
                'updated_at' => $now,
            ]);

        DB::table('currencies')
            ->where('code', 'PLN')
            ->update([
                'is_base' => true,
                'is_enabled' => true,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
        Schema::dropIfExists('currencies');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaults(): array
    {
        return [
            [
                'code' => 'PLN',
                'name_pl' => 'Polski złoty',
                'name_en' => 'Polish zloty',
                'symbol' => 'zł',
                'decimal_places' => 2,
                'is_enabled' => true,
                'is_base' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'EUR',
                'name_pl' => 'Euro',
                'name_en' => 'Euro',
                'symbol' => '€',
                'decimal_places' => 2,
                'is_enabled' => true,
                'is_base' => false,
                'sort_order' => 20,
            ],
            [
                'code' => 'GBP',
                'name_pl' => 'Funt brytyjski',
                'name_en' => 'British pound',
                'symbol' => '£',
                'decimal_places' => 2,
                'is_enabled' => true,
                'is_base' => false,
                'sort_order' => 30,
            ],
            [
                'code' => 'USD',
                'name_pl' => 'Dolar amerykański',
                'name_en' => 'US dollar',
                'symbol' => '$',
                'decimal_places' => 2,
                'is_enabled' => true,
                'is_base' => false,
                'sort_order' => 40,
            ],
        ];
    }
};
