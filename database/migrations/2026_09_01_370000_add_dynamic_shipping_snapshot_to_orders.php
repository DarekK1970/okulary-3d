<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'shipping_rate_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('shipping_rate_id')
                    ->nullable()
                    ->after('shipping_method');

                $table->index(
                    'shipping_rate_id',
                    'orders_ship_rate_idx'
                );
            });
        }

        if (! Schema::hasColumn('orders', 'shipping_weight_grams')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedInteger('shipping_weight_grams')
                    ->nullable()
                    ->after('shipping_name_snapshot');
            });
        }

        if (! Schema::hasColumn('orders', 'shipping_base_before_margin')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal(
                    'shipping_base_before_margin',
                    12,
                    2
                )
                    ->nullable()
                    ->after('shipping_weight_grams');
            });
        }

        if (! Schema::hasColumn('orders', 'shipping_logistics_margin_percent')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal(
                    'shipping_logistics_margin_percent',
                    6,
                    2
                )
                    ->default(0)
                    ->after('shipping_base_before_margin');
            });
        }

        if (! Schema::hasColumn('orders', 'shipping_country_name_snapshot')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string(
                    'shipping_country_name_snapshot',
                    100
                )
                    ->nullable()
                    ->after('shipping_country_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'shipping_rate_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('orders_ship_rate_idx');
                $table->dropColumn('shipping_rate_id');
            });
        }

        foreach (
            [
                'shipping_country_name_snapshot',
                'shipping_logistics_margin_percent',
                'shipping_base_before_margin',
                'shipping_weight_grams',
            ]
            as $column
        ) {
            if (Schema::hasColumn('orders', $column)) {
                Schema::table('orders', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
