<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable(
                'portal_analytics_bot_requests'
            )
        ) {
            return;
        }

        Schema::create(
            'portal_analytics_bot_requests',
            function (Blueprint $table): void {
                $table->id();

                $table->string(
                    'bot_name',
                    120
                )->index();

                $table->string(
                    'category',
                    40
                )->default('other')
                    ->index();

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
                    'method',
                    10
                );

                $table->unsignedSmallInteger(
                    'status_code'
                );

                $table->string(
                    'locale',
                    10
                )->nullable()
                    ->index();

                /*
                 * The raw User-Agent is deliberately NOT stored.
                 * The hash lets us troubleshoot repeated automated
                 * clients without collecting extra raw request data.
                 */
                $table->char(
                    'user_agent_hash',
                    64
                )->nullable()
                    ->index();

                $table->timestamp(
                    'occurred_at'
                )->index();

                $table->index(
                    [
                        'category',
                        'occurred_at',
                    ],
                    'portal_bot_category_time_idx'
                );

                $table->index(
                    [
                        'bot_name',
                        'occurred_at',
                    ],
                    'portal_bot_name_time_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'portal_analytics_bot_requests'
        );
    }
};
