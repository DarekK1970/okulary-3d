<?php

use App\Enums\ContextRecommendationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'articles',
            function (Blueprint $table) {
                $table->boolean(
                    'recommendation_auto'
                )
                    ->default(true)
                    ->after('published_at');
            }
        );

        Schema::create(
            'article_context_recommendations',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId(
                    'article_id'
                )
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string(
                    'target_type',
                    20
                )->index();

                $table->string(
                    'tool_key',
                    80
                )->nullable();

                $table->foreignId(
                    'product_id'
                )
                    ->nullable()
                    ->constrained('products')
                    ->cascadeOnDelete();

                $table->unsignedSmallInteger(
                    'position'
                )->default(1);

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->foreignId(
                    'created_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->index([
                    'article_id',
                    'target_type',
                    'position',
                ]);

                $table->unique(
                    [
                        'article_id',
                        'target_type',
                        'tool_key',
                    ],
                    'article_context_tool_unique'
                );

                $table->unique(
                    [
                        'article_id',
                        'product_id',
                    ],
                    'article_context_product_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'article_context_recommendations'
        );

        Schema::table(
            'articles',
            function (Blueprint $table) {
                $table->dropColumn(
                    'recommendation_auto'
                );
            }
        );
    }
};
