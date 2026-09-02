<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_categories', function (Blueprint $table): void {
            $table->string('portal_section', 40)
                ->default('articles')
                ->index();
        });

        $this->assignSection(
            slug: 'ciekawostki-historyczne',
            name: 'Ciekawostki historyczne',
            section: 'history-curiosities'
        );

        $this->assignSection(
            slug: 'techniki-3d',
            name: 'Techniki 3D',
            section: 'techniques'
        );
    }

    public function down(): void
    {
        Schema::table('article_categories', function (Blueprint $table): void {
            $table->dropIndex(['portal_section']);
            $table->dropColumn('portal_section');
        });
    }

    private function assignSection(
        string $slug,
        string $name,
        string $section
    ): void {
        $existing = DB::table('article_categories')
            ->where('slug', $slug)
            ->orWhere('name', $name)
            ->first();

        if (! $existing) {
            return;
        }

        DB::table('article_categories')
            ->where('id', $existing->id)
            ->update([
                'portal_section' => $section,
                'updated_at' => now(),
            ]);
    }
};
