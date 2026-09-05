<?php

namespace App\Http\Controllers;

use App\Enums\GalleryStatus;
use App\Models\StereoGalleryItem;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StereoGalleryController extends Controller
{
    public function index(
        string $locale
    ): View {
        $items = StereoGalleryItem::query()
            ->published()
            ->latest('published_at')
            ->paginate(18);

        return view(
            'gallery.index',
            compact('items')
        );
    }

    public function show(
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

        return view(
            'gallery.show',
            [
                'galleryItem' => $galleryItem,
                'pageSeo' => $seo->gallery(
                    $galleryItem,
                    $locale
                ),
            ]
        );
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

            $leftPath = $this->storeImage(
                $left,
                $folder,
                'left'
            );

            try {
                $rightPath = $this->storeImage(
                    $right,
                    $folder,
                    'right'
                );
            } catch (\Throwable $exception) {
                Storage::disk('public')
                    ->delete($leftPath);

                throw $exception;
            }

            $stereoPath = $this->storeStereoPair(
                $left,
                $right,
                $folder
            );

            [$leftWidth, $leftHeight] =
                $this->dimensions($left);

            [$rightWidth, $rightHeight] =
                $this->dimensions($right);
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
        [$leftImage, $rightImage, $stereoImage] =
            $this->buildStereoPairImages(
                $source,
                $submissionType
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

        [$leftWidth, $leftHeight] =
            $this->dimensionsFromPath(
                $leftImage
            );

        [$rightWidth, $rightHeight] =
            $this->dimensionsFromPath(
                $rightImage
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
                    $this->makeStereoPairFromPair(
                        $this->makeImageFromBlob(
                            $images[0]
                        ),
                        $this->makeImageFromBlob(
                            $images[1]
                        )
                    ),
                ];
            }
        }

        [$leftImage, $rightImage] =
            $this->splitSideBySideImage(
                $sourcePath
            );

        return [
            $leftImage,
            $rightImage,
            $this->makeStereoPairFromPair(
                $leftImage,
                $rightImage
            ),
        ];
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

        $leftPath = tempnam(
            sys_get_temp_dir(),
            'gallery-left-'
        );
        $rightPath = tempnam(
            sys_get_temp_dir(),
            'gallery-right-'
        );

        imagejpeg($left, $leftPath, 92);
        imagejpeg($right, $rightPath, 92);

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

    private function createImageFromFile(
        string $path,
        string $fieldName = 'source_image'
    ) {
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
        string $rightPath
    ): string {
        $left = $this->createImageFromFile(
            $leftPath,
            'source_image'
        );
        $right = $this->createImageFromFile(
            $rightPath,
            'source_image'
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

        $temp = tempnam(
            sys_get_temp_dir(),
            'gallery-stereo-'
        );
        imagejpeg($combined, $temp, 92);

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

    private function storeImage(
        UploadedFile $file,
        string $folder,
        string $side
    ): string {
        $extension = strtolower(
            $file->guessExtension()
                ?: $file->extension()
                ?: 'jpg'
        );

        return $file->storeAs(
            $folder,
            sprintf(
                '%s.%s',
                $side,
                $extension
            ),
            'public'
        );
    }

    private function storeStereoPair(
        UploadedFile $left,
        UploadedFile $right,
        string $folder
    ): string {
        $leftPath = $left->getRealPath();
        $rightPath = $right->getRealPath();
        $combinedPath = $this->makeStereoPairFromPair(
            $leftPath,
            $rightPath
        );

        return $this->storeGeneratedImage(
            $combinedPath,
            $folder,
            'stereo_pair'
        );
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
