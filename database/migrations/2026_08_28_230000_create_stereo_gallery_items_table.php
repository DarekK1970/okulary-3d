<?php

use App\Enums\GalleryStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'stereo_gallery_items',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('slug', 190)
                    ->unique();

                $table->string('title', 160);
                $table->text('description')
                    ->nullable();

                $table->string('author_name', 120);

                $table->string(
                    'license',
                    40
                )->default('all_rights_reserved');

                $table->string(
                    'status',
                    30
                )->default(
                    GalleryStatus::Pending->value
                )->index();

                $table->string(
                    'left_image_path',
                    500
                );

                $table->string(
                    'right_image_path',
                    500
                );

                $table->unsignedInteger(
                    'left_width'
                )->nullable();

                $table->unsignedInteger(
                    'left_height'
                )->nullable();

                $table->unsignedInteger(
                    'right_width'
                )->nullable();

                $table->unsignedInteger(
                    'right_height'
                )->nullable();

                $table->timestamp(
                    'rights_confirmed_at'
                );

                $table->timestamp(
                    'published_at'
                )->nullable()->index();

                $table->foreignId(
                    'moderated_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp(
                    'moderated_at'
                )->nullable();

                $table->text(
                    'moderation_note'
                )->nullable();

                $table->timestamps();

                $table->index([
                    'user_id',
                    'status',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'stereo_gallery_items'
        );
    }
};
