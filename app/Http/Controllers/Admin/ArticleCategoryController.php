<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticlePortalSection;
use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArticleCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.article-categories.index', [
            'categories' => ArticleCategory::query()
                ->withCount('articles')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'portalSections' => ArticlePortalSection::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCategory($request);

        ArticleCategory::create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug(
                ($validated['slug'] ?? null) ?: $validated['name']
            ),
            'description' => ($validated['description'] ?? null) ?: null,
            'portal_section' => $validated['portal_section']
                ?? ArticlePortalSection::Articles->value,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return back()->with('status', __('admin.categories.messages.created'));
    }

    public function update(Request $request, ArticleCategory $category): RedirectResponse
    {
        $validated = $this->validateCategory($request, $category);

        $category->update([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug(
                ($validated['slug'] ?? null) ?: $validated['name'],
                $category
            ),
            'description' => ($validated['description'] ?? null) ?: null,
            'portal_section' => $validated['portal_section']
                ?? $category->portal_section?->value
                ?? ArticlePortalSection::Articles->value,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return back()->with('status', __('admin.categories.messages.updated'));
    }

    public function destroy(ArticleCategory $category): RedirectResponse
    {
        if ($category->articles()->exists()) {
            return back()->withErrors([
                'category_delete' => __('admin.categories.messages.in_use'),
            ]);
        }

        $category->delete();

        return back()->with('status', __('admin.categories.messages.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCategory(Request $request, ?ArticleCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'slug' => [
                'nullable',
                'string',
                'max:140',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('article_categories', 'slug')->ignore($category?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'portal_section' => [
                'nullable',
                Rule::in(ArticlePortalSection::values()),
            ],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    private function uniqueSlug(string $source, ?ArticleCategory $ignore = null): string
    {
        $base = Str::slug($source) ?: 'kategoria';
        $slug = $base;
        $counter = 2;

        while (
            ArticleCategory::query()
                ->where('slug', $slug)
                ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
