<?php

use App\Enums\ArticleTranslationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('source_locale', 10)
                ->default(config('locales.default', 'pl'))
                ->index();
        });

        Schema::create('article_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('article_id')
                ->constrained('articles')
                ->cascadeOnDelete();

            $table->string('locale', 10);
            $table->string('title', 220);
            $table->string('slug', 240);
            $table->text('excerpt')->nullable();
            $table->longText('body_html');
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 180)->nullable();
            $table->string('translation_status', 24)
                ->default(ArticleTranslationStatus::Draft->value)
                ->index();

            $table->timestamps();

            $table->unique(['article_id', 'locale']);
            $table->unique(['locale', 'slug']);
            $table->index(['locale', 'translation_status']);
        });

        $defaultLocale = config('locales.default', 'pl');

        DB::table('articles')
            ->orderBy('id')
            ->chunkById(100, function ($articles) use ($defaultLocale): void {
                $now = now();

                $rows = [];

                foreach ($articles as $article) {
                    $rows[] = [
                        'article_id' => $article->id,
                        'locale' => $defaultLocale,
                        'title' => $article->title,
                        'slug' => $article->slug,
                        'excerpt' => $article->excerpt,
                        'body_html' => $article->body_html,
                        'seo_title' => null,
                        'seo_description' => $article->excerpt
                            ? mb_substr(strip_tags($article->excerpt), 0, 180)
                            : null,
                        'translation_status' => ArticleTranslationStatus::Source->value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('article_translations')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_translations');

        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['source_locale']);
            $table->dropColumn('source_locale');
        });
    }
};
