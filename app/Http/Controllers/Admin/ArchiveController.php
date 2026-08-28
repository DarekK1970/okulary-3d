<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArchiveTranslationStatus;
use App\Http\Controllers\Controller;
use App\Models\ArchiveItem;
use App\Models\ArchiveItemTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    private const TECHNIQUES = [
        'stereocard',
        'stereo_photo',
        'anaglyph',
        'viewmaster',
        'lenticular',
        'other',
    ];

    private const RIGHTS = [
        'public_domain',
        'cc0',
        'cc_by',
        'cc_by_sa',
        'permission',
    ];

    public function index(
        Request $request
    ): View {
        $query = ArchiveItem::query()
            ->with('translations')
            ->latest();

        if ($request->filled('q')) {
            $search = trim(
                $request->string('q')->toString()
            );

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where(
                        'creator',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'publisher',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhereHas(
                        'translations',
                        fn ($translationQuery) =>
                            $translationQuery->where(
                                'title',
                                'like',
                                '%' . $search . '%'
                            )
                    );
            });
        }

        if ($request->filled('published')) {
            $query->where(
                'is_published',
                $request->boolean('published')
            );
        }

        return view(
            'admin.archive.index',
            [
                'items' => $query
                    ->paginate(25)
                    ->withQueryString(),
            ]
        );
    }

    public function create(): View
    {
        return view(
            'admin.archive.create',
            $this->formData()
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $validated = $this->validateRequest(
            $request,
            true
        );

        $archiveItem = DB::transaction(
            function () use (
                $request,
                $validated
            ) {
                $folder =
                    'archive/' . Str::uuid();

                $originalPath =
                    $this->storeImage(
                        $request->file(
                            'original_image'
                        ),
                        $folder,
                        'original'
                    );

                $leftPath = null;
                $rightPath = null;

                if ($request->hasFile('left_image')) {
                    $leftPath =
                        $this->storeImage(
                            $request->file(
                                'left_image'
                            ),
                            $folder,
                            'left'
                        );

                    $rightPath =
                        $this->storeImage(
                            $request->file(
                                'right_image'
                            ),
                            $folder,
                            'right'
                        );
                }

                [$width, $height] =
                    $this->dimensions(
                        $request->file(
                            'original_image'
                        )
                    );

                $archiveItem =
                    ArchiveItem::create([
                        ...$this->sharedPayload(
                            $request,
                            $validated
                        ),
                        'original_image_path' =>
                            $originalPath,
                        'left_image_path' =>
                            $leftPath,
                        'right_image_path' =>
                            $rightPath,
                        'original_width' =>
                            $width,
                        'original_height' =>
                            $height,
                        'created_by' =>
                            $request->user()->id,
                        'updated_by' =>
                            $request->user()->id,
                    ]);

                $this->syncTranslations(
                    $archiveItem,
                    $validated
                );

                return $archiveItem;
            }
        );

        return redirect()
            ->route(
                'admin.archive.edit',
                $archiveItem
            )
            ->with(
                'status',
                __('archive.admin.saved')
            );
    }

    public function edit(
        ArchiveItem $archiveItem
    ): View {
        $archiveItem->load('translations');

        return view(
            'admin.archive.edit',
            [
                ...$this->formData(),
                'archiveItem' =>
                    $archiveItem,
            ]
        );
    }

    public function update(
        Request $request,
        ArchiveItem $archiveItem
    ): RedirectResponse {
        $validated = $this->validateRequest(
            $request,
            false
        );

        DB::transaction(
            function () use (
                $request,
                $validated,
                $archiveItem
            ) {
                $payload =
                    $this->sharedPayload(
                        $request,
                        $validated
                    );

                $folder = dirname(
                    $archiveItem
                        ->original_image_path
                );

                if (
                    $request->hasFile(
                        'original_image'
                    )
                ) {
                    $newPath = $this->storeImage(
                        $request->file(
                            'original_image'
                        ),
                        $folder,
                        'original'
                    );

                    if (
                        $newPath
                        !== $archiveItem
                            ->original_image_path
                    ) {
                        Storage::disk('public')
                            ->delete(
                                $archiveItem
                                    ->original_image_path
                            );
                    }

                    [$width, $height] =
                        $this->dimensions(
                            $request->file(
                                'original_image'
                            )
                        );

                    $payload[
                        'original_image_path'
                    ] = $newPath;

                    $payload[
                        'original_width'
                    ] = $width;

                    $payload[
                        'original_height'
                    ] = $height;
                }

                if (
                    $request->hasFile(
                        'left_image'
                    )
                ) {
                    $newLeft =
                        $this->storeImage(
                            $request->file(
                                'left_image'
                            ),
                            $folder,
                            'left'
                        );

                    $newRight =
                        $this->storeImage(
                            $request->file(
                                'right_image'
                            ),
                            $folder,
                            'right'
                        );

                    $oldStereoPaths = array_filter([
                        $archiveItem
                            ->left_image_path,
                        $archiveItem
                            ->right_image_path,
                    ]);

                    $pathsToDelete = array_filter(
                        $oldStereoPaths,
                        fn ($path) => ! in_array(
                            $path,
                            [$newLeft, $newRight],
                            true
                        )
                    );

                    Storage::disk('public')
                        ->delete($pathsToDelete);

                    $payload[
                        'left_image_path'
                    ] = $newLeft;

                    $payload[
                        'right_image_path'
                    ] = $newRight;
                }

                if (
                    $request->boolean(
                        'remove_stereo_pair'
                    )
                    && ! $request->hasFile(
                        'left_image'
                    )
                ) {
                    Storage::disk('public')
                        ->delete(array_filter([
                            $archiveItem
                                ->left_image_path,
                            $archiveItem
                                ->right_image_path,
                        ]));

                    $payload[
                        'left_image_path'
                    ] = null;

                    $payload[
                        'right_image_path'
                    ] = null;
                }

                $payload['updated_by'] =
                    $request->user()->id;

                $archiveItem->update(
                    $payload
                );

                $this->syncTranslations(
                    $archiveItem,
                    $validated
                );
            }
        );

        return back()->with(
            'status',
            __('archive.admin.saved')
        );
    }

    public function destroy(
        ArchiveItem $archiveItem
    ): RedirectResponse {
        Storage::disk('public')
            ->delete(array_filter([
                $archiveItem
                    ->original_image_path,
                $archiveItem
                    ->left_image_path,
                $archiveItem
                    ->right_image_path,
            ]));

        $archiveItem->delete();

        return redirect()
            ->route('admin.archive.index')
            ->with(
                'status',
                __('archive.admin.deleted')
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRequest(
        Request $request,
        bool $creating
    ): array {
        $validated = $request->validate([
            'source_locale' => [
                'required',
                Rule::in(['pl', 'en']),
            ],
            'technique' => [
                'required',
                Rule::in(self::TECHNIQUES),
            ],
            'year_from' => [
                'required',
                'integer',
                'min:1800',
                'max:2100',
            ],
            'year_to' => [
                'nullable',
                'integer',
                'min:1800',
                'max:2100',
                'gte:year_from',
            ],
            'circa' => [
                'nullable',
                'boolean',
            ],
            'creator' => [
                'nullable',
                'string',
                'max:190',
            ],
            'publisher' => [
                'nullable',
                'string',
                'max:190',
            ],
            'country' => [
                'nullable',
                'string',
                'max:120',
            ],
            'collection_name' => [
                'nullable',
                'string',
                'max:190',
            ],
            'source_name' => [
                'required',
                'string',
                'max:190',
            ],
            'source_url' => [
                'nullable',
                'url',
                'max:1000',
            ],
            'rights_status' => [
                'required',
                Rule::in(self::RIGHTS),
            ],
            'rights_note' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'is_published' => [
                'nullable',
                'boolean',
            ],
            'original_image' => [
                $creating
                    ? 'required'
                    : 'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:15360',
            ],
            'left_image' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:15360',
            ],
            'right_image' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:15360',
            ],
            'remove_stereo_pair' => [
                'nullable',
                'boolean',
            ],
            'translations.pl.title' => [
                'nullable',
                'string',
                'max:220',
            ],
            'translations.pl.slug' => [
                'nullable',
                'string',
                'max:220',
            ],
            'translations.pl.description' => [
                'nullable',
                'string',
                'max:4000',
            ],
            'translations.pl.historical_note' => [
                'nullable',
                'string',
                'max:20000',
            ],
            'translations.pl.seo_title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'translations.pl.seo_description' => [
                'nullable',
                'string',
                'max:500',
            ],
            'translations.pl.translation_status' => [
                'nullable',
                Rule::in(
                    ArchiveTranslationStatus::values()
                ),
            ],
            'translations.en.title' => [
                'nullable',
                'string',
                'max:220',
            ],
            'translations.en.slug' => [
                'nullable',
                'string',
                'max:220',
            ],
            'translations.en.description' => [
                'nullable',
                'string',
                'max:4000',
            ],
            'translations.en.historical_note' => [
                'nullable',
                'string',
                'max:20000',
            ],
            'translations.en.seo_title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'translations.en.seo_description' => [
                'nullable',
                'string',
                'max:500',
            ],
            'translations.en.translation_status' => [
                'nullable',
                Rule::in(
                    ArchiveTranslationStatus::values()
                ),
            ],
        ]);

        $sourceLocale =
            $validated['source_locale'];

        $sourceTitle =
            data_get(
                $validated,
                "translations.{$sourceLocale}.title"
            );

        if (! filled($sourceTitle)) {
            throw ValidationException::withMessages([
                "translations.{$sourceLocale}.title" =>
                    __('archive.admin.source_title_required'),
            ]);
        }

        $hasLeft =
            $request->hasFile('left_image');

        $hasRight =
            $request->hasFile('right_image');

        if ($hasLeft xor $hasRight) {
            throw ValidationException::withMessages([
                'left_image' =>
                    __('archive.admin.stereo_pair_required'),
                'right_image' =>
                    __('archive.admin.stereo_pair_required'),
            ]);
        }

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function sharedPayload(
        Request $request,
        array $validated
    ): array {
        $published =
            $request->boolean(
                'is_published'
            );

        return [
            'source_locale' =>
                $validated['source_locale'],
            'technique' =>
                $validated['technique'],
            'year_from' =>
                $validated['year_from'],
            'year_to' =>
                $validated['year_to']
                ?? null,
            'circa' =>
                $request->boolean('circa'),
            'creator' =>
                $validated['creator']
                ?? null,
            'publisher' =>
                $validated['publisher']
                ?? null,
            'country' =>
                $validated['country']
                ?? null,
            'collection_name' =>
                $validated['collection_name']
                ?? null,
            'source_name' =>
                $validated['source_name'],
            'source_url' =>
                $validated['source_url']
                ?? null,
            'rights_status' =>
                $validated['rights_status'],
            'rights_note' =>
                $validated['rights_note']
                ?? null,
            'is_published' =>
                $published,
            'published_at' =>
                $published
                    ? now()
                    : null,
        ];
    }

    private function syncTranslations(
        ArchiveItem $archiveItem,
        array $validated
    ): void {
        foreach (['pl', 'en'] as $locale) {
            $data = data_get(
                $validated,
                "translations.{$locale}",
                []
            );

            $title = trim(
                (string) (
                    $data['title']
                    ?? ''
                )
            );

            if ($title === '') {
                $archiveItem
                    ->translations()
                    ->where('locale', $locale)
                    ->delete();

                continue;
            }

            $status =
                $locale
                    === $archiveItem
                        ->source_locale
                    ? ArchiveTranslationStatus::Source
                    : ArchiveTranslationStatus::from(
                        $data[
                            'translation_status'
                        ]
                        ?? ArchiveTranslationStatus::Draft->value
                    );

            $slug = trim(
                (string) (
                    $data['slug']
                    ?? ''
                )
            );

            if ($slug === '') {
                $slug = Str::slug($title);
            }

            if ($slug === '') {
                $slug =
                    'archive-'
                    . $archiveItem->id
                    . '-'
                    . $locale;
            }

            $slug =
                $this->uniqueTranslationSlug(
                    $slug,
                    $locale,
                    $archiveItem->id
                );

            $archiveItem
                ->translations()
                ->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'title' => $title,
                        'slug' => $slug,
                        'description' =>
                            $data['description']
                            ?? null,
                        'historical_note' =>
                            $data[
                                'historical_note'
                            ]
                            ?? null,
                        'seo_title' =>
                            $data['seo_title']
                            ?? null,
                        'seo_description' =>
                            $data[
                                'seo_description'
                            ]
                            ?? null,
                        'translation_status' =>
                            $status,
                    ]
                );
        }
    }

    private function uniqueTranslationSlug(
        string $slug,
        string $locale,
        int $archiveItemId
    ): string {
        $base = Str::slug($slug);

        if ($base === '') {
            $base = 'archive';
        }

        $candidate = $base;
        $number = 2;

        while (
            ArchiveItemTranslation::query()
                ->where('locale', $locale)
                ->where('slug', $candidate)
                ->where(
                    'archive_item_id',
                    '!=',
                    $archiveItemId
                )
                ->exists()
        ) {
            $candidate =
                $base . '-' . $number;

            $number += 1;
        }

        return $candidate;
    }

    private function storeImage(
        UploadedFile $file,
        string $folder,
        string $name
    ): string {
        $extension = strtolower(
            $file->guessExtension()
                ?: $file->extension()
                ?: 'jpg'
        );

        return $file->storeAs(
            $folder,
            $name . '.' . $extension,
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

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'techniques' =>
                self::TECHNIQUES,
            'rightsStatuses' =>
                self::RIGHTS,
            'translationStatuses' =>
                ArchiveTranslationStatus::cases(),
        ];
    }
}
