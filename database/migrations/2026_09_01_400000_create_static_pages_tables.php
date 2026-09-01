<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('static_pages')) {
            Schema::create(
                'static_pages',
                function (Blueprint $table): void {
                    $table->id();
                    $table->string(
                        'key',
                        100
                    )->unique();
                    $table->string(
                        'group',
                        40
                    )->default('content');
                    $table->string(
                        'source_locale',
                        10
                    )->default('pl');
                    $table->boolean(
                        'is_active'
                    )->default(true);
                    $table->unsignedInteger(
                        'sort_order'
                    )->default(0);
                    $table->timestamps();

                    $table->index(
                        [
                            'group',
                            'sort_order',
                        ],
                        'static_pages_group_sort_idx'
                    );
                }
            );
        }

        if (
            ! Schema::hasTable(
                'static_page_translations'
            )
        ) {
            Schema::create(
                'static_page_translations',
                function (Blueprint $table): void {
                    $table->id();

                    $table->foreignId(
                        'static_page_id'
                    )
                        ->constrained(
                            'static_pages'
                        )
                        ->cascadeOnDelete();

                    $table->string(
                        'locale',
                        10
                    );

                    $table->string(
                        'title',
                        220
                    );

                    $table->mediumText(
                        'body_html'
                    )->nullable();

                    $table->string(
                        'seo_title',
                        180
                    )->nullable();

                    $table->string(
                        'seo_description',
                        320
                    )->nullable();

                    $table->string(
                        'translation_status',
                        30
                    )->default('draft');

                    $table->timestamps();

                    $table->unique(
                        [
                            'static_page_id',
                            'locale',
                        ],
                        'static_page_locale_unique'
                    );

                    $table->index(
                        [
                            'locale',
                            'translation_status',
                        ],
                        'static_page_locale_status_idx'
                    );
                }
            );
        }

        $pages = [
            [
                'key' => 'faq',
                'group' => 'content',
                'title' => 'FAQ',
                'sort_order' => 10,
            ],
            [
                'key' => 'shipping-payments',
                'group' => 'content',
                'title' => 'Wysyłka i płatności',
                'sort_order' => 20,
            ],
            [
                'key' => 'returns-complaints',
                'group' => 'content',
                'title' => 'Zwroty i reklamacje',
                'sort_order' => 30,
            ],
            [
                'key' => 'privacy-policy',
                'group' => 'content',
                'title' => 'Polityka prywatności',
                'sort_order' => 40,
            ],
            [
                'key' => 'portal-terms',
                'group' => 'content',
                'title' => 'Regulamin portalu',
                'sort_order' => 50,
            ],
            [
                'key' => 'shop-terms',
                'group' => 'shop',
                'title' => 'Regulamin sklepu',
                'sort_order' => 10,
            ],
            [
                'key' => 'secure-payments',
                'group' => 'shop',
                'title' => 'Bezpieczne płatności',
                'sort_order' => 20,
            ],
        ];

        foreach ($pages as $page) {
            $existing = DB::table(
                'static_pages'
            )
                ->where(
                    'key',
                    $page['key']
                )
                ->first();

            if (! $existing) {
                $pageId = DB::table(
                    'static_pages'
                )->insertGetId([
                    'key' =>
                        $page['key'],
                    'group' =>
                        $page['group'],
                    'source_locale' =>
                        'pl',
                    'is_active' =>
                        true,
                    'sort_order' =>
                        $page['sort_order'],
                    'created_at' =>
                        now(),
                    'updated_at' =>
                        now(),
                ]);
            } else {
                $pageId = $existing->id;

                DB::table(
                    'static_pages'
                )
                    ->where(
                        'id',
                        $pageId
                    )
                    ->update([
                        'group' =>
                            $page['group'],
                        'sort_order' =>
                            $page['sort_order'],
                        'updated_at' =>
                            now(),
                    ]);
            }

            $sourceExists = DB::table(
                'static_page_translations'
            )
                ->where(
                    'static_page_id',
                    $pageId
                )
                ->where(
                    'locale',
                    'pl'
                )
                ->exists();

            if (! $sourceExists) {
                DB::table(
                    'static_page_translations'
                )->insert([
                    'static_page_id' =>
                        $pageId,
                    'locale' => 'pl',
                    'title' =>
                        $page['title'],
                    'body_html' =>
                        null,
                    'seo_title' =>
                        $page['title'],
                    'seo_description' =>
                        null,
                    'translation_status' =>
                        'source',
                    'created_at' =>
                        now(),
                    'updated_at' =>
                        now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'static_page_translations'
        );

        Schema::dropIfExists(
            'static_pages'
        );
    }
};
