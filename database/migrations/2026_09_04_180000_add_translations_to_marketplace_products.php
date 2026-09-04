<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('marketplace_products', 'source_locale')) {
            Schema::table('marketplace_products', function (Blueprint $table): void {
                $table->string('source_locale', 10)->default('pl')->after('marketplace_category_id')->index();
            });
        }

        if (! Schema::hasTable('marketplace_product_translations')) {
            Schema::create('marketplace_product_translations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('marketplace_product_id');
                $table->string('locale', 10);
                $table->string('name', 180);
                $table->string('slug', 200);
                $table->string('short_description', 500);
                $table->text('description');
                $table->string('translation_status', 24)->default('draft')->index();
                $table->timestamps();
                $table->unique(['marketplace_product_id', 'locale'], 'marketplace_product_locale_unique');
                $table->unique(['locale', 'slug'], 'marketplace_product_locale_slug_unique');
                $table->foreign('marketplace_product_id', 'marketplace_product_translation_fk')
                    ->references('id')->on('marketplace_products')->cascadeOnDelete();
            });
        }

        $now = now();
        DB::table('marketplace_products')->orderBy('id')->each(function ($product) use ($now): void {
            DB::table('marketplace_product_translations')->insertOrIgnore([
                'marketplace_product_id' => $product->id,
                'locale' => 'pl',
                'name' => $product->name,
                'slug' => $product->slug,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'translation_status' => 'source',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_product_translations');
        if (Schema::hasColumn('marketplace_products', 'source_locale')) {
            Schema::table('marketplace_products', function (Blueprint $table): void {
                $table->dropColumn('source_locale');
            });
        }
    }
};
