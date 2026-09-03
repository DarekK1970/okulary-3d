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
        $slugs = [
            'stereoskopia-historia-i-archiwum',
            'stereoskopia-historia-archiwum',
        ];

        foreach (DB::table('article_categories')->get(['id', 'name', 'slug']) as $category) {
            if (in_array($category->slug, $slugs, true)
                || in_array(Str::slug($category->name), $slugs, true)) {
                DB::table('article_categories')->where('id', $category->id)->update([
                    'portal_section' => 'history-curiosities',
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
