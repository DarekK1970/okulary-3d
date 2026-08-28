<?php

namespace App\Http\Controllers;

use App\Enums\GalleryStatus;
use App\Models\StereoGalleryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
        StereoGalleryItem $galleryItem
    ): View {
        abort_unless(
            $galleryItem->status
                === GalleryStatus::Published
            && $galleryItem->published_at,
            404
        );

        return view(
            'gallery.show',
            compact('galleryItem')
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
            'left_image' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:10240',
            ],
            'right_image' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:10240',
            ],
            'rights_confirmation' => [
                'accepted',
            ],
        ]);

        $user = $request->user();

        $left = $request->file(
            'left_image'
        );

        $right = $request->file(
            'right_image'
        );

        $folder = sprintf(
            'gallery/%d/%s',
            $user->id,
            Str::uuid()
        );

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

        [$leftWidth, $leftHeight] =
            $this->dimensions($left);

        [$rightWidth, $rightHeight] =
            $this->dimensions($right);

        $galleryItem =
            StereoGalleryItem::create([
                'user_id' => $user->id,
                'slug' => $this->uniqueSlug(
                    $validated['title']
                ),
                'title' => $validated['title'],
                'description' =>
                    $validated['description']
                    ?? null,
                'author_name' =>
                    $validated['author_name'],
                'license' =>
                    $validated['license'],
                'status' =>
                    GalleryStatus::Pending,
                'left_image_path' =>
                    $leftPath,
                'right_image_path' =>
                    $rightPath,
                'left_width' =>
                    $leftWidth,
                'left_height' =>
                    $leftHeight,
                'right_width' =>
                    $rightWidth,
                'right_height' =>
                    $rightHeight,
                'rights_confirmed_at' =>
                    now(),
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
            ->delete([
                $galleryItem
                    ->left_image_path,
                $galleryItem
                    ->right_image_path,
            ]);

        $galleryItem->delete();

        return back()->with(
            'status',
            __('gallery.messages.deleted')
        );
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
                . '-'
                . $number;

            $number += 1;
        }

        return $slug;
    }
}
