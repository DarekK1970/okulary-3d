<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'shipping_carrier' => function (Blueprint $table): void {
                $table->string(
                    'shipping_carrier',
                    100
                )
                    ->nullable()
                    ->after(
                        'shipping_point_country_code'
                    );
            },

            'shipping_tracking_number' => function (Blueprint $table): void {
                $table->string(
                    'shipping_tracking_number',
                    190
                )
                    ->nullable()
                    ->after(
                        'shipping_carrier'
                    );
            },

            'shipping_tracking_url' => function (Blueprint $table): void {
                $table->text(
                    'shipping_tracking_url'
                )
                    ->nullable()
                    ->after(
                        'shipping_tracking_number'
                    );
            },

            'shipping_external_id' => function (Blueprint $table): void {
                $table->string(
                    'shipping_external_id',
                    190
                )
                    ->nullable()
                    ->after(
                        'shipping_tracking_url'
                    );
            },

            'shipping_tracking_updated_at' => function (Blueprint $table): void {
                $table->dateTime(
                    'shipping_tracking_updated_at'
                )
                    ->nullable()
                    ->after(
                        'shipping_external_id'
                    );
            },
        ];

        foreach (
            $columns
            as $column => $definition
        ) {
            if (
                ! Schema::hasColumn(
                    'orders',
                    $column
                )
            ) {
                Schema::table(
                    'orders',
                    $definition
                );
            }
        }
    }

    public function down(): void
    {
        foreach (
            [
                'shipping_tracking_updated_at',
                'shipping_external_id',
                'shipping_tracking_url',
                'shipping_tracking_number',
                'shipping_carrier',
            ]
            as $column
        ) {
            if (
                Schema::hasColumn(
                    'orders',
                    $column
                )
            ) {
                Schema::table(
                    'orders',
                    fn (Blueprint $table) =>
                        $table->dropColumn(
                            $column
                        )
                );
            }
        }
    }
};
