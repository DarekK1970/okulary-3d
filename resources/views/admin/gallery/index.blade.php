@extends('admin.layout')

@section('title', __('gallery.admin.title') . ' — ' . __('admin.title'))
@section('page_heading', __('gallery.admin.title'))

@section('content')
<section class="admin-gallery-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('gallery.admin.kicker') }}</span>
            <h1>{{ __('gallery.admin.title') }}</h1>
            <p>{{ __('gallery.admin.description') }}</p>
        </div>
    </div>

    <form
        class="cms-filter-bar"
        method="get"
        action="{{ route('admin.gallery.index') }}"
    >
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="{{ __('gallery.admin.search') }}"
        >

        <select name="status">
            <option value="">{{ __('gallery.admin.all_statuses') }}</option>

            @foreach ($statuses as $status)
                <option
                    value="{{ $status->value }}"
                    @selected(request('status') === $status->value)
                >
                    {{ __('gallery.statuses.' . $status->value) }}
                </option>
            @endforeach
        </select>

        <button type="submit">{{ __('gallery.admin.filter') }}</button>

        @if (request()->hasAny(['q', 'status']))
            <a href="{{ route('admin.gallery.index') }}">
                {{ __('gallery.admin.clear') }}
            </a>
        @endif
    </form>

    <form
        method="post"
        action="{{ route('admin.gallery.bulk-publish') }}"
    >
        @csrf

        <div class="admin-gallery-bulk-actions">
            <button class="cms-action-button" type="submit">
                {{ __('gallery.admin.bulk_publish_selected') }}
            </button>
        </div>

        <div class="cms-table-wrap">
            <table class="cms-table admin-gallery-table">
                <thead>
                    <tr>
                        <th>{{ __('gallery.admin.preview') }}</th>
                        <th class="admin-gallery-select-column">
                            <span class="sr-only">{{ __('gallery.admin.select_for_bulk') }}</span>
                        </th>
                        <th>{{ __('gallery.admin.item') }}</th>
                        <th>{{ __('gallery.admin.user') }}</th>
                        <th>{{ __('gallery.admin.date') }}</th>
                        <th>{{ __('gallery.admin.status') }}</th>
                        <th class="cms-actions-cell">{{ __('gallery.admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>
                                <img
                                    class="admin-gallery-thumb"
                                    src="{{ $item->leftImageUrl() }}"
                                    alt=""
                                >
                            </td>

                            <td class="admin-gallery-select-column">
                                @if ($item->status === \App\Enums\GalleryStatus::Pending)
                                    <input
                                        type="checkbox"
                                        name="gallery_items[]"
                                        value="{{ $item->id }}"
                                        aria-label="{{ __('gallery.admin.select_item', ['title' => $item->title]) }}"
                                    >
                                @endif
                            </td>

                            <td>
                                <strong>{{ $item->title }}</strong>
                                <div class="catalog-muted">{{ $item->author_name }}</div>
                            </td>

                            <td>
                                <strong>{{ $item->user?->name }}</strong>
                                <div class="catalog-muted">{{ $item->user?->email }}</div>
                            </td>

                            <td>{{ $item->created_at->format('d.m.Y H:i') }}</td>

                            <td>
                                <span class="gallery-status gallery-status-{{ $item->status->value }}">
                                    {{ __('gallery.statuses.' . $item->status->value) }}
                                </span>
                            </td>

                            <td class="cms-actions-cell">
                                <a
                                    class="cms-action-button"
                                    href="{{ route('admin.gallery.show', $item) }}"
                                >
                                    {{ __('gallery.admin.open') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="cms-empty">
                                {{ __('gallery.admin.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    @if ($items->hasPages())
        <div class="cms-pagination">
            {{ $items->links() }}
        </div>
    @endif
</section>
@endsection
