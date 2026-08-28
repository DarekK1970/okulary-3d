<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Services\MediaAssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $query = MediaAsset::query()
            ->with('uploader')
            ->withCount('heroArticles')
            ->latest('id');

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('title', 'like', '%' . $search . '%')
                    ->orWhere('original_name', 'like', '%' . $search . '%')
                    ->orWhere('alt_text', 'like', '%' . $search . '%')
                    ->orWhere('caption', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('folder')) {
            $query->where('folder', $request->input('folder'));
        }

        return view('admin.media.index', [
            'mediaAssets' => $query->paginate(30)->withQueryString(),
            'folders' => MediaAsset::query()
                ->select('folder')
                ->distinct()
                ->orderBy('folder')
                ->pluck('folder'),
        ]);
    }

    public function store(
        Request $request,
        MediaAssetService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'folder' => ['nullable', 'string', 'max:120'],
        ]);

        foreach ($validated['files'] as $file) {
            $service->storeImage(
                $file,
                $request->user(),
                $validated['folder'] ?? 'general'
            );
        }

        return back()->with(
            'status',
            trans_choice(
                'media.messages.uploaded',
                count($validated['files']),
                ['count' => count($validated['files'])]
            )
        );
    }

    public function edit(MediaAsset $media): View
    {
        $media->loadCount('heroArticles');

        return view('admin.media.edit', [
            'media' => $media,
            'folders' => MediaAsset::query()
                ->select('folder')
                ->distinct()
                ->orderBy('folder')
                ->pluck('folder'),
        ]);
    }

    public function update(
        Request $request,
        MediaAsset $media,
        MediaAssetService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:180'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:2000'],
            'folder' => ['required', 'string', 'max:120'],
        ]);

        $media->update([
            'title' => ($validated['title'] ?? null) ?: null,
            'alt_text' => ($validated['alt_text'] ?? null) ?: null,
            'caption' => ($validated['caption'] ?? null) ?: null,
            'folder' => $service->normalizeFolder($validated['folder']),
        ]);

        return back()->with(
            'status',
            __('media.messages.updated')
        );
    }

    public function destroy(
        MediaAsset $media,
        MediaAssetService $service
    ): RedirectResponse {
        if ($media->heroArticles()->exists()) {
            return back()->withErrors([
                'media_delete' => __('media.messages.in_use'),
            ]);
        }

        $service->delete($media);

        return redirect()
            ->route('admin.media.index')
            ->with('status', __('media.messages.deleted'));
    }
}
