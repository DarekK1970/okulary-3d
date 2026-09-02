<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PartnerLogoService
{
    public const MAX_WIDTH = 120;

    public function store(UploadedFile $file): string
    {
        if (! function_exists('imagecreatefromstring')) {
            throw ValidationException::withMessages([
                'logo' => __('partners.validation.image_processing_unavailable'),
            ]);
        }

        $contents = file_get_contents($file->getRealPath());
        $source = $contents !== false
            ? @imagecreatefromstring($contents)
            : false;

        if ($source === false) {
            throw ValidationException::withMessages([
                'logo' => __('partners.validation.invalid_image'),
            ]);
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if ($sourceWidth < 1 || $sourceHeight < 1) {
            imagedestroy($source);

            throw ValidationException::withMessages([
                'logo' => __('partners.validation.invalid_image'),
            ]);
        }

        $targetWidth = min(self::MAX_WIDTH, $sourceWidth);
        $targetHeight = max(
            1,
            (int) round($sourceHeight * ($targetWidth / $sourceWidth))
        );

        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($target === false) {
            imagedestroy($source);

            throw ValidationException::withMessages([
                'logo' => __('partners.validation.invalid_image'),
            ]);
        }

        $mime = strtolower((string) $file->getMimeType());
        $supportsAlpha = in_array($mime, ['image/png', 'image/webp'], true);

        if ($supportsAlpha) {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
            imagefilledrectangle(
                $target,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $transparent
            );
        } else {
            $white = imagecolorallocate($target, 255, 255, 255);
            imagefilledrectangle(
                $target,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $white
            );
        }

        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        [$binary, $extension] = $this->encode($target, $mime);

        imagedestroy($target);
        imagedestroy($source);

        $path = sprintf(
            'partners/logos/%s.%s',
            Str::uuid(),
            $extension
        );

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function encode(\GdImage $image, string $mime): array
    {
        ob_start();

        if ($mime === 'image/png') {
            imagepng($image, null, 8);
            $extension = 'png';
        } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
            imagewebp($image, null, 88);
            $extension = 'webp';
        } else {
            imagejpeg($image, null, 88);
            $extension = 'jpg';
        }

        $binary = ob_get_clean();

        if (! is_string($binary) || $binary === '') {
            throw ValidationException::withMessages([
                'logo' => __('partners.validation.invalid_image'),
            ]);
        }

        return [$binary, $extension];
    }
}
