<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $orderColumns = [
            'base_currency' => function (Blueprint $table): void {
                $table->char('base_currency', 3)->default('PLN')->after('currency');
            },
            'exchange_rate' => function (Blueprint $table): void {
                $table->decimal('exchange_rate', 18, 8)->default(1)->after('base_currency');
            },
            'exchange_rate_source' => function (Blueprint $table): void {
                $table->string('exchange_rate_source', 32)->nullable()->after('exchange_rate');
            },
            'exchange_rate_effective_date' => function (Blueprint $table): void {
                $table->date('exchange_rate_effective_date')->nullable()->after('exchange_rate_source');
            },
            'currency_markup_percent' => function (Blueprint $table): void {
                $table->decimal('currency_markup_percent', 6, 2)->default(0)->after('exchange_rate_effective_date');
            },
            'subtotal_base_gross' => function (Blueprint $table): void {
                $table->decimal('subtotal_base_gross', 12, 2)->nullable()->after('subtotal_gross');
            },
            'shipping_base_gross' => function (Blueprint $table): void {
                $table->decimal('shipping_base_gross', 12, 2)->nullable()->after('shipping_gross');
            },
            'total_base_gross' => function (Blueprint $table): void {
                $table->decimal('total_base_gross', 12, 2)->nullable()->after('total_gross');
            },
        ];

        foreach ($orderColumns as $column => $definition) {
            if (! Schema::hasColumn('orders', $column)) {
                Schema::table('orders', $definition);
            }
        }

        $itemColumns = [
            'base_currency' => function (Blueprint $table): void {
                $table->char('base_currency', 3)->default('PLN')->after('currency');
            },
            'base_unit_price_gross' => function (Blueprint $table): void {
                $table->decimal('base_unit_price_gross', 12, 2)->nullable()->after('unit_price_gross');
            },
            'base_line_total_gross' => function (Blueprint $table): void {
                $table->decimal('base_line_total_gross', 12, 2)->nullable()->after('line_total_gross');
            },
        ];

        foreach ($itemColumns as $column => $definition) {
            if (! Schema::hasColumn('order_items', $column)) {
                Schema::table('order_items', $definition);
            }
        }

        if (Schema::hasTable('app_settings')) {
            $setting = DB::table('app_settings')
                ->where('group', 'currency')
                ->where('key', 'markup_percent')
                ->first();

            $plainValue = null;

            if ($setting && filled($setting->value)) {
                try {
                    $plainValue = Crypt::decryptString(
                        (string) $setting->value
                    );
                } catch (\Throwable) {
                    // Compatibility with the first K86.4D patch.
                    $plainValue = trim(
                        (string) $setting->value
                    );
                }
            }

            if (
                blank($plainValue)
                || in_array(
                    trim((string) $plainValue),
                    ['0', '0.0', '0.00'],
                    true
                )
            ) {
                $plainValue = '5.00';
            }

            $encryptedValue = Crypt::encryptString(
                trim((string) $plainValue)
            );

            if (! $setting) {
                DB::table('app_settings')->insert([
                    'group' => 'currency',
                    'key' => 'markup_percent',
                    'value' => $encryptedValue,
                    'is_secret' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('app_settings')
                    ->where('id', $setting->id)
                    ->update([
                        'value' => $encryptedValue,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        foreach (
            ['base_line_total_gross', 'base_unit_price_gross', 'base_currency']
            as $column
        ) {
            if (Schema::hasColumn('order_items', $column)) {
                Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }

        foreach (
            [
                'total_base_gross',
                'shipping_base_gross',
                'subtotal_base_gross',
                'currency_markup_percent',
                'exchange_rate_effective_date',
                'exchange_rate_source',
                'exchange_rate',
                'base_currency',
            ]
            as $column
        ) {
            if (Schema::hasColumn('orders', $column)) {
                Schema::table('orders', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
