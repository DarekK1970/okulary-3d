<?php

namespace App\Models;

use App\Enums\CatalogTranslationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'source_locale',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductCategoryTranslation::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function translation(string $locale): ?ProductCategoryTranslation
    {
        return $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();
    }

    public function sourceTranslation(): ?ProductCategoryTranslation
    {
        return $this->translation($this->source_locale);
    }

    public function publicTranslation(string $locale): ?ProductCategoryTranslation
    {
        if ($this->relationLoaded('translations')) {
            return $this->translations
                ->where('locale', $locale)
                ->first(fn ($item) => $item->isPubliclyReady());
        }

        return $this->translations()
            ->where('locale', $locale)
            ->whereIn(
                'translation_status',
                CatalogTranslationStatus::publicValues()
            )
            ->first();
    }

    /**
     * Return categories in pre-order tree form:
     * [
     *   ['category' => ProductCategory, 'depth' => 0],
     *   ['category' => ProductCategory, 'depth' => 1],
     *   ...
     * ]
     *
     * Orphans and malformed cycles are still returned exactly once.
     */
    public static function flattenTree(Collection $categories): Collection
    {
        $ordered = $categories
            ->sortBy(fn (self $category) => [
                (int) $category->sort_order,
                (int) $category->id,
            ])
            ->values();

        $children = $ordered->groupBy(
            fn (self $category) => $category->parent_id === null
                ? 'root'
                : (string) $category->parent_id
        );

        $rows = collect();
        $visited = [];

        $walk = function (?int $parentId, int $depth) use (
            &$walk,
            $children,
            $rows,
            &$visited
        ): void {
            $key = $parentId === null ? 'root' : (string) $parentId;

            foreach ($children->get($key, collect()) as $category) {
                if (isset($visited[$category->id])) {
                    continue;
                }

                $visited[$category->id] = true;
                $rows->push([
                    'category' => $category,
                    'depth' => $depth,
                ]);

                $walk((int) $category->id, $depth + 1);
            }
        };

        $walk(null, 0);

        // Keep orphaned/cyclic data visible to the administrator.
        foreach ($ordered as $category) {
            if (isset($visited[$category->id])) {
                continue;
            }

            $visited[$category->id] = true;
            $rows->push([
                'category' => $category,
                'depth' => 0,
            ]);

            $walk((int) $category->id, 1);
        }

        return $rows;
    }

    public function descendantIds(
        bool $includeSelf = true
    ): array {
        return $this->descendantIdsFrom(
            self::query()->get(['id', 'parent_id']),
            $includeSelf
        );
    }

    public function descendantIdsFrom(
        Collection $categories,
        bool $includeSelf = true
    ): array {
        $children = $categories->groupBy(
            fn (self $category) => $category->parent_id === null
                ? 'root'
                : (string) $category->parent_id
        );

        $ids = $includeSelf ? [(int) $this->id] : [];
        $visited = [(int) $this->id => true];
        $queue = [(int) $this->id];

        while ($queue !== []) {
            $parentId = array_shift($queue);

            foreach ($children->get((string) $parentId, collect()) as $child) {
                $childId = (int) $child->id;

                if (isset($visited[$childId])) {
                    continue;
                }

                $visited[$childId] = true;
                $ids[] = $childId;
                $queue[] = $childId;
            }
        }

        return $ids;
    }

    /**
     * Root -> ... -> current category.
     */
    public function pathFrom(Collection $categories): Collection
    {
        $byId = $categories->keyBy(
            fn (self $category) => (int) $category->id
        );

        $current = $byId->get((int) $this->id, $this);
        $path = collect();
        $visited = [];

        while ($current) {
            $currentId = (int) $current->id;

            if (isset($visited[$currentId])) {
                break;
            }

            $visited[$currentId] = true;
            $path->prepend($current);

            if ($current->parent_id === null) {
                break;
            }

            $current = $byId->get((int) $current->parent_id);
        }

        return $path->values();
    }
}
