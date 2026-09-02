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


    public function storeGeneratedImage(
        string $bytes,
        User $user,
        string $originalName,
        ?string $title = null,
        ?string $altText = null,
        string $folder = 'ai-generated'
    ): MediaAsset {
        if ($bytes === '') {
            throw new \RuntimeException(
                'Generated image data is empty.'
            );
        }

        $dimensions = @getimagesizefromstring(
            $bytes
        );

        if (! is_array($dimensions)) {
            throw new \RuntimeException(
                'Generated data is not a valid image.'
            );
        }

        $mimeType = strtolower(
            (string) (
                $dimensions['mime']
                ?? 'image/png'
            )
        );

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png',
        };

        $storedName =
            (string) Str::uuid()
            . '.'
            . $extension;

        $path =
            'media/'
            . now()->format('Y/m')
            . '/'
            . $storedName;

        $stored = Storage::disk(
            'public'
        )->put(
            $path,
            $bytes
        );

        if (! $stored) {
            throw new \RuntimeException(
                'Unable to store generated image.'
            );
        }

        try {
            return MediaAsset::create([
                'disk' => 'public',
                'path' => $path,
                'original_name' =>
                    Str::limit(
                        $originalName,
                        255,
                        ''
                    ),
                'stored_name' =>
                    $storedName,
                'mime_type' =>
                    $mimeType,
                'extension' =>
                    $extension,
                'size_bytes' =>
                    strlen($bytes),
                'width' =>
                    (int) (
                        $dimensions[0]
                        ?? 0
                    ) ?: null,
                'height' =>
                    (int) (
                        $dimensions[1]
                        ?? 0
                    ) ?: null,
                'title' =>
                    filled($title)
                        ? Str::limit(
                            (string) $title,
                            180,
                            ''
                        )
                        : null,
                'alt_text' =>
                    filled($altText)
                        ? Str::limit(
                            (string) $altText,
                            255,
                            ''
                        )
                        : null,
                'caption' => null,
                'folder' =>
                    $this->normalizeFolder(
                        $folder
                    ),
                'uploaded_by' =>
                    $user->id,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk(
                'public'
            )->delete($path);

            throw $exception;
        }
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
