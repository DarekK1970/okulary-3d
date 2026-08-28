@extends('admin.layout')

@section('title', __('archive.admin.title') . ' — ' . __('admin.title'))
@section('page_heading', __('archive.admin.title'))

@section('content')
<section class="admin-archive-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('archive.admin.kicker') }}</span>
            <h1>{{ __('archive.admin.title') }}</h1>
            <p>{{ __('archive.admin.description') }}</p>
        </div>

        <a
            class="cms-primary-button"
            href="{{ route('admin.archive.create') }}"
        >
            + {{ __('archive.admin.new_item') }}
        </a>
    </div>

    <form
        class="cms-filter-bar"
        method="get"
        action="{{ route('admin.archive.index') }}"
    >
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="{{ __('archive.admin.search') }}"
        >

        <select name="published">
            <option value="">{{ __('archive.admin.all_publication_states') }}</option>
            <option value="1" @selected(request('published') === '1')>
                {{ __('archive.admin.published') }}
            </option>
            <option value="0" @selected(request('published') === '0')>
                {{ __('archive.admin.unpublished') }}
            </option>
        </select>

        <button type="submit">{{ __('archive.admin.filter') }}</button>

        @if (request()->hasAny(['q', 'published']))
            <a href="{{ route('admin.archive.index') }}">
                {{ __('archive.admin.clear') }}
            </a>
        @endif
    </form>

    <div class="cms-table-wrap">
        <table class="cms-table admin-archive-table">
            <thead>
                <tr>
                    <th>{{ __('archive.admin.preview') }}</th>
                    <th>{{ __('archive.admin.item') }}</th>
                    <th>{{ __('archive.admin.period') }}</th>
                    <th>{{ __('archive.admin.technique') }}</th>
                    <th>{{ __('archive.admin.translations') }}</th>
                    <th>{{ __('archive.admin.publication') }}</th>
                    <th class="cms-actions-cell">{{ __('archive.admin.actions') }}</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($items as $item)
                    @php
                        $pl = $item->translation('pl');
                        $en = $item->translation('en');
                        $source = $item->translation($item->source_locale);
                    @endphp

                    <tr>
                        <td>
                            <img
                                class="admin-archive-thumb"
                                src="{{ $item->originalImageUrl() }}"
                                alt=""
                            >
                        </td>

                        <td>
                            <strong>{{ $source?->title ?: ('#' . $item->id) }}</strong>

                            @if ($item->creator)
                                <div class="catalog-muted">{{ $item->creator }}</div>
                            @endif
                        </td>

                        <td>{{ $item->yearLabel() }}</td>

                        <td>
                            {{ __('archive.techniques.' . $item->technique) }}
                        </td>

                        <td>
                            <div class="admin-archive-language-status">
                                <span class="{{ $pl?->isPubliclyReady() ? 'is-ready' : '' }}">
                                    PL
                                </span>
                                <span class="{{ $en?->isPubliclyReady() ? 'is-ready' : '' }}">
                                    EN
                                </span>
                            </div>
                        </td>

                        <td>
                            <span class="admin-archive-publication {{ $item->is_published ? 'is-published' : '' }}">
                                {{ $item->is_published ? __('archive.admin.published') : __('archive.admin.unpublished') }}
                            </span>
                        </td>

                        <td class="cms-actions-cell">
                            <a
                                class="cms-action-button"
                                href="{{ route('admin.archive.edit', $item) }}"
                            >
                                {{ __('archive.admin.edit') }}
                            </a>

                            @if ($item->is_published && $source?->isPubliclyReady())
                                <a
                                    class="cms-action-button"
                                    target="_blank"
                                    href="{{ route('archive.show', [
                                        'locale' => $item->source_locale,
                                        'slug' => $source->slug
                                    ]) }}"
                                >
                                    {{ __('archive.admin.open') }} ↗
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="cms-empty">
                            {{ __('archive.admin.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($items->hasPages())
        <div class="cms-pagination">
            {{ $items->links() }}
        </div>
    @endif
</section>
@endsection
