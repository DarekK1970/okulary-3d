<?php

namespace App\Http\Controllers;

use App\Enums\GalleryStatus;
use App\Models\StereoGalleryItem;
use App\Models\StereoGalleryRating;
use App\Services\SeoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StereoGalleryController extends Controller
{
    private const MAX_FRAME_DIMENSION = 1920;

    private const MIN_FRAME_DIMENSION = 260;

    private const JPEG_QUALITY = 88;

    public function index(
        Request $request,
        string $locale
    ): View {
        $items = $this->publishedGalleryQuery($request)
            ->get();
        $requestedPhoto = $request->string('photo')->toString();

        return view(
            'gallery.index',
            [
                'items' => $items,
                'currentGalleryItem' => $items->first(
                    fn (StereoGalleryItem $item): bool => $item->slug === $requestedPhoto
                ) ?? $items->first(),
            ]
        );
    }

    public function show(
        Request $request,
        string $locale,
        StereoGalleryItem $galleryItem,
        SeoService $seo
    ): View {
        abort_unless(
            $galleryItem->status
                === GalleryStatus::Published
            && $galleryItem->published_at,
            404
        );

        $items = $this->publishedGalleryQuery($request)
            ->get();
        $currentGalleryItem = $items->first(
            fn (StereoGalleryItem $item): bool => $item->is($galleryItem)
        ) ?? $galleryItem
            ->loadCount('ratings')
            ->loadAvg('ratings', 'rating');

        return view(
            'gallery.show',
            [
                'galleryItem' => $currentGalleryItem,
                'items' => $items,
                'currentGalleryItem' => $currentGalleryItem,
                'pageSeo' => $seo->gallery(
                    $galleryItem,
                    $locale
                ),
            ]
        );
    }

    public function rate(
        Request $request,
        string $locale,
        StereoGalleryItem $galleryItem
    ): JsonResponse|RedirectResponse {
        abort_unless(
            $galleryItem->status === GalleryStatus::Published
            && $galleryItem->published_at,
            404
        );

        $validated = $request->validate([
            'rating' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],
        ]);

        $alreadyRated = StereoGalleryRating::query()
            ->where('stereo_gallery_item_id', $galleryItem->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($alreadyRated) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('gallery.rating.already_rated'),
                ], 409);
            }

            return back()->withErrors([
                'rating' => __('gallery.rating.already_rated'),
            ]);
        }

        StereoGalleryRating::create([
            'stereo_gallery_item_id' => $galleryItem->id,
            'user_id' => $request->user()->id,
            'rating' => (int) $validated['rating'],
        ]);

        $galleryItem->loadCount('ratings')
            ->loadAvg('ratings', 'rating');

        if ($request->expectsJson()) {
            return response()->json([
                'count' => $galleryItem->ratingCount(),
                'average' => $galleryItem->ratingAverage(),
                'summary' => $galleryItem->ratingSummary(),
            ]);
        }

        return back();
    }

    public function create(
        string $locale
    ): View {
        return view('gallery.create');
    }

    public function store(
        Request $request,
        string $locale
    ): RedirectResponse {
        $submissionType = $request->input(
            'submission_type'
        );

        if (blank($submissionType)) {
            $submissionType = $request->hasFile('left_image')
                || $request->hasFile('right_image')
                ? 'left_right'
                : 'stereo_pair';
        }

        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:160',
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'author_name' => [
                'required',
                'string',
                'max:120',
            ],
            'license' => [
                'required',
                Rule::in([
                    'all_rights_reserved',
                    'cc_by',
                    'cc_by_sa',
                    'cc0',
                ]),
            ],
            'submission_type' => [
                'nullable',
                Rule::in([
                    'stereo_pair',
                    'mpo',
                    'left_right',
                ]),
            ],
            'source_image' => [
                Rule::requiredIf(
                    ! in_array(
                        $submissionType,
                        ['left_right'],
                        true
                    )
                ),
                'file',
                'mimetypes:image/jpeg,image/png,image/webp',
                'extensions:jpeg,jpg,png,webp,mpo',
                'max:10240',
            ],
            'left_image' => [
                Rule::requiredIf(
                    $submissionType === 'left_right'
                ),
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:10240',
            ],
            'right_image' => [
                Rule::requiredIf(
                    $submissionType === 'left_right'
                ),
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:10240',
            ],
            'rights_confirmation' => [
                'accepted',
            ],
        ]);

        $user = $request->user();
        $folder = sprintf(
            'gallery/%d/%s',
            $user->id,
            Str::uuid()
        );

        if ($submissionType === 'left_right') {
            $left = $request->file('left_image');
            $right = $request->file('right_image');

            [$leftImage, $leftWidth, $leftHeight] =
                $this->optimizeUploadedFrame(
                    $left,
                    'left_image'
                );

            [$rightImage, $rightWidth, $rightHeight] =
                $this->optimizeUploadedFrame(
                    $right,
                    'right_image'
                );

            $leftPath = $this->storeGeneratedImage(
                $leftImage,
                $folder,
                'left'
            );

            try {
                $rightPath = $this->storeGeneratedImage(
                    $rightImage,
                    $folder,
                    'right'
                );
            } catch (\Throwable $exception) {
                Storage::disk('public')
                    ->delete($leftPath);

                throw $exception;
            }

            $stereoImage = $this->makeStereoPairFromPair(
                $leftImage,
                $rightImage,
                'left_image'
            );

            $stereoPath = $this->storeGeneratedImage(
                $stereoImage,
                $folder,
                'stereo_pair'
            );
        } else {
            $source = $request->file('source_image');

            [
                $leftPath,
                $rightPath,
                $stereoPath,
                $leftWidth,
                $leftHeight,
                $rightWidth,
                $rightHeight,
            ] = $this->storeNormalizedStereoPair(
                $source,
                $folder,
                $submissionType
            );
        }

        $galleryItem =
            StereoGalleryItem::create([
                'user_id' => $user->id,
                'slug' => $this->uniqueSlug(
                    $validated['title']
                ),
                'title' => $validated['title'],
                'description' => $validated['description']
                    ?? null,
                'author_name' => $validated['author_name'],
                'license' => $validated['license'],
                'status' => GalleryStatus::Pending,
                'left_image_path' => $leftPath,
                'right_image_path' => $rightPath,
                'stereo_pair_path' => $stereoPath,
                'left_width' => $leftWidth,
                'left_height' => $leftHeight,
                'right_width' => $rightWidth,
                'right_height' => $rightHeight,
                'rights_confirmed_at' => now(),
            ]);

        return redirect()
            ->route(
                'account.gallery.index',
                ['locale' => $locale]
            )
            ->with(
                'status',
                __('gallery.messages.submitted')
            );
    }

    public function accountIndex(
        Request $request,
        string $locale
    ): View {
        $items = StereoGalleryItem::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->latest()
            ->paginate(20);

        return view(
            'account.gallery.index',
            compact('items')
        );
    }

    public function destroy(
        Request $request,
        string $locale,
        StereoGalleryItem $galleryItem
    ): RedirectResponse {
        abort_unless(
            $galleryItem->canBeDeletedBy(
                $request->user()
            ),
            403
        );

        Storage::disk('public')
            ->delete(
                array_filter([
                    $galleryItem
                        ->left_image_path,
                    $galleryItem
                        ->right_image_path,
                    $galleryItem
                        ->stereo_pair_path,
                ])
            );

        $galleryItem->delete();

        return back()->with(
            'status',
            __('gallery.messages.deleted')
        );
    }

    private function storeNormalizedStereoPair(
        UploadedFile $source,
        string $folder,
        string $submissionType
    ): array {
        [$leftImage, $rightImage] =
            $this->buildStereoPairImages(
                $source,
                $submissionType
            );

        [$leftImage, $leftWidth, $leftHeight] =
            $this->optimizeFrameFile(
                $leftImage,
                'source_image'
            );

        [$rightImage, $rightWidth, $rightHeight] =
            $this->optimizeFrameFile(
                $rightImage,
                'source_image'
            );

        $stereoImage = $this->makeStereoPairFromPair(
            $leftImage,
            $rightImage,
            'source_image'
        );

        $leftPath = $this->storeGeneratedImage(
            $leftImage,
            $folder,
            'left'
        );

        try {
            $rightPath = $this->storeGeneratedImage(
                $rightImage,
                $folder,
                'right'
            );
        } catch (\Throwable $exception) {
            Storage::disk('public')
                ->delete($leftPath);

            throw $exception;
        }

        $stereoPath = $this->storeGeneratedImage(
            $stereoImage,
            $folder,
            'stereo_pair'
        );

        return [
            $leftPath,
            $rightPath,
            $stereoPath,
            $leftWidth,
            $leftHeight,
            $rightWidth,
            $rightHeight,
        ];
    }

    private function buildStereoPairImages(
        UploadedFile $source,
        string $submissionType
    ): array {
        $sourcePath = $source->getRealPath();

        if ($submissionType === 'mpo') {
            $images = $this->extractMpoImages(
                $sourcePath
            );

            if (
                count($images) >= 2
            ) {
                return [
                    $this->makeImageFromBlob(
                        $images[0]
                    ),
                    $this->makeImageFromBlob(
                        $images[1]
                    ),
                ];
            }
        }

        return $this->splitSideBySideImage(
            $sourcePath
        );
    }

    private function splitSideBySideImage(
        string $path
    ): array {
        $image = $this->createImageFromFile(
            $path,
            'source_image'
        );

        $width = imagesx($image);
        $height = imagesy($image);
        $half = intdiv($width, 2);

        $left = imagecreatetruecolor(
            $half,
            $height
        );
        $right = imagecreatetruecolor(
            $half,
            $height
        );

        imagecopy(
            $left,
            $image,
            0,
            0,
            0,
            0,
            $half,
            $height
        );
        imagecopy(
            $right,
            $image,
            0,
            0,
            $half,
            0,
            $half,
            $height
        );

        $leftPath = $this->writeJpegImage(
            $left,
            'gallery-left-'
        );
        $rightPath = $this->writeJpegImage(
            $right,
            'gallery-right-'
        );

        imagedestroy($image);
        imagedestroy($left);
        imagedestroy($right);

        return [$leftPath, $rightPath];
    }

    private function extractMpoImages(
        string $path
    ): array {
        $contents = file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        $parts = [];
        $offset = 0;

        while (($start = strpos($contents, "\xFF\xD8", $offset)) !== false) {
            $end = strpos($contents, "\xFF\xD9", $start + 2);

            if ($end === false) {
                break;
            }

            $parts[] = substr(
                $contents,
                $start,
                $end - $start + 2
            );

            $offset = $end + 2;
        }

        return $parts;
    }

    private function makeImageFromBlob(
        string $blob
    ): string {
        $temp = tempnam(
            sys_get_temp_dir(),
            'gallery-blob-'
        );

        file_put_contents(
            $temp,
            $blob
        );

        $this->assertValidImageFile(
            $temp,
            'source_image'
        );

        return $temp;
    }

    private function optimizeUploadedFrame(
        UploadedFile $file,
        string $fieldName
    ): array {
        return $this->optimizeFrameFile(
            $file->getRealPath(),
            $fieldName
        );
    }

    private function optimizeFrameFile(
        string $path,
        string $fieldName
    ): array {
        $image = $this->createImageFromFile(
            $path,
            $fieldName
        );

        $width = imagesx($image);
        $height = imagesy($image);

        $this->assertReadableResolution(
            $width,
            $height,
            $fieldName
        );

        $scale = min(
            1,
            self::MAX_FRAME_DIMENSION / max($width, $height)
        );

        if ($scale < 1) {
            $newWidth = max(
                1,
                (int) round($width * $scale)
            );
            $newHeight = max(
                1,
                (int) round($height * $scale)
            );
            $optimized = imagecreatetruecolor(
                $newWidth,
                $newHeight
            );

            imagecopyresampled(
                $optimized,
                $image,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $width,
                $height
            );

            imagedestroy($image);

            $image = $optimized;
            $width = $newWidth;
            $height = $newHeight;
        }

        $optimizedPath = $this->writeJpegImage(
            $image,
            'gallery-optimized-'
        );

        imagedestroy($image);

        return [
            $optimizedPath,
            $width,
            $height,
        ];
    }

    private function assertReadableResolution(
        int $width,
        int $height,
        string $fieldName
    ): void {
        if (
            $width < self::MIN_FRAME_DIMENSION
            || $height < self::MIN_FRAME_DIMENSION
        ) {
            throw ValidationException::withMessages([
                $fieldName => [
                    __('gallery.validation.insufficient_resolution'),
                ],
            ]);
        }
    }

    private function writeJpegImage(
        \GdImage $image,
        string $prefix
    ): string {
        $path = tempnam(
            sys_get_temp_dir(),
            $prefix
        );

        imagejpeg(
            $image,
            $path,
            self::JPEG_QUALITY
        );

        return $path;
    }

    private function createImageFromFile(
        string $path,
        string $fieldName = 'source_image'
    ): \GdImage {
        $this->assertValidImageFile(
            $path,
            $fieldName
        );

        $image = @imagecreatefromstring(
            file_get_contents($path)
        );

        if ($image === false) {
            throw ValidationException::withMessages([
                $fieldName => [
                    __('gallery.validation.invalid_image'),
                ],
            ]);
        }

        return $image;
    }

    private function assertValidImageFile(
        string $path,
        string $fieldName = 'source_image'
    ): void {
        if (! is_file($path) || ! is_readable($path)) {
            throw ValidationException::withMessages([
                $fieldName => [
                    __('gallery.validation.invalid_image'),
                ],
            ]);
        }

        $data = @file_get_contents($path);

        if ($data === false || $data === '') {
            throw ValidationException::withMessages([
                $fieldName => [
                    __('gallery.validation.invalid_image'),
                ],
            ]);
        }

        $decoded = @imagecreatefromstring($data);

        if ($decoded === false) {
            throw ValidationException::withMessages([
                $fieldName => [
                    __('gallery.validation.invalid_image'),
                ],
            ]);
        }

        imagedestroy($decoded);
    }

    private function makeStereoPairFromPair(
        string $leftPath,
        string $rightPath,
        string $fieldName = 'source_image'
    ): string {
        $left = $this->createImageFromFile(
            $leftPath,
            $fieldName
        );
        $right = $this->createImageFromFile(
            $rightPath,
            $fieldName
        );

        $leftWidth = imagesx($left);
        $leftHeight = imagesy($left);
        $rightWidth = imagesx($right);
        $rightHeight = imagesy($right);
        $combinedWidth = $leftWidth + $rightWidth;
        $combinedHeight = max(
            $leftHeight,
            $rightHeight
        );

        $combined = imagecreatetruecolor(
            $combinedWidth,
            $combinedHeight
        );

        imagecopy(
            $combined,
            $left,
            0,
            0,
            0,
            0,
            $leftWidth,
            $leftHeight
        );
        imagecopy(
            $combined,
            $right,
            $leftWidth,
            0,
            0,
            0,
            $rightWidth,
            $rightHeight
        );

        $temp = $this->writeJpegImage(
            $combined,
            'gallery-stereo-'
        );

        imagedestroy($left);
        imagedestroy($right);
        imagedestroy($combined);

        return $temp;
    }

    private function storeGeneratedImage(
        string $tempPath,
        string $folder,
        string $name
    ): string {
        $extension = 'jpg';
        $target = sprintf(
            '%s/%s.%s',
            $folder,
            $name,
            $extension
        );

        Storage::disk('public')
            ->put(
                $target,
                file_get_contents($tempPath)
            );

        return $target;
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function dimensions(
        UploadedFile $file
    ): array {
        $size = @getimagesize(
            $file->getRealPath()
        );

        if (! is_array($size)) {
            return [null, null];
        }

        return [
            (int) $size[0],
            (int) $size[1],
        ];
    }

    private function dimensionsFromPath(
        string $path
    ): array {
        $size = @getimagesize($path);

        if (! is_array($size)) {
            return [null, null];
        }

        return [
            (int) $size[0],
            (int) $size[1],
        ];
    }

    private function publishedGalleryQuery(Request $request): Builder
    {
        $query = StereoGalleryItem::query()
            ->published()
            ->latest('published_at')
            ->latest('id');

        if (Schema::hasTable('stereo_gallery_ratings')) {
            $query
                ->withCount('ratings')
                ->withAvg('ratings', 'rating');
        }

        if ($request->user() && Schema::hasTable('stereo_gallery_ratings')) {
            $query->with([
                'ratings' => fn ($ratings) => $ratings
                    ->where('user_id', $request->user()->id),
            ]);
        }

        if ($request->filled('author')) {
            $query->where(
                'author_name',
                $request->string('author')->toString()
            );
        }

        return $query;
    }

    private function uniqueSlug(
        string $title
    ): string {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'stereo';
        }

        $slug = $base;
        $number = 2;

        while (
            StereoGalleryItem::query()
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base
                .'-'
                .$number;

            $number += 1;
        }

        return $slug;
    }
}
