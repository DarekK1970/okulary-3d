@extends('admin.layout')

@section('title', __('admin.articles.title') . ' — ' . __('admin.title'))
@section('page_heading', __('admin.articles.title'))

@section('content')
<section class="cms-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('admin.articles.kicker') }}</span>
            <h1>{{ __('admin.articles.title') }}</h1>
            <p>{{ __('admin.articles.description') }}</p>
        </div>

        <a class="cms-primary-button" href="{{ route('admin.articles.create') }}">
            + {{ __('admin.articles.new') }}
        </a>
    </div>

    <form class="cms-filter-bar" method="get" action="{{ route('admin.articles.index') }}">
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="{{ __('admin.articles.filters.search') }}"
        >

        <select name="status">
            <option value="">{{ __('admin.articles.filters.all_statuses') }}</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                    {{ __('admin.articles.statuses.' . $status->value) }}
                </option>
            @endforeach
        </select>

        <select name="category">
            <option value="">{{ __('admin.articles.filters.all_categories') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

        <button type="submit">{{ __('admin.articles.filters.apply') }}</button>

        @if (request()->hasAny(['q', 'status', 'category']))
            <a href="{{ route('admin.articles.index') }}">{{ __('admin.articles.filters.clear') }}</a>
        @endif
    </form>

    <div class="cms-table-wrap">
        <table class="cms-table">
            <thead>
                <tr>
                    <th>{{ __('admin.articles.table.title') }}</th>
                    <th>{{ __('admin.articles.table.category') }}</th>
                    <th>{{ __('admin.articles.table.status') }}</th>
                    <th>{{ __('admin.articles.table.publication') }}</th>
                    <th>{{ __('admin.articles.table.author') }}</th>
                    <th class="cms-actions-cell">{{ __('admin.articles.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($articles as $article)
                    <tr>
                        <td>
                            <div class="cms-title-cell">
                                @if ($article->hero_image_path)
                                    <img src="{{ Storage::url($article->hero_image_path) }}" alt="">
                                @else
                                    <span class="cms-image-placeholder">3D</span>
                                @endif
                                <div>
                                    <strong>{{ $article->title }}</strong>
                                    <span>/{{ $article->slug }}</span>
                                </div>
                            </div>
                        </td>
                        <td>{{ $article->category?->name }}</td>
                        <td>
                            <span class="cms-status cms-status-{{ $article->status->value }}">
                                {{ __('admin.articles.statuses.' . $article->status->value) }}
                            </span>
                        </td>
                        <td>
                            {{ $article->published_at?->format('d.m.Y H:i') ?? '—' }}
                        </td>
                        <td>{{ $article->creator?->name ?? '—' }}</td>
                        <td class="cms-actions-cell">
                            <a class="cms-action-button" href="{{ route('admin.articles.edit', $article) }}">
                                {{ __('admin.articles.actions.edit') }}
                            </a>

                            <form
                                method="post"
                                action="{{ route('admin.articles.destroy', $article) }}"
                                onsubmit="return confirm('{{ __('admin.articles.actions.delete_confirm') }}')"
                            >
                                @csrf
                                @method('DELETE')
                                <button class="cms-action-button cms-action-danger" type="submit">
                                    {{ __('admin.articles.actions.delete') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="cms-empty">
                            {{ __('admin.articles.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($articles->hasPages())
        <div class="cms-pagination">
            {{ $articles->links() }}
        </div>
    @endif
</section>
@endsection
