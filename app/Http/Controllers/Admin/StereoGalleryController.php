<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GalleryStatus;
use App\Http\Controllers\Controller;
use App\Models\StereoGalleryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StereoGalleryController extends Controller
{
    public function index(
        Request $request
    ): View {
        $query = StereoGalleryItem::query()
            ->with('user')
            ->latest();

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->string('status')
            );
        }

        if ($request->filled('q')) {
            $search = trim(
                $request->string('q')
                    ->toString()
            );

            $query->where(
                function ($builder) use ($search) {
                    $builder
                        ->where(
                            'title',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'author_name',
                            'like',
                            '%' . $search . '%'
                        );
                }
            );
        }

        return view(
            'admin.gallery.index',
            [
                'items' =>
                    $query->paginate(25)
                        ->withQueryString(),
                'statuses' =>
                    GalleryStatus::cases(),
            ]
        );
    }

    public function show(
        StereoGalleryItem $galleryItem
    ): View {
        $galleryItem->load([
            'user',
            'moderator',
        ]);

        return view(
            'admin.gallery.show',
            compact('galleryItem')
        );
    }

    public function update(
        Request $request,
        StereoGalleryItem $galleryItem
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(
                    GalleryStatus::values()
                ),
            ],
            'moderation_note' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $status = GalleryStatus::from(
            $validated['status']
        );

        $galleryItem->update([
            'status' => $status,
            'published_at' =>
                $status
                    === GalleryStatus::Published
                        ? (
                            $galleryItem
                                ->published_at
                            ?? now()
                        )
                        : null,
            'moderated_by' =>
                $request->user()->id,
            'moderated_at' =>
                now(),
            'moderation_note' =>
                $validated['moderation_note']
                ?? null,
        ]);

        return redirect()
            ->route(
                'admin.gallery.show',
                $galleryItem
            )
            ->with(
                'status',
                __('gallery.admin.saved')
            );
    }
}
