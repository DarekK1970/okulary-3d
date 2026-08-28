<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaAssetService
{
    public function storeImage(
        UploadedFile $file,
        User $user,
        string $folder = 'general'
    ): MediaAsset {
        $path = $file->store(
            'media/' . now()->format('Y/m'),
            'public'
        );

        $width = null;
        $height = null;

        try {
            $absolutePath = Storage::disk('public')->path($path);
            $dimensions = @getimagesize($absolutePath);

            if (is_array($dimensions)) {
                $width = $dimensions[0] ?? null;
                $height = $dimensions[1] ?? null;
            }
        } catch (\Throwable) {
            // Metadata is useful, but failure to read dimensions must not
            // block a valid uploaded image.
        }

        $originalName = $file->getClientOriginalName();
        $extension = strtolower(
            $file->getClientOriginalExtension()
                ?: pathinfo($path, PATHINFO_EXTENSION)
        );

        return MediaAsset::create([
            'disk' => 'public',
            'path' => $path,
            'original_name' => $originalName,
            'stored_name' => basename($path),
            'mime_type' => $file->getMimeType(),
            'extension' => $extension ?: null,
            'size_bytes' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'title' => Str::limit(
                pathinfo($originalName, PATHINFO_FILENAME),
                180,
                ''
            ),
            'alt_text' => null,
            'caption' => null,
            'folder' => $this->normalizeFolder($folder),
            'uploaded_by' => $user->id,
        ]);
    }

    public function delete(MediaAsset $media): void
    {
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }

    public function normalizeFolder(?string $folder): string
    {
        $folder = trim((string) $folder);

        if ($folder === '') {
            return 'general';
        }

        return Str::limit(
            preg_replace('/\s+/u', ' ', $folder) ?: 'general',
            120,
            ''
        );
    }
}
