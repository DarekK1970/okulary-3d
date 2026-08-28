<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('number', 40)->unique();
            $table->uuid('public_token')->unique();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('locale', 10)->default('pl');
            $table->string('status', 30)->default('pending')->index();
            $table->char('currency', 3)->default('PLN');

            $table->decimal('subtotal_gross', 12, 2);
            $table->decimal('shipping_gross', 12, 2)->default(0);
            $table->decimal('total_gross', 12, 2);

            $table->string('customer_email', 255)->index();
            $table->string('customer_first_name', 120);
            $table->string('customer_last_name', 120);
            $table->string('customer_phone', 40)->nullable();

            $table->string('billing_company', 180)->nullable();
            $table->string('billing_tax_id', 40)->nullable();
            $table->string('billing_address_line1', 255);
            $table->string('billing_address_line2', 255)->nullable();
            $table->string('billing_postal_code', 30);
            $table->string('billing_city', 120);
            $table->char('billing_country_code', 2)->default('PL');

            $table->boolean('shipping_same_as_billing')->default(true);
            $table->string('shipping_first_name', 120);
            $table->string('shipping_last_name', 120);
            $table->string('shipping_company', 180)->nullable();
            $table->string('shipping_address_line1', 255);
            $table->string('shipping_address_line2', 255)->nullable();
            $table->string('shipping_postal_code', 30);
            $table->string('shipping_city', 120);
            $table->char('shipping_country_code', 2)->default('PL');

            $table->text('customer_note')->nullable();

            $table->timestamp('placed_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('stock_released_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();

            $table->string('sku_snapshot', 100);
            $table->string('product_name_snapshot', 220);
            $table->string('variant_name_snapshot', 140)->nullable();

            $table->unsignedInteger('quantity');
            $table->decimal('unit_price_gross', 12, 2);
            $table->decimal('vat_rate', 5, 2);
            $table->decimal('line_total_gross', 12, 2);
            $table->char('currency', 3);

            $table->timestamps();

            $table->index(['order_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
