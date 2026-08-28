<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();

            $table->string('disk', 32)->default('public');
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('mime_type', 100)->nullable();
            $table->string('extension', 16)->nullable();

            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            $table->string('title', 180)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->text('caption')->nullable();

            $table->string('folder', 120)->default('general')->index();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['disk', 'path']);
            $table->index(['mime_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
