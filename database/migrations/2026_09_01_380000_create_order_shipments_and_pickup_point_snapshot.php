<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'shipping_point_name',
            'shipping_point_type',
            'shipping_point_original_id',
            'shipping_point_country_code',
        ] as $column) {
            if (! Schema::hasColumn('orders', $column)) {
                Schema::table('orders', function (Blueprint $table) use ($column) {
                    $after = match ($column) {
                        'shipping_point_name' => 'shipping_point',
                        'shipping_point_type' => 'shipping_point_name',
                        'shipping_point_original_id' => 'shipping_point_type',
                        default => 'shipping_point_original_id',
                    };

                    $table->string($column, 190)
                        ->nullable()
                        ->after($after);
                });
            }
        }

        if (! Schema::hasTable('order_shipments')) {
            Schema::create('order_shipments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('provider', 40)
                    ->default('furgonetka');
                $table->string('external_package_id', 100)
                    ->nullable()
                    ->index();
                $table->unsignedBigInteger('service_id')
                    ->nullable();
                $table->string('carrier', 80)
                    ->nullable();
                $table->string('state', 80)
                    ->nullable()
                    ->index();

                $table->uuid('order_command_uuid')
                    ->nullable()
                    ->index();

                $table->string('tracking_number', 160)
                    ->nullable();
                $table->text('tracking_url')
                    ->nullable();

                $table->string('last_tracking_state', 80)
                    ->nullable();
                $table->string('last_tracking_status', 255)
                    ->nullable();
                $table->dateTime('last_tracking_at')
                    ->nullable();

                $table->string('label_format', 10)
                    ->default('pdf');
                $table->string('label_page_format', 10)
                    ->default('a6');

                $table->json('request_snapshot')
                    ->nullable();
                $table->json('response_snapshot')
                    ->nullable();

                $table->dateTime('ordered_at')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    ['order_id', 'provider'],
                    'order_ship_provider_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shipments');

        foreach ([
            'shipping_point_country_code',
            'shipping_point_original_id',
            'shipping_point_type',
            'shipping_point_name',
        ] as $column) {
            if (Schema::hasColumn('orders', $column)) {
                Schema::table('orders', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
