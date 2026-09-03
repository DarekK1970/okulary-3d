<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('processing_machines', function (Blueprint $table): void {
            $table->id();
            $table->string('machine_id')->unique();
            $table->string('api_key_id')->unique();
            $table->text('api_secret');
            $table->json('capabilities')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('processing_machine_nonces', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('processing_machine_id')->constrained()->cascadeOnDelete();
            $table->string('nonce', 64);
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->unique(['processing_machine_id', 'nonce']);
        });

        Schema::create('lenticular_projects', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('status')->default('draft')->index();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('lenticular_project_files', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('lenticular_project_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('media_type')->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('lenticular_jobs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('lenticular_project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('source_file_id')->nullable()->constrained('lenticular_project_files')->nullOnDelete();
            $table->foreignId('processing_machine_id')->nullable()->constrained()->nullOnDelete();
            $table->string('operation')->index();
            $table->string('status')->default('queued')->index();
            $table->unsignedSmallInteger('priority')->default(100)->index();
            $table->json('parameters');
            $table->string('lease_token', 64)->nullable();
            $table->timestamp('lease_expires_at')->nullable()->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('stage')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lenticular_job_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('lenticular_job_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('lenticular_artifacts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('lenticular_job_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('media_type');
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->timestamps();
            $table->unique(['lenticular_job_id', 'kind']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lenticular_artifacts');
        Schema::dropIfExists('lenticular_job_events');
        Schema::dropIfExists('lenticular_jobs');
        Schema::dropIfExists('lenticular_project_files');
        Schema::dropIfExists('lenticular_projects');
        Schema::dropIfExists('processing_machine_nonces');
        Schema::dropIfExists('processing_machines');
    }
};
