<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_method', 60)
                ->nullable()
                ->after('shipping_gross');

            $table->string('shipping_name_snapshot', 160)
                ->nullable()
                ->after('shipping_method');

            $table->string('shipping_point', 160)
                ->nullable()
                ->after('shipping_country_code');

            $table->string('payment_method', 60)
                ->default('bank_transfer')
                ->after('shipping_name_snapshot');

            $table->string('payment_status', 30)
                ->default('unpaid')
                ->index()
                ->after('payment_method');

            $table->string('payment_merchant_external_id', 100)
                ->nullable()
                ->unique()
                ->after('payment_status');

            $table->string('payment_idempotency_key', 45)
                ->nullable()
                ->unique()
                ->after('payment_merchant_external_id');

            $table->string('payment_external_id', 100)
                ->nullable()
                ->unique()
                ->after('payment_merchant_external_id');

            $table->text('payment_redirect_url')
                ->nullable()
                ->after('payment_external_id');

            $table->text('payment_error')
                ->nullable()
                ->after('payment_redirect_url');

            $table->timestamp('paid_at')
                ->nullable()
                ->index()
                ->after('placed_at');

            $table->timestamp('payment_failed_at')
                ->nullable()
                ->after('paid_at');
        });

        Schema::create('sales_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->string('type', 40)->index();
            $table->string('number', 60)->unique();
            $table->char('currency', 3);

            $table->decimal('subtotal_gross', 12, 2);
            $table->decimal('shipping_gross', 12, 2);
            $table->decimal('total_gross', 12, 2);

            $table->string('buyer_name', 255);
            $table->string('buyer_email', 255);
            $table->string('billing_company', 180)->nullable();
            $table->string('billing_tax_id', 40)->nullable();
            $table->text('billing_address');

            $table->timestamp('issued_at')->index();
            $table->timestamps();

            $table->index(['order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_documents');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['payment_merchant_external_id']);
            $table->dropUnique(['payment_idempotency_key']);
            $table->dropUnique(['payment_external_id']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['paid_at']);

            $table->dropColumn([
                'shipping_method',
                'shipping_name_snapshot',
                'shipping_point',
                'payment_method',
                'payment_status',
                'payment_merchant_external_id',
                'payment_idempotency_key',
                'payment_external_id',
                'payment_redirect_url',
                'payment_error',
                'paid_at',
                'payment_failed_at',
            ]);
        });
    }
};
