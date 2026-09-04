<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('marketplace_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketplace_category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description', 500);
            $table->text('description');
            $table->string('image_path')->nullable();
            $table->string('print_size', 10)->index();
            $table->unsignedInteger('token_cost');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('marketplace_shipping_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('marketplace_shipping_rates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('marketplace_shipping_provider_id');
            $table->string('country_code', 2)->nullable();
            $table->string('print_size', 10)->nullable();
            $table->unsignedInteger('token_cost');
            $table->timestamps();
            $table->unique(['marketplace_shipping_provider_id', 'country_code', 'print_size'], 'marketplace_shipping_rate_unique');
            $table->foreign('marketplace_shipping_provider_id', 'marketplace_rate_provider_fk')
                ->references('id')->on('marketplace_shipping_providers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_shipping_rates');
        Schema::dropIfExists('marketplace_shipping_providers');
        Schema::dropIfExists('marketplace_products');
        Schema::dropIfExists('marketplace_categories');
    }
};
