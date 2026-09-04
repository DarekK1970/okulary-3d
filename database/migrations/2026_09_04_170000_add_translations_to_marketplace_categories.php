<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_categories', function (Blueprint $table): void {
            $table->string('source_locale', 10)->default('pl')->after('id')->index();
        });

        Schema::create('marketplace_category_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketplace_category_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 150);
            $table->string('slug', 170);
            $table->text('description')->nullable();
            $table->string('translation_status', 24)->default('draft')->index();
            $table->timestamps();
            $table->unique(['marketplace_category_id', 'locale'], 'marketplace_category_locale_unique');
            $table->unique(['locale', 'slug'], 'marketplace_category_locale_slug_unique');
        });

        $now = now();
        DB::table('marketplace_categories')->orderBy('id')->each(function ($category) use ($now): void {
            DB::table('marketplace_category_translations')->insert([
                'marketplace_category_id' => $category->id,
                'locale' => 'pl',
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'translation_status' => 'source',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_category_translations');
        Schema::table('marketplace_categories', function (Blueprint $table): void {
            $table->dropColumn('source_locale');
        });
    }
};
