<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stereo_gallery_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stereo_gallery_item_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->timestamps();

            $table->unique([
                'stereo_gallery_item_id',
                'user_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stereo_gallery_ratings');
    }
};
