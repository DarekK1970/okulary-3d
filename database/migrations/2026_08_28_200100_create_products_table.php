<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('product_categories')
                ->restrictOnDelete();
            $table->string('source_locale', 10)->default('pl')->index();
            $table->string('status', 24)->default('draft')->index();
            $table->string('brand', 120)->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category_id', 'status']);
        });

        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 220);
            $table->string('slug', 240);
            $table->text('short_description')->nullable();
            $table->longText('description_html');
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 180)->nullable();
            $table->string('translation_status', 24)->default('draft')->index();
            $table->timestamps();

            $table->unique(['product_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_translations');
        Schema::dropIfExists('products');
    }
};
