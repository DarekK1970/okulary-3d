<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('article_categories')
                ->restrictOnDelete();

            $table->string('title', 220);
            $table->string('slug', 240)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body_html');
            $table->string('hero_image_path')->nullable();

            $table->string('status', 24)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['category_id', 'status']);
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
