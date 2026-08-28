<?php

namespace App\Services;

use App\Enums\ArchiveTranslationStatus;
use App\Enums\ArticleTranslationStatus;
use App\Enums\CatalogTranslationStatus;
use App\Models\AiTranslationRun;
use App\Models\ArchiveItem;
use App\Models\ArchiveItemTranslation;
use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductTranslation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AiTranslationService
{
    public const TYPE_ARTICLE = 'article';
    public const TYPE_PRODUCT = 'product';
    public const TYPE_PRODUCT_CATEGORY = 'product_category';
    public const TYPE_ARCHIVE = 'archive';

    public function __construct(
        private AiTranslationProviderService $provider,
        private AiTranslationSettingsService $settings,
        private ArticleHtmlSanitizer $sanitizer
    ) {
    }

    /** @return list<string> */
    public function allowedTypesFor(User $user): array
    {
        $types = [
            self::TYPE_ARTICLE,
            self::TYPE_ARCHIVE,
        ];

        if (in_array(
            $user->role,
            [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN],
            true
        )) {
            $types[] = self::TYPE_PRODUCT;
            $types[] = self::TYPE_PRODUCT_CATEGORY;
        }

        return $types;
    }

    public function translate(
        string $type,
        int $id,
        User $user
    ): AiTranslationRun {
        if (! in_array(
            $type,
            $this->allowedTypesFor($user),
            true
        )) {
            abort(403);
        }

        $content = $this->findContent($type, $id);
        $sourceLocale = (string) $content->source_locale;
        $targetLocale = $this->targetLocale($sourceLocale);
        $sourceTranslation = $content->translation($sourceLocale);

        if (! $sourceTranslation) {
            throw new RuntimeException(
                __('ai_translator.errors.source_missing')
            );
        }

        $targetTranslation = $content->translation($targetLocale);

        if (
            $targetTranslation
            && method_exists($targetTranslation, 'isPubliclyReady')
            && $targetTranslation->isPubliclyReady()
        ) {
            throw new RuntimeException(
                __('ai_translator.errors.ready_locked')
            );
        }

        $fields = $this->sourceFields(
            $type,
            $sourceTranslation
        );

        $run = AiTranslationRun::create([
            'content_type' => $type,
            'content_id' => $content->getKey(),
            'source_locale' => $sourceLocale,
            'target_locale' => $targetLocale,
            'provider' => $this->settings->provider(),
            'model' => $this->settings->model(),
            'status' => 'started',
            'request_chars' => mb_strlen(
                json_encode(
                    $fields,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                ) ?: ''
            ),
            'initiated_by' => $user->id,
        ]);

        try {
            $result = $this->provider->translate(
                $fields,
                $sourceLocale,
                $targetLocale,
                $type
            );

            $this->validateGeneratedFields(
                $type,
                $result['fields']
            );

            DB::transaction(function () use (
                $type,
                $content,
                $targetLocale,
                $result,
                $user
            ) {
                $this->saveDraft(
                    $type,
                    $content,
                    $targetLocale,
                    $result['fields']
                );

                if (
                    in_array(
                        $type,
                        [
                            self::TYPE_ARTICLE,
                            self::TYPE_PRODUCT,
                            self::TYPE_ARCHIVE,
                        ],
                        true
                    )
                ) {
                    $content->forceFill([
                        'updated_by' => $user->id,
                    ])->save();
                }
            });

            $run->update([
                'provider' => $result['provider'],
                'model' => $result['model'],
                'status' => 'success',
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
                'total_tokens' => $result['total_tokens'],
                'response_chars' => mb_strlen(
                    $result['raw_text']
                ),
                'error_message' => null,
            ]);
        } catch (\Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'error_message' => mb_substr(
                    $exception->getMessage(),
                    0,
                    2000
                ),
            ]);

            throw $exception;
        }

        return $run->fresh();
    }

    /**
     * @return array<string, string>
     */
    public function describe(
        string $type,
        Model $content
    ): array {
        $sourceLocale = (string) $content->source_locale;
        $targetLocale = $this->targetLocale($sourceLocale);
        $source = $content->translation($sourceLocale);
        $target = $content->translation($targetLocale);

        return [
            'id' => (string) $content->getKey(),
            'type' => $type,
            'label' => $this->label($type, $source),
            'source_locale' => $sourceLocale,
            'target_locale' => $targetLocale,
            'target_status' => $target
                ? $target->translation_status->value
                : 'missing',
            'target_ready' => $target
                && method_exists($target, 'isPubliclyReady')
                && $target->isPubliclyReady()
                ? '1'
                : '0',
            'edit_url' => $this->editUrl($type, $content),
        ];
    }

    public function typeLabelKey(string $type): string
    {
        return 'ai_translator.types.' . $type;
    }

    /** @return class-string<Model> */
    public function modelClass(string $type): string
    {
        return match ($type) {
            self::TYPE_ARTICLE => Article::class,
            self::TYPE_PRODUCT => Product::class,
            self::TYPE_PRODUCT_CATEGORY => ProductCategory::class,
            self::TYPE_ARCHIVE => ArchiveItem::class,
            default => throw new RuntimeException(
                __('ai_translator.errors.type')
            ),
        };
    }

    private function findContent(
        string $type,
        int $id
    ): Model {
        $class = $this->modelClass($type);

        return $class::query()
            ->with('translations')
            ->findOrFail($id);
    }

    private function targetLocale(
        string $sourceLocale
    ): string {
        $supported = array_keys(
            config('locales.supported', [
                'pl' => [],
                'en' => [],
            ])
        );

        foreach ($supported as $locale) {
            if ($locale !== $sourceLocale) {
                return $locale;
            }
        }

        throw new RuntimeException(
            __('ai_translator.errors.target_missing')
        );
    }

    /**
     * @return array<string, string>
     */
    private function sourceFields(
        string $type,
        Model $translation
    ): array {
        return match ($type) {
            self::TYPE_ARTICLE => [
                'title' => (string) $translation->title,
                'excerpt' => (string) ($translation->excerpt ?? ''),
                'body_html' => (string) $translation->body_html,
                'seo_title' => (string) ($translation->seo_title ?? ''),
                'seo_description' => (string) ($translation->seo_description ?? ''),
            ],
            self::TYPE_PRODUCT => [
                'name' => (string) $translation->name,
                'short_description' => (string) ($translation->short_description ?? ''),
                'description_html' => (string) $translation->description_html,
                'seo_title' => (string) ($translation->seo_title ?? ''),
                'seo_description' => (string) ($translation->seo_description ?? ''),
            ],
            self::TYPE_PRODUCT_CATEGORY => [
                'name' => (string) $translation->name,
                'description' => (string) ($translation->description ?? ''),
            ],
            self::TYPE_ARCHIVE => [
                'title' => (string) $translation->title,
                'description' => (string) ($translation->description ?? ''),
                'historical_note' => (string) ($translation->historical_note ?? ''),
                'seo_title' => (string) ($translation->seo_title ?? ''),
                'seo_description' => (string) ($translation->seo_description ?? ''),
            ],
            default => throw new RuntimeException(
                __('ai_translator.errors.type')
            ),
        };
    }

    /**
     * @param array<string, string> $fields
     */
    private function saveDraft(
        string $type,
        Model $content,
        string $targetLocale,
        array $fields
    ): void {
        match ($type) {
            self::TYPE_ARTICLE => $this->saveArticle(
                $content,
                $targetLocale,
                $fields
            ),
            self::TYPE_PRODUCT => $this->saveProduct(
                $content,
                $targetLocale,
                $fields
            ),
            self::TYPE_PRODUCT_CATEGORY => $this->saveProductCategory(
                $content,
                $targetLocale,
                $fields
            ),
            self::TYPE_ARCHIVE => $this->saveArchive(
                $content,
                $targetLocale,
                $fields
            ),
            default => throw new RuntimeException(
                __('ai_translator.errors.type')
            ),
        };
    }

    /** @param array<string, string> $fields */
    private function saveArticle(
        Article $article,
        string $locale,
        array $fields
    ): void {
        $existing = $article->translation($locale);
        $slug = $this->uniqueSlug(
            ArticleTranslation::class,
            $locale,
            $fields['title'],
            $existing?->id
        );

        $article->translations()->updateOrCreate(
            ['locale' => $locale],
            [
                'title' => $this->limit($fields['title'], 220),
                'slug' => $slug,
                'excerpt' => $this->nullableLimited($fields['excerpt'], 1000),
                'body_html' => $this->sanitizer->sanitize(
                    $fields['body_html']
                ),
                'seo_title' => $this->nullableLimited($fields['seo_title'], 70),
                'seo_description' => $this->nullableLimited($fields['seo_description'], 180),
                'translation_status' => ArticleTranslationStatus::Draft,
            ]
        );
    }

    /** @param array<string, string> $fields */
    private function saveProduct(
        Product $product,
        string $locale,
        array $fields
    ): void {
        $existing = $product->translation($locale);
        $slug = $this->uniqueSlug(
            ProductTranslation::class,
            $locale,
            $fields['name'],
            $existing?->id
        );

        $product->translations()->updateOrCreate(
            ['locale' => $locale],
            [
                'name' => $this->limit($fields['name'], 220),
                'slug' => $slug,
                'short_description' => $this->nullableLimited(
                    $fields['short_description'],
                    1200
                ),
                'description_html' => $this->sanitizer->sanitize(
                    $fields['description_html']
                ),
                'seo_title' => $this->nullableLimited($fields['seo_title'], 70),
                'seo_description' => $this->nullableLimited($fields['seo_description'], 180),
                'translation_status' => CatalogTranslationStatus::Draft,
            ]
        );
    }

    /** @param array<string, string> $fields */
    private function saveProductCategory(
        ProductCategory $category,
        string $locale,
        array $fields
    ): void {
        $existing = $category->translation($locale);
        $slug = $this->uniqueSlug(
            ProductCategoryTranslation::class,
            $locale,
            $fields['name'],
            $existing?->id
        );

        $category->translations()->updateOrCreate(
            ['locale' => $locale],
            [
                'name' => $this->limit($fields['name'], 160),
                'slug' => $slug,
                'description' => $this->nullableLimited($fields['description'], 3000),
                'translation_status' => CatalogTranslationStatus::Draft,
            ]
        );
    }

    /** @param array<string, string> $fields */
    private function saveArchive(
        ArchiveItem $archiveItem,
        string $locale,
        array $fields
    ): void {
        $existing = $archiveItem->translation($locale);
        $slug = $this->uniqueSlug(
            ArchiveItemTranslation::class,
            $locale,
            $fields['title'],
            $existing?->id
        );

        $archiveItem->translations()->updateOrCreate(
            ['locale' => $locale],
            [
                'title' => $this->limit($fields['title'], 220),
                'slug' => $slug,
                'description' => $this->nullableLimited($fields['description'], 4000),
                'historical_note' => $this->nullableLimited(
                    $fields['historical_note'],
                    20000
                ),
                'seo_title' => $this->nullableLimited($fields['seo_title'], 255),
                'seo_description' => $this->nullableLimited($fields['seo_description'], 500),
                'translation_status' => ArchiveTranslationStatus::Draft,
            ]
        );
    }

    /**
     * @param class-string<Model> $translationClass
     */
    private function uniqueSlug(
        string $translationClass,
        string $locale,
        string $title,
        ?int $ignoreId = null
    ): string {
        $base = Str::slug($title) ?: 'translation';
        $candidate = $base;
        $number = 2;

        while (true) {
            $query = $translationClass::query()
                ->where('locale', $locale)
                ->where('slug', $candidate);

            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }

            if (! $query->exists()) {
                return $candidate;
            }

            $candidate = $base . '-' . $number;
            $number += 1;
        }
    }

    private function label(
        string $type,
        ?Model $source
    ): string {
        if (! $source) {
            return '#';
        }

        return match ($type) {
            self::TYPE_PRODUCT,
            self::TYPE_PRODUCT_CATEGORY =>
                (string) $source->name,
            default => (string) $source->title,
        };
    }

    private function editUrl(
        string $type,
        Model $content
    ): string {
        return match ($type) {
            self::TYPE_ARTICLE => route(
                'admin.articles.edit',
                $content
            ),
            self::TYPE_PRODUCT => route(
                'admin.products.edit',
                $content
            ),
            self::TYPE_PRODUCT_CATEGORY => route(
                'admin.product-categories.index'
            ),
            self::TYPE_ARCHIVE => route(
                'admin.archive.edit',
                $content
            ),
            default => '#',
        };
    }

    /**
     * @param array<string, string> $fields
     */
    private function validateGeneratedFields(
        string $type,
        array $fields
    ): void {
        $required = match ($type) {
            self::TYPE_ARTICLE => [
                'title',
                'body_html',
            ],
            self::TYPE_PRODUCT => [
                'name',
                'description_html',
            ],
            self::TYPE_PRODUCT_CATEGORY => [
                'name',
            ],
            self::TYPE_ARCHIVE => [
                'title',
            ],
            default => [],
        };

        foreach ($required as $key) {
            if (trim((string) ($fields[$key] ?? '')) === '') {
                throw new RuntimeException(
                    __('ai_translator.errors.required_field', [
                        'field' => $key,
                    ])
                );
            }
        }
    }

    private function limit(string $value, int $max): string
    {
        return mb_substr(trim($value), 0, $max);
    }

    private function nullableLimited(
        string $value,
        int $max
    ): ?string {
        $value = $this->limit($value, $max);

        return $value === '' ? null : $value;
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
