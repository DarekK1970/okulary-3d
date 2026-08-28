<?php

use App\Enums\ArchiveTranslationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'archive_items',
            function (Blueprint $table) {
                $table->id();

                $table->string(
                    'source_locale',
                    5
                )->default('pl');

                $table->string(
                    'technique',
                    50
                )->index();

                $table->unsignedSmallInteger(
                    'year_from'
                )->index();

                $table->unsignedSmallInteger(
                    'year_to'
                )->nullable();

                $table->boolean(
                    'circa'
                )->default(false);

                $table->string(
                    'creator',
                    190
                )->nullable();

                $table->string(
                    'publisher',
                    190
                )->nullable();

                $table->string(
                    'country',
                    120
                )->nullable()->index();

                $table->string(
                    'collection_name',
                    190
                )->nullable()->index();

                $table->string(
                    'source_name',
                    190
                );

                $table->string(
                    'source_url',
                    1000
                )->nullable();

                $table->string(
                    'rights_status',
                    50
                )->index();

                $table->text(
                    'rights_note'
                )->nullable();

                $table->string(
                    'original_image_path',
                    500
                );

                $table->string(
                    'left_image_path',
                    500
                )->nullable();

                $table->string(
                    'right_image_path',
                    500
                )->nullable();

                $table->unsignedInteger(
                    'original_width'
                )->nullable();

                $table->unsignedInteger(
                    'original_height'
                )->nullable();

                $table->boolean(
                    'is_published'
                )->default(false)->index();

                $table->timestamp(
                    'published_at'
                )->nullable()->index();

                $table->foreignId(
                    'created_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId(
                    'updated_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->index([
                    'is_published',
                    'technique',
                    'year_from',
                ]);
            }
        );

        Schema::create(
            'archive_item_translations',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId(
                    'archive_item_id'
                )
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string(
                    'locale',
                    5
                );

                $table->string(
                    'title',
                    220
                );

                $table->string(
                    'slug',
                    220
                );

                $table->text(
                    'description'
                )->nullable();

                $table->longText(
                    'historical_note'
                )->nullable();

                $table->string(
                    'seo_title',
                    255
                )->nullable();

                $table->string(
                    'seo_description',
                    500
                )->nullable();

                $table->string(
                    'translation_status',
                    30
                )->default(
                    ArchiveTranslationStatus::Draft->value
                )->index();

                $table->timestamps();

                $table->unique([
                    'archive_item_id',
                    'locale',
                ]);

                $table->unique([
                    'locale',
                    'slug',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'archive_item_translations'
        );

        Schema::dropIfExists(
            'archive_items'
        );
    }
};
