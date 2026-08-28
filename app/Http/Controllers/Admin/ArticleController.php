<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Services\ArticleHtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $query = Article::query()
            ->with(['category', 'creator'])
            ->latest('id');

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('title', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%')
                    ->orWhere('excerpt', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status') && in_array($request->input('status'), ArticleStatus::values(), true)) {
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
        ]);
    }

    public function create(): View
    {
        return view('admin.articles.create', [
            'article' => new Article([
                'status' => ArticleStatus::Draft,
            ]),
            'categories' => $this->categories(),
            'statuses' => ArticleStatus::cases(),
        ]);
    }

    public function store(Request $request, ArticleHtmlSanitizer $sanitizer): RedirectResponse
    {
        $validated = $this->validateArticle($request);
        $status = ArticleStatus::from($validated['status']);

        $article = new Article();
        $article->category_id = (int) $validated['category_id'];
        $article->title = $validated['title'];
        $article->slug = $this->uniqueSlug(
            ($validated['slug'] ?? null) ?: $validated['title']
        );
        $article->excerpt = ($validated['excerpt'] ?? null) ?: null;
        $article->body_html = $sanitizer->sanitize($validated['body_html']);
        $article->status = $status;
        $article->published_at = $this->publishedAt(
            $status,
            $validated['published_at'] ?? null
        );
        $article->created_by = $request->user()->id;
        $article->updated_by = $request->user()->id;

        if ($request->hasFile('hero_image')) {
            $article->hero_image_path = $request->file('hero_image')
                ->store('articles/heroes', 'public');
        }

        $article->save();

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('status', __('admin.articles.messages.created'));
    }

    public function edit(Article $article): View
    {
        return view('admin.articles.edit', [
            'article' => $article,
            'categories' => $this->categories(),
            'statuses' => ArticleStatus::cases(),
        ]);
    }

    public function update(
        Request $request,
        Article $article,
        ArticleHtmlSanitizer $sanitizer
    ): RedirectResponse {
        $validated = $this->validateArticle($request, $article);
        $status = ArticleStatus::from($validated['status']);

        $article->category_id = (int) $validated['category_id'];
        $article->title = $validated['title'];
        $article->slug = $this->uniqueSlug(
            ($validated['slug'] ?? null) ?: $validated['title'],
            $article
        );
        $article->excerpt = ($validated['excerpt'] ?? null) ?: null;
        $article->body_html = $sanitizer->sanitize($validated['body_html']);
        $article->status = $status;
        $article->published_at = $this->publishedAt(
            $status,
            $validated['published_at'] ?? null
        );
        $article->updated_by = $request->user()->id;

        if ($request->hasFile('hero_image')) {
            if ($article->hero_image_path) {
                Storage::disk('public')->delete($article->hero_image_path);
            }

            $article->hero_image_path = $request->file('hero_image')
                ->store('articles/heroes', 'public');
        }

        if ($request->boolean('remove_hero_image') && $article->hero_image_path) {
            Storage::disk('public')->delete($article->hero_image_path);
            $article->hero_image_path = null;
        }

        $article->save();

        return back()->with('status', __('admin.articles.messages.updated'));
    }

    public function destroy(Article $article): RedirectResponse
    {
        if ($article->hero_image_path) {
            Storage::disk('public')->delete($article->hero_image_path);
        }

        $article->delete();

        return redirect()
            ->route('admin.articles.index')
            ->with('status', __('admin.articles.messages.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateArticle(Request $request, ?Article $article = null): array
    {
        $publishedAtRules = ['nullable', 'date'];

        if ($request->input('status') === ArticleStatus::Scheduled->value) {
            $publishedAtRules = ['required', 'date', 'after:now'];
        }

        return $request->validate([
            'category_id' => [
                'required',
                'integer',
                Rule::exists('article_categories', 'id')
                    ->where(fn ($query) => $query->where('is_active', true)),
            ],
            'title' => ['required', 'string', 'min:3', 'max:220'],
            'slug' => ['nullable', 'string', 'max:240', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body_html' => ['required', 'string', 'min:3'],
            'status' => ['required', Rule::in(ArticleStatus::values())],
            'published_at' => $publishedAtRules,
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_hero_image' => ['nullable', 'boolean'],
        ]);
    }

    private function publishedAt(ArticleStatus $status, mixed $value): mixed
    {
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

    private function uniqueSlug(string $source, ?Article $ignore = null): string
    {
        $base = Str::slug($source) ?: 'artykul';
        $slug = $base;
        $counter = 2;

        while (
            Article::query()
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
