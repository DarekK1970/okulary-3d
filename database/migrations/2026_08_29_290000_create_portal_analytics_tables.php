<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable(
                'portal_analytics_sessions'
            )
        ) {
            Schema::create(
                'portal_analytics_sessions',
                function (Blueprint $table) {
                    $table->uuid('id')
                        ->primary();

                    $table->char(
                        'browser_session_hash',
                        64
                    )->index();

                    /*
                     * DATETIME zamiast TIMESTAMP:
                     * nie korzystamy z niejawnych defaultów
                     * TIMESTAMP zależnych od konfiguracji
                     * MySQL/MariaDB.
                     */
                    $table->dateTime(
                        'started_at'
                    )->index();

                    $table->dateTime(
                        'last_seen_at'
                    )->index();

                    $table->string(
                        'landing_path',
                        500
                    );

                    $table->string(
                        'landing_locale',
                        5
                    )->nullable()
                        ->index();

                    $table->string(
                        'source_group',
                        30
                    )->default('direct')
                        ->index();

                    $table->string(
                        'source_name',
                        190
                    )->nullable()
                        ->index();

                    $table->string(
                        'referrer_domain',
                        190
                    )->nullable()
                        ->index();

                    $table->string(
                        'utm_source',
                        190
                    )->nullable();

                    $table->string(
                        'utm_medium',
                        190
                    )->nullable();

                    $table->string(
                        'utm_campaign',
                        190
                    )->nullable();

                    $table->string(
                        'device_type',
                        30
                    )->default('other')
                        ->index();

                    $table->boolean(
                        'is_authenticated'
                    )->default(false)
                        ->index();

                    $table->unsignedInteger(
                        'pageviews_count'
                    )->default(0);

                    $table->unsignedInteger(
                        'events_count'
                    )->default(0);

                    $table->timestamps();

                    $table->index([
                        'source_group',
                        'started_at',
                    ]);

                    $table->index([
                        'device_type',
                        'started_at',
                    ]);
                }
            );
        }

        if (
            ! Schema::hasTable(
                'portal_analytics_page_views'
            )
        ) {
            Schema::create(
                'portal_analytics_page_views',
                function (Blueprint $table) {
                    $table->id();

                    $table->uuid(
                        'analytics_session_id'
                    );

                    $table->string(
                        'route_name',
                        160
                    )->nullable()
                        ->index();

                    $table->string(
                        'path',
                        500
                    )->index();

                    $table->string(
                        'locale',
                        5
                    )->nullable()
                        ->index();

                    $table->string(
                        'page_type',
                        40
                    )->default('other')
                        ->index();

                    $table->string(
                        'referrer_domain',
                        190
                    )->nullable();

                    $table->dateTime(
                        'occurred_at'
                    )->index();

                    $table->foreign(
                        'analytics_session_id'
                    )
                        ->references('id')
                        ->on(
                            'portal_analytics_sessions'
                        )
                        ->cascadeOnDelete();

                    $table->index([
                        'page_type',
                        'occurred_at',
                    ]);

                    $table->index([
                        'route_name',
                        'occurred_at',
                    ]);
                }
            );
        }

        if (
            ! Schema::hasTable(
                'portal_analytics_events'
            )
        ) {
            Schema::create(
                'portal_analytics_events',
                function (Blueprint $table) {
                    $table->id();

                    $table->uuid(
                        'analytics_session_id'
                    );

                    $table->string(
                        'event_name',
                        80
                    )->index();

                    $table->string(
                        'category',
                        80
                    )->nullable()
                        ->index();

                    $table->string(
                        'label',
                        255
                    )->nullable();

                    $table->decimal(
                        'value',
                        14,
                        2
                    )->nullable();

                    $table->string(
                        'route_name',
                        160
                    )->nullable()
                        ->index();

                    $table->string(
                        'path',
                        500
                    )->nullable();

                    $table->string(
                        'locale',
                        5
                    )->nullable()
                        ->index();

                    $table->json(
                        'metadata'
                    )->nullable();

                    $table->dateTime(
                        'occurred_at'
                    )->index();

                    $table->foreign(
                        'analytics_session_id'
                    )
                        ->references('id')
                        ->on(
                            'portal_analytics_sessions'
                        )
                        ->cascadeOnDelete();

                    $table->index([
                        'event_name',
                        'occurred_at',
                    ]);

                    $table->index([
                        'category',
                        'occurred_at',
                    ]);
                }
            );
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable(
                'portal_analytics_events'
            )
        ) {
            Schema::drop(
                'portal_analytics_events'
            );
        }

        if (
            Schema::hasTable(
                'portal_analytics_page_views'
            )
        ) {
            Schema::drop(
                'portal_analytics_page_views'
            );
        }

        if (
            Schema::hasTable(
                'portal_analytics_sessions'
            )
        ) {
            Schema::drop(
                'portal_analytics_sessions'
            );
        }
    }
};
