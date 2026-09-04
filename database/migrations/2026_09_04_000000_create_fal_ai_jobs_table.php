<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fal_ai_jobs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('lenticular_project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('source_file_id')->nullable()->constrained('lenticular_project_files')->nullOnDelete();
            $table->foreignUuid('end_file_id')->nullable()->constrained('lenticular_project_files')->nullOnDelete();
            $table->foreignUuid('result_file_id')->nullable()->constrained('lenticular_project_files')->nullOnDelete();
            $table->string('operation', 40)->index();
            $table->string('status', 30)->default('queued')->index();
            $table->uuid('idempotency_key')->unique();
            $table->string('endpoint', 180);
            $table->string('provider_request_id')->nullable()->unique();
            $table->json('parameters');
            $table->json('provider_response')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('stage')->nullable();
            $table->decimal('estimated_cost_usd', 12, 6)->nullable();
            $table->decimal('actual_cost_usd', 12, 6)->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['lenticular_project_id', 'created_at']);
        });

        Schema::create('fal_ai_job_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('fal_ai_job_id')->constrained()->cascadeOnDelete();
            $table->string('type', 60)->index();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fal_ai_job_events');
        Schema::dropIfExists('fal_ai_jobs');
    }
};
