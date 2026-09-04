<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_lens_grants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 40)->index();
            $table->unsignedInteger('amount');
            $table->timestamp('effective_at')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('token_lens_transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('token_lens_grant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40)->index();
            $table->integer('amount');
            $table->string('idempotency_key')->unique();
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_lens_transactions');
        Schema::dropIfExists('token_lens_grants');
    }
};
