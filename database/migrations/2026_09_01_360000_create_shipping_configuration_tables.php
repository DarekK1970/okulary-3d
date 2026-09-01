<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product_variants', 'weight_grams')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->unsignedInteger('weight_grams')
                    ->nullable();
            });
        }

        if (! Schema::hasTable('shipping_countries')) {
            Schema::create('shipping_countries', function (Blueprint $table) {
                $table->id();
                $table->char('code', 2)->unique();
                $table->string('name_pl', 100);
                $table->string('name_en', 100);
                $table->boolean('is_enabled')
                    ->default(false)
                    ->index();
                $table->boolean('is_default')
                    ->default(false)
                    ->index();
                $table->unsignedInteger('sort_order')
                    ->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('shipping_methods')) {
            Schema::create('shipping_methods', function (Blueprint $table) {
                $table->id();
                $table->string('code', 60)->unique();
                $table->string('name_pl', 120);
                $table->string('name_en', 120);
                $table->boolean('requires_pickup_point')
                    ->default(false);
                $table->boolean('is_enabled')
                    ->default(true)
                    ->index();
                $table->unsignedInteger('sort_order')
                    ->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('shipping_rates')) {
            Schema::create('shipping_rates', function (Blueprint $table) {
                $table->id();

                $table->foreignId('shipping_country_id')
                    ->constrained('shipping_countries')
                    ->cascadeOnDelete();

                $table->foreignId('shipping_method_id')
                    ->constrained('shipping_methods')
                    ->cascadeOnDelete();

                $table->unsignedInteger('weight_from_grams');
                $table->unsignedInteger('weight_to_grams');

                $table->decimal('price_pln', 12, 2);
                $table->boolean('is_enabled')
                    ->default(true)
                    ->index();

                $table->timestamps();

                $table->unique(
                    [
                        'shipping_country_id',
                        'shipping_method_id',
                        'weight_from_grams',
                        'weight_to_grams',
                    ],
                    'ship_rate_range_uq'
                );

                $table->index(
                    [
                        'shipping_country_id',
                        'shipping_method_id',
                        'is_enabled',
                    ],
                    'ship_rate_lookup_idx'
                );
            });
        }

        $now = now();

        $countries = [
            ['PL', 'Polska', 'Poland', true, true, 10],
            ['DE', 'Niemcy', 'Germany', false, false, 20],
            ['CZ', 'Czechy', 'Czechia', false, false, 30],
            ['SK', 'Słowacja', 'Slovakia', false, false, 40],
            ['AT', 'Austria', 'Austria', false, false, 50],
            ['BE', 'Belgia', 'Belgium', false, false, 60],
            ['BG', 'Bułgaria', 'Bulgaria', false, false, 70],
            ['HR', 'Chorwacja', 'Croatia', false, false, 80],
            ['CY', 'Cypr', 'Cyprus', false, false, 90],
            ['DK', 'Dania', 'Denmark', false, false, 100],
            ['EE', 'Estonia', 'Estonia', false, false, 110],
            ['FI', 'Finlandia', 'Finland', false, false, 120],
            ['FR', 'Francja', 'France', false, false, 130],
            ['GR', 'Grecja', 'Greece', false, false, 140],
            ['ES', 'Hiszpania', 'Spain', false, false, 150],
            ['NL', 'Niderlandy', 'Netherlands', false, false, 160],
            ['IE', 'Irlandia', 'Ireland', false, false, 170],
            ['IS', 'Islandia', 'Iceland', false, false, 180],
            ['LI', 'Liechtenstein', 'Liechtenstein', false, false, 190],
            ['LT', 'Litwa', 'Lithuania', false, false, 200],
            ['LU', 'Luksemburg', 'Luxembourg', false, false, 210],
            ['LV', 'Łotwa', 'Latvia', false, false, 220],
            ['MT', 'Malta', 'Malta', false, false, 230],
            ['NO', 'Norwegia', 'Norway', false, false, 240],
            ['PT', 'Portugalia', 'Portugal', false, false, 250],
            ['RO', 'Rumunia', 'Romania', false, false, 260],
            ['SI', 'Słowenia', 'Slovenia', false, false, 270],
            ['SE', 'Szwecja', 'Sweden', false, false, 280],
            ['CH', 'Szwajcaria', 'Switzerland', false, false, 290],
            ['HU', 'Węgry', 'Hungary', false, false, 300],
            ['IT', 'Włochy', 'Italy', false, false, 310],
            ['GB', 'Wielka Brytania', 'United Kingdom', false, false, 320],
        ];

        foreach ($countries as [
            $code,
            $namePl,
            $nameEn,
            $enabled,
            $default,
            $sortOrder,
        ]) {
            DB::table('shipping_countries')->updateOrInsert(
                ['code' => $code],
                [
                    'name_pl' => $namePl,
                    'name_en' => $nameEn,
                    'is_enabled' => $enabled,
                    'is_default' => $default,
                    'sort_order' => $sortOrder,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $methods = [
            [
                'code' => 'courier',
                'name_pl' => 'Kurier',
                'name_en' => 'Courier',
                'requires_pickup_point' => false,
                'is_enabled' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'parcel_locker',
                'name_pl' => 'Paczkomat / punkt odbioru',
                'name_en' => 'Parcel locker / pickup point',
                'requires_pickup_point' => true,
                'is_enabled' => true,
                'sort_order' => 20,
            ],
            [
                'code' => 'pickup',
                'name_pl' => 'Odbiór osobisty',
                'name_en' => 'Local pickup',
                'requires_pickup_point' => false,
                'is_enabled' => true,
                'sort_order' => 30,
            ],
        ];

        foreach ($methods as $method) {
            DB::table('shipping_methods')->updateOrInsert(
                ['code' => $method['code']],
                [
                    ...$method,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $polandId = DB::table('shipping_countries')
            ->where('code', 'PL')
            ->value('id');

        if ($polandId) {
            $defaultRates = [
                ['courier', 0, 30000, '18.99'],
                ['parcel_locker', 0, 30000, '16.99'],
                ['pickup', 0, 30000, '0.00'],
            ];

            foreach ($defaultRates as [
                $methodCode,
                $from,
                $to,
                $price,
            ]) {
                $methodId = DB::table('shipping_methods')
                    ->where('code', $methodCode)
                    ->value('id');

                if (! $methodId) {
                    continue;
                }

                DB::table('shipping_rates')->updateOrInsert(
                    [
                        'shipping_country_id' => $polandId,
                        'shipping_method_id' => $methodId,
                        'weight_from_grams' => $from,
                        'weight_to_grams' => $to,
                    ],
                    [
                        'price_pln' => $price,
                        'is_enabled' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }

        // Polska jest zawsze krajem domyślnym K87.
        DB::table('shipping_countries')
            ->where('code', '<>', 'PL')
            ->update(['is_default' => false]);

        DB::table('shipping_countries')
            ->where('code', 'PL')
            ->update([
                'is_enabled' => true,
                'is_default' => true,
            ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('shipping_countries');

        if (Schema::hasColumn('product_variants', 'weight_grams')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('weight_grams');
            });
        }
    }
};
