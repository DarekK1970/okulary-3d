<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('hero_media_id')
                ->nullable()
                ->after('hero_image_path')
                ->constrained('media_assets')
                ->nullOnDelete();
        });

        $articles = DB::table('articles')
            ->whereNotNull('hero_image_path')
            ->where('hero_image_path', '!=', '')
            ->orderBy('id')
            ->get();

        foreach ($articles as $article) {
            $path = (string) $article->hero_image_path;
            $basename = basename($path);
            $extension = pathinfo($basename, PATHINFO_EXTENSION) ?: null;

            $mediaId = DB::table('media_assets')
                ->where('disk', 'public')
                ->where('path', $path)
                ->value('id');

            if (! $mediaId) {
                $mediaId = DB::table('media_assets')->insertGetId([
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => $basename,
                    'stored_name' => $basename,
                    'mime_type' => null,
                    'extension' => $extension,
                    'size_bytes' => null,
                    'width' => null,
                    'height' => null,
                    'title' => Str::limit((string) $article->title, 180, ''),
                    'alt_text' => Str::limit((string) $article->title, 255, ''),
                    'caption' => null,
                    'folder' => 'legacy-articles',
                    'uploaded_by' => $article->created_by,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('articles')
                ->where('id', $article->id)
                ->update([
                    'hero_media_id' => $mediaId,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['hero_media_id']);
            $table->dropColumn('hero_media_id');
        });
    }
};
