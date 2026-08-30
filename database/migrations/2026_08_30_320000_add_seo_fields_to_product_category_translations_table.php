<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasContent = Schema::hasColumn(
            'product_category_translations',
            'content_html'
        );
        $hasSeoTitle = Schema::hasColumn(
            'product_category_translations',
            'seo_title'
        );
        $hasSeoDescription = Schema::hasColumn(
            'product_category_translations',
            'seo_description'
        );

        if ($hasContent && $hasSeoTitle && $hasSeoDescription) {
            return;
        }

        Schema::table(
            'product_category_translations',
            function (Blueprint $table) use (
                $hasContent,
                $hasSeoTitle,
                $hasSeoDescription
            ): void {
                if (! $hasContent) {
                    $table->mediumText('content_html')->nullable();
                }

                if (! $hasSeoTitle) {
                    $table->string('seo_title', 180)->nullable();
                }

                if (! $hasSeoDescription) {
                    $table->string('seo_description', 320)->nullable();
                }
            }
        );
    }

    public function down(): void
    {
        $columns = collect([
            'content_html',
            'seo_title',
            'seo_description',
        ])->filter(
            fn (string $column): bool => Schema::hasColumn(
                'product_category_translations',
                $column
            )
        )->all();

        if ($columns === []) {
            return;
        }

        Schema::table(
            'product_category_translations',
            fn (Blueprint $table) => $table->dropColumn($columns)
        );
    }
};
