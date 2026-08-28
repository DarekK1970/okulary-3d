<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'ai_translation_runs',
            function (Blueprint $table) {
                $table->id();
                $table->string('content_type', 60)->index();
                $table->unsignedBigInteger('content_id')->index();
                $table->string('source_locale', 5);
                $table->string('target_locale', 5);
                $table->string('provider', 30)->index();
                $table->string('model', 120);
                $table->string('status', 30)->index();
                $table->unsignedInteger('input_tokens')->nullable();
                $table->unsignedInteger('output_tokens')->nullable();
                $table->unsignedInteger('total_tokens')->nullable();
                $table->unsignedInteger('request_chars')->nullable();
                $table->unsignedInteger('response_chars')->nullable();
                $table->text('error_message')->nullable();
                $table->foreignId('initiated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamps();

                $table->index([
                    'content_type',
                    'content_id',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_translation_runs'
        );
    }
};
