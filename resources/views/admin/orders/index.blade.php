@extends('admin.layout')

@section('title', __('cart.admin.orders') . ' — ' . __('admin.title'))
@section('page_heading', __('cart.admin.orders'))

@section('content')
<section class="admin-orders-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('cart.admin.kicker') }}</span>
            <h1>{{ __('cart.admin.orders') }}</h1>
            <p>{{ __('cart.admin.description') }}</p>
        </div>
    </div>

    <form class="cms-filter-bar" method="get" action="{{ route('admin.orders.index') }}">
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="{{ __('cart.admin.search') }}"
        >

        <select name="status">
            <option value="">{{ __('cart.admin.all_statuses') }}</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                    {{ __('cart.statuses.' . $status->value) }}
                </option>
            @endforeach
        </select>

        <button type="submit">{{ __('cart.admin.filter') }}</button>

        @if (request()->hasAny(['q', 'status']))
            <a href="{{ route('admin.orders.index') }}">{{ __('cart.admin.clear') }}</a>
        @endif
    </form>

    <div class="cms-table-wrap">
        <table class="cms-table admin-orders-table">
            <thead>
                <tr>
                    <th>{{ __('cart.admin.number') }}</th>
                    <th>{{ __('cart.admin.date') }}</th>
                    <th>{{ __('cart.admin.customer') }}</th>
                    <th>{{ __('cart.admin.items') }}</th>
                    <th>{{ __('cart.admin.total') }}</th>
                    <th>{{ __('cart.admin.status') }}</th>
                    <th class="cms-actions-cell">{{ __('cart.admin.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td><strong>{{ $order->number }}</strong></td>
                        <td>{{ $order->placed_at?->format('d.m.Y H:i') }}</td>
                        <td>
                            <strong>{{ $order->customerName() }}</strong>
                            <div class="catalog-muted">{{ $order->customer_email }}</div>
                        </td>
                        <td>{{ $order->items_count }}</td>
                        <td>
                            <strong>
                                {{ number_format((float) $order->total_gross, 2, ',', ' ') }}
                                {{ $order->currency }}
                            </strong>
                        </td>
                        <td>
                            <span class="order-status order-status-{{ $order->status->value }}">
                                {{ __('cart.statuses.' . $order->status->value) }}
                            </span>
                        </td>
                        <td class="cms-actions-cell">
                            <a class="cms-action-button" href="{{ route('admin.orders.show', $order) }}">
                                {{ __('cart.admin.open') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="cms-empty">
                            {{ __('cart.admin.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($orders->hasPages())
        <div class="cms-pagination">{{ $orders->links() }}</div>
    @endif
</section>
@endsection
