<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleStatus;
use App\Enums\ArticleTranslationStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleTranslation;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Services\ArticleHtmlSanitizer;
use App\Services\ContextualRecommendationService;
use App\Services\MediaAssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $query = Article::query()
            ->with(['category', 'creator', 'translations', 'heroMedia'])
            ->latest('id');

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));

            $query->whereHas('translations', function ($builder) use ($search) {
                $builder->where(function ($translationQuery) use ($search) {
                    $translationQuery
                        ->where('title', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%')
                        ->orWhere('excerpt', 'like', '%' . $search . '%');
                });
            });
        }

        if (
            $request->filled('status')
            && in_array($request->input('status'), ArticleStatus::values(), true)
        ) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->where('category_id', (int) $request->input('category'));
        }

        return view('admin.articles.index', [
            'articles' => $query->paginate(20)->withQueryString(),
            'categories' => ArticleCategory::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'statuses' => ArticleStatus::cases(),
            'supportedLocales' => config('locales.supported', []),
        ]);
    }

    public function create(
        ContextualRecommendationService $recommendations
    ): View {
        return view('admin.articles.create', [
            'article' => new Article([
                'status' => ArticleStatus::Draft,
                'source_locale' => config('locales.default', 'pl'),
                'recommendation_auto' => true,
            ]),
            'categories' => $this->categories(),
            'statuses' => ArticleStatus::cases(),
            'translationStatuses' => ArticleTranslationStatus::cases(),
            'supportedLocales' => config('locales.supported', []),
            'mediaAssets' => $this->mediaAssets(),
            'recommendationTools' =>
                $recommendations->toolCatalog(),
            'recommendationProducts' =>
                $this->recommendationProducts(),
            'selectedRecommendationTools' => [],
            'selectedRecommendationProducts' => [],
        ]);
    }

    public function store(
        Request $request,
        ArticleHtmlSanitizer $sanitizer,
        MediaAssetService $mediaService,
        ContextualRecommendationService $recommendations
    ): RedirectResponse {
        $validated = $this->validateArticle(
            $request,
            $recommendations
        );
        $status = ArticleStatus::from($validated['status']);
        $sourceLocale = $validated['source_locale'];
        $source = $validated['translations'][$sourceLocale];

        $sourceTranslationSlug = $this->uniqueTranslationSlug(
            $sourceLocale,
            ($source['slug'] ?? null) ?: $source['title']
        );

        $article = new Article();
        $article->category_id = (int) $validated['category_id'];
        $article->source_locale = $sourceLocale;

        $article->title = $source['title'];
        $article->slug = $this->uniqueLegacySlug($sourceTranslationSlug);
        $article->excerpt = ($source['excerpt'] ?? null) ?: null;
        $article->body_html = $sanitizer->sanitize($source['body_html']);

        $article->status = $status;
        $article->recommendation_auto =
            $request->boolean(
                'recommendation_auto'
            );
        $article->published_at = $this->publishedAt(
            $status,
            $validated['published_at'] ?? null
        );
        $article->created_by = $request->user()->id;
        $article->updated_by = $request->user()->id;

        $this->applyHeroMedia(
            $request,
            $article,
            $validated,
            $mediaService
        );

        $article->save();

        $this->syncTranslations(
            $article,
            $validated,
            $sanitizer,
            [$sourceLocale => $sourceTranslationSlug]
        );

        $recommendations->syncManual(
            $article,
            $validated['recommendation_tools'] ?? [],
            $validated['recommendation_products'] ?? [],
            $request->user()->id
        );

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('status', __('cms.articles.messages.created'));
    }

    public function edit(
        Article $article,
        ContextualRecommendationService $recommendations
    ): View {
        $article->load([
            'translations',
            'heroMedia',
            'contextRecommendations',
        ]);

        $selection =
            $recommendations->manualSelection(
                $article
            );

        return view('admin.articles.edit', [
            'article' => $article,
            'categories' => $this->categories(),
            'statuses' => ArticleStatus::cases(),
            'translationStatuses' => ArticleTranslationStatus::cases(),
            'supportedLocales' => config('locales.supported', []),
            'mediaAssets' => $this->mediaAssets(),
            'recommendationTools' =>
                $recommendations->toolCatalog(),
            'recommendationProducts' =>
                $this->recommendationProducts(),
            'selectedRecommendationTools' =>
                $selection['tools'],
            'selectedRecommendationProducts' =>
                $selection['products'],
        ]);
    }

    public function update(
        Request $request,
        Article $article,
        ArticleHtmlSanitizer $sanitizer,
        MediaAssetService $mediaService,
        ContextualRecommendationService $recommendations
    ): RedirectResponse {
        $validated = $this->validateArticle(
            $request,
            $recommendations
        );
        $status = ArticleStatus::from($validated['status']);
        $sourceLocale = $validated['source_locale'];
        $source = $validated['translations'][$sourceLocale];

        $sourceTranslationSlug = $this->uniqueTranslationSlug(
            $sourceLocale,
            ($source['slug'] ?? null) ?: $source['title'],
            $article->translation($sourceLocale)
        );

        $article->category_id = (int) $validated['category_id'];
        $article->source_locale = $sourceLocale;

        $article->title = $source['title'];
        $article->slug = $this->uniqueLegacySlug(
            $sourceTranslationSlug,
            $article
        );
        $article->excerpt = ($source['excerpt'] ?? null) ?: null;
        $article->body_html = $sanitizer->sanitize($source['body_html']);

        $article->status = $status;
        $article->recommendation_auto =
            $request->boolean(
                'recommendation_auto'
            );
        $article->published_at = $this->publishedAt(
            $status,
            $validated['published_at'] ?? null
        );
        $article->updated_by = $request->user()->id;

        $this->applyHeroMedia(
            $request,
            $article,
            $validated,
            $mediaService
        );

        $article->save();

        $this->syncTranslations(
            $article,
            $validated,
            $sanitizer,
            [$sourceLocale => $sourceTranslationSlug]
        );

        $recommendations->syncManual(
            $article,
            $validated['recommendation_tools'] ?? [],
            $validated['recommendation_products'] ?? [],
            $request->user()->id
        );

        return back()->with('status', __('cms.articles.messages.updated'));
    }

    public function destroy(Article $article): RedirectResponse
    {
        // Media assets are shared resources and must not be deleted together
        // with an article.
        $article->delete();

        return redirect()
            ->route('admin.articles.index')
            ->with('status', __('cms.articles.messages.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateArticle(
        Request $request,
        ContextualRecommendationService $recommendations
    ): array
    {
        $supportedLocales = array_keys(
            config('locales.supported', ['pl' => []])
        );

        $sourceLocale = (string) $request->input(
            'source_locale',
            config('locales.default', 'pl')
        );

        $publishedAtRules = ['nullable', 'date'];

        if ($request->input('status') === ArticleStatus::Scheduled->value) {
            $publishedAtRules = ['required', 'date', 'after:now'];
        }

        $rules = [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('article_categories', 'id')
                    ->where(fn ($query) => $query->where('is_active', true)),
            ],
            'source_locale' => [
                'required',
                Rule::in($supportedLocales),
            ],
            'status' => [
                'required',
                Rule::in(ArticleStatus::values()),
            ],
            'published_at' => $publishedAtRules,
            'hero_media_id' => [
                'nullable',
                'integer',
                Rule::exists('media_assets', 'id'),
            ],
            'hero_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'remove_hero_image' => ['nullable', 'boolean'],
            'recommendation_auto' => [
                'nullable',
                'boolean',
            ],
            'recommendation_tools' => [
                'nullable',
                'array',
                'max:2',
            ],
            'recommendation_tools.*' => [
                'string',
                Rule::in(
                    array_keys(
                        $recommendations
                            ->toolDefinitions()
                    )
                ),
            ],
            'recommendation_products' => [
                'nullable',
                'array',
                'max:4',
            ],
            'recommendation_products.*' => [
                'integer',
                'distinct',
                Rule::exists(
                    'products',
                    'id'
                ),
            ],
            'translations' => ['required', 'array'],
        ];

        foreach ($supportedLocales as $locale) {
            $requiredForSource = $locale === $sourceLocale
                ? 'required'
                : 'nullable';

            $rules["translations.{$locale}.title"] = [
                $requiredForSource,
                'string',
                'max:220',
            ];

            $rules["translations.{$locale}.slug"] = [
                'nullable',
                'string',
                'max:240',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ];

            $rules["translations.{$locale}.excerpt"] = [
                'nullable',
                'string',
                'max:1000',
            ];

            $rules["translations.{$locale}.body_html"] = [
                $requiredForSource,
                'string',
            ];

            $rules["translations.{$locale}.seo_title"] = [
                'nullable',
                'string',
                'max:70',
            ];

            $rules["translations.{$locale}.seo_description"] = [
                'nullable',
                'string',
                'max:180',
            ];

            $rules["translations.{$locale}.translation_status"] = [
                'nullable',
                Rule::in(ArticleTranslationStatus::values()),
            ];
        }

        $validated = $request->validate($rules);

        foreach ($supportedLocales as $locale) {
            if ($locale === $sourceLocale) {
                continue;
            }

            $translation = $validated['translations'][$locale] ?? [];
            $title = trim((string) ($translation['title'] ?? ''));
            $body = trim((string) ($translation['body_html'] ?? ''));

            if (($title === '') xor ($body === '')) {
                throw ValidationException::withMessages([
                    "translations.{$locale}.title" => __(
                        'cms.articles.validation.translation_complete'
                    ),
                ]);
            }
        }

        return $validated;
    }

    private function applyHeroMedia(
        Request $request,
        Article $article,
        array $validated,
        MediaAssetService $mediaService
    ): void {
        if ($request->hasFile('hero_image')) {
            $media = $mediaService->storeImage(
                $request->file('hero_image'),
                $request->user(),
                'article-heroes'
            );

            $article->hero_media_id = $media->id;
            $article->hero_image_path = $media->path;

            return;
        }

        if (! empty($validated['hero_media_id'])) {
            $media = MediaAsset::query()->findOrFail(
                (int) $validated['hero_media_id']
            );

            $article->hero_media_id = $media->id;
            $article->hero_image_path = $media->path;

            return;
        }

        if ($request->boolean('remove_hero_image')) {
            $article->hero_media_id = null;
            $article->hero_image_path = null;
        }
    }

    /**
     * @param array<string, mixed> $validated
     * @param array<string, string> $reservedSlugs
     */
    private function syncTranslations(
        Article $article,
        array $validated,
        ArticleHtmlSanitizer $sanitizer,
        array $reservedSlugs = []
    ): void {
        $supportedLocales = array_keys(
            config('locales.supported', ['pl' => []])
        );

        foreach ($supportedLocales as $locale) {
            $data = $validated['translations'][$locale] ?? [];

            $title = trim((string) ($data['title'] ?? ''));
            $body = trim((string) ($data['body_html'] ?? ''));

            $existing = $article->translations()
                ->where('locale', $locale)
                ->first();

            if ($locale !== $article->source_locale && $title === '' && $body === '') {
                $existing?->delete();
                continue;
            }

            $translationStatus = $locale === $article->source_locale
                ? ArticleTranslationStatus::Source
                : ArticleTranslationStatus::from(
                    $data['translation_status']
                        ?? ArticleTranslationStatus::Draft->value
                );

            $slug = $reservedSlugs[$locale]
                ?? $this->uniqueTranslationSlug(
                    $locale,
                    ($data['slug'] ?? null) ?: $title,
                    $existing
                );

            $article->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'title' => $title,
                    'slug' => $slug,
                    'excerpt' => ($data['excerpt'] ?? null) ?: null,
                    'body_html' => $sanitizer->sanitize($body),
                    'seo_title' => ($data['seo_title'] ?? null) ?: null,
                    'seo_description' => ($data['seo_description'] ?? null) ?: null,
                    'translation_status' => $translationStatus,
                ]
            );
        }
    }

    private function publishedAt(
        ArticleStatus $status,
        mixed $value
    ): mixed {
        return match ($status) {
            ArticleStatus::Draft => null,
            ArticleStatus::Scheduled => $value,
            ArticleStatus::Published => $value ?: now(),
        };
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ArticleCategory>
     */
    private function categories()
    {
        return ArticleCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, MediaAsset>
     */
    private function mediaAssets()
    {
        return MediaAsset::query()
            ->latest('id')
            ->limit(100)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    private function recommendationProducts()
    {
        return Product::query()
            ->active()
            ->whereHas('activeVariants')
            ->with([
                'translations',
                'activeVariants',
                'category.translations',
            ])
            ->orderByDesc('is_featured')
            ->latest('id')
            ->limit(150)
            ->get();
    }

    private function uniqueTranslationSlug(
        string $locale,
        string $source,
        ?ArticleTranslation $ignore = null
    ): string {
        $base = Str::slug($source) ?: 'article';
        $slug = $base;
        $counter = 2;

        while (
            ArticleTranslation::query()
                ->where('locale', $locale)
                ->where('slug', $slug)
                ->when(
                    $ignore,
                    fn ($query) => $query->whereKeyNot($ignore->getKey())
                )
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function uniqueLegacySlug(
        string $source,
        ?Article $ignore = null
    ): string {
        $base = Str::slug($source) ?: 'article';
        $slug = $base;
        $counter = 2;

        while (
            Article::query()
                ->where('slug', $slug)
                ->when(
                    $ignore,
                    fn ($query) => $query->whereKeyNot($ignore->getKey())
                )
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
