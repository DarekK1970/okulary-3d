<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $slug = 'techniki-i-technologie-3d';

        foreach (DB::table('article_categories')->get(['id', 'name', 'slug']) as $category) {
            if ($category->slug === $slug || Str::slug($category->name) === $slug) {
                DB::table('article_categories')->where('id', $category->id)->update([
                    'portal_section' => 'techniques',
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep the corrected editorial placement: the previous value is unknown.
    }
};
