<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('stereo_gallery_items', 'stereo_pair_path')) {
            return;
        }

        Schema::table('stereo_gallery_items', function (Blueprint $table) {
            $table->string('stereo_pair_path', 500)
                ->nullable()
                ->after('right_image_path');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('stereo_gallery_items', 'stereo_pair_path')) {
            return;
        }

        Schema::table('stereo_gallery_items', function (Blueprint $table) {
            $table->dropColumn('stereo_pair_path');
        });
    }
};
