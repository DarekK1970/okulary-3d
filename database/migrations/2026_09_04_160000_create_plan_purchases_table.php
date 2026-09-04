<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_purchases', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_token')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan', 20);
            $table->unsignedTinyInteger('duration_months')->default(3);
            $table->unsignedInteger('token_lens');
            $table->decimal('price', 10, 2);
            $table->char('currency', 3)->default('PLN');
            $table->boolean('auto_renew')->default(true);
            $table->string('status', 20)->default('unpaid');
            $table->string('payment_merchant_external_id')->nullable()->unique();
            $table->uuid('payment_idempotency_key')->nullable();
            $table->string('payment_external_id')->nullable()->index();
            $table->text('payment_redirect_url')->nullable();
            $table->text('payment_error')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_purchases');
    }
};
