<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONTEXT_INDEX =
        'acr_article_type_position_idx';

    private const TOOL_UNIQUE =
        'article_context_tool_unique';

    private const PRODUCT_UNIQUE =
        'article_context_product_unique';

    public function up(): void
    {
        if (
            ! Schema::hasColumn(
                'articles',
                'recommendation_auto'
            )
        ) {
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
        }

        if (
            ! Schema::hasTable(
                'article_context_recommendations'
            )
        ) {
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

                    $table->index(
                        [
                            'article_id',
                            'target_type',
                            'position',
                        ],
                        self::CONTEXT_INDEX
                    );

                    $table->unique(
                        [
                            'article_id',
                            'target_type',
                            'tool_key',
                        ],
                        self::TOOL_UNIQUE
                    );

                    $table->unique(
                        [
                            'article_id',
                            'product_id',
                        ],
                        self::PRODUCT_UNIQUE
                    );
                }
            );

            return;
        }

        /*
         * MariaDB/MySQL may leave this table partially created
         * when a later ALTER TABLE command fails. Reconcile
         * indexes that can be missing after such a failure.
         */
        if (
            ! Schema::hasIndex(
                'article_context_recommendations',
                self::CONTEXT_INDEX
            )
        ) {
            Schema::table(
                'article_context_recommendations',
                function (Blueprint $table) {
                    $table->index(
                        [
                            'article_id',
                            'target_type',
                            'position',
                        ],
                        self::CONTEXT_INDEX
                    );
                }
            );
        }

        if (
            ! Schema::hasIndex(
                'article_context_recommendations',
                self::TOOL_UNIQUE
            )
        ) {
            Schema::table(
                'article_context_recommendations',
                function (Blueprint $table) {
                    $table->unique(
                        [
                            'article_id',
                            'target_type',
                            'tool_key',
                        ],
                        self::TOOL_UNIQUE
                    );
                }
            );
        }

        if (
            ! Schema::hasIndex(
                'article_context_recommendations',
                self::PRODUCT_UNIQUE
            )
        ) {
            Schema::table(
                'article_context_recommendations',
                function (Blueprint $table) {
                    $table->unique(
                        [
                            'article_id',
                            'product_id',
                        ],
                        self::PRODUCT_UNIQUE
                    );
                }
            );
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable(
                'article_context_recommendations'
            )
        ) {
            Schema::drop(
                'article_context_recommendations'
            );
        }

        if (
            Schema::hasColumn(
                'articles',
                'recommendation_auto'
            )
        ) {
            Schema::table(
                'articles',
                function (Blueprint $table) {
                    $table->dropColumn(
                        'recommendation_auto'
                    );
                }
            );
        }
    }
};
