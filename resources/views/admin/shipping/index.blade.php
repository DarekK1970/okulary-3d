@extends('admin.layout')

@section('title', __('shipping.admin.title') . ' — ' . __('admin.title'))
@section('page_heading', __('shipping.admin.title'))

@section('content')
<style>
.shipping-admin-page{display:grid;gap:18px}
.shipping-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.shipping-summary-card{padding:14px;border:1px solid #dfe6ee;border-radius:12px;background:#fff}
.shipping-summary-card span{display:block;color:#8b97a9;font-size:.55rem;font-weight:800;text-transform:uppercase}
.shipping-summary-card strong{display:block;margin-top:5px;color:#20324d;font-size:1.05rem}
.shipping-grid-2{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px;align-items:start}
.shipping-country-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:7px}
.shipping-country-option,.shipping-method-option{display:flex;align-items:center;gap:7px;padding:9px 10px;border:1px solid #e2e8ef;border-radius:9px;background:#fbfcfd;color:#526075;font-size:.6rem}
.shipping-country-option.is-default{border-color:#a9dcea;background:#f1fbfe}
.shipping-method-list{display:grid;gap:7px}
.shipping-rate-create{display:grid;grid-template-columns:1.2fr 1.2fr .7fr .7fr .7fr auto;gap:8px;align-items:end}
.shipping-rate-list{display:grid;gap:8px}
.shipping-rate-row{display:grid;grid-template-columns:1.2fr 1.2fr .7fr .7fr .7fr auto auto;gap:8px;align-items:end;padding:10px;border:1px solid #e2e8ef;border-radius:10px;background:#fbfcfd}
.shipping-weight-table input{min-width:90px}
.shipping-info{padding:11px 12px;border:1px solid #d8e8ee;border-radius:10px;background:#f4fbfd;color:#557084;font-size:.59rem;line-height:1.55}
.shipping-warning{border-color:#f1dfb8;background:#fffaf0;color:#7d6634}
.shipping-actions{display:flex;gap:7px;align-items:center}
.shipping-small{display:block;margin-top:4px;color:#94a0b2;font-size:.52rem;line-height:1.45}
@media(max-width:1100px){
.shipping-summary{grid-template-columns:repeat(2,minmax(0,1fr))}
.shipping-grid-2{grid-template-columns:1fr}
.shipping-country-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
.shipping-rate-create,.shipping-rate-row{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(max-width:650px){
.shipping-summary,.shipping-country-grid,.shipping-rate-create,.shipping-rate-row{grid-template-columns:1fr}
}
</style>

<section class="shipping-admin-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('shipping.admin.kicker') }}</span>
            <h1>{{ __('shipping.admin.title') }}</h1>
            <p>{{ __('shipping.admin.description') }}</p>
        </div>

        <div class="catalog-heading-actions">
            <a
                class="cms-secondary-button"
                href="{{ route('admin.shipping.furgonetka.settings') }}"
            >
                🚚 Furgonetka.pl
            </a>

            <a
                class="cms-secondary-button"
                href="{{ route('admin.products.index') }}"
            >
                ← {{ __('shipping.admin.back_to_products') }}
            </a>
        </div>
    </div>

    <div class="shipping-summary">
        <div class="shipping-summary-card">
            <span>{{ __('shipping.admin.stats.active_countries') }}</span>
            <strong>{{ $countries->where('is_enabled', true)->count() }}</strong>
        </div>

        <div class="shipping-summary-card">
            <span>{{ __('shipping.admin.stats.active_methods') }}</span>
            <strong>{{ $methods->where('is_enabled', true)->count() }}</strong>
        </div>

        <div class="shipping-summary-card">
            <span>{{ __('shipping.admin.stats.rates') }}</span>
            <strong>{{ $rates->count() }}</strong>
        </div>

        <div class="shipping-summary-card">
            <span>{{ __('shipping.admin.stats.missing_weight') }}</span>
            <strong>{{ $missingWeightCount }}</strong>
        </div>
    </div>

    <form
        class="cms-panel"
        method="post"
        action="{{ route('admin.shipping.settings.update') }}"
    >
        @csrf
        @method('PUT')

        <div class="catalog-section-title">
            <div>
                <span class="admin-eyebrow">
                    {{ __('shipping.admin.settings.kicker') }}
                </span>
                <h2>{{ __('shipping.admin.settings.title') }}</h2>
                <p>{{ __('shipping.admin.settings.description') }}</p>
            </div>
        </div>

        <div class="shipping-grid-2">
            <div>
                <div class="cms-field">
                    <label>
                        {{ __('shipping.admin.settings.logistics_margin') }}
                    </label>
                    <input
                        type="number"
                        name="logistics_margin_percent"
                        min="0"
                        max="100"
                        step="0.01"
                        value="{{ old(
                            'logistics_margin_percent',
                            $settings->logisticsMarginPercent()
                        ) }}"
                        required
                    >
                    <small class="shipping-small">
                        {{ __('shipping.admin.settings.logistics_margin_help') }}
                    </small>
                </div>

                <h3>{{ __('shipping.admin.settings.methods') }}</h3>

                <div class="shipping-method-list">
                    @foreach ($methods as $method)
                        <label class="shipping-method-option">
                            <input
                                type="checkbox"
                                name="methods[]"
                                value="{{ $method->id }}"
                                @checked(
                                    in_array(
                                        $method->id,
                                        old(
                                            'methods',
                                            $methods
                                                ->where('is_enabled', true)
                                                ->pluck('id')
                                                ->all()
                                        ),
                                        true
                                    )
                                )
                            >
                            <span>
                                <strong>{{ $method->name_pl }}</strong>
                                <small class="shipping-small">
                                    {{ $method->code }}
                                    @if ($method->requires_pickup_point)
                                        · {{ __('shipping.admin.settings.pickup_point') }}
                                    @endif
                                </small>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <h3>{{ __('shipping.admin.settings.countries') }}</h3>

                <div class="shipping-country-grid">
                    @foreach ($countries as $country)
                        <label
                            class="shipping-country-option {{ $country->code === 'PL' ? 'is-default' : '' }}"
                        >
                            <input
                                type="checkbox"
                                name="countries[]"
                                value="{{ $country->code }}"
                                @checked(
                                    $country->code === 'PL'
                                    || in_array(
                                        $country->code,
                                        old(
                                            'countries',
                                            $countries
                                                ->where('is_enabled', true)
                                                ->pluck('code')
                                                ->all()
                                        ),
                                        true
                                    )
                                )
                                @disabled($country->code === 'PL')
                            >

                            @if ($country->code === 'PL')
                                <input
                                    type="hidden"
                                    name="countries[]"
                                    value="PL"
                                >
                            @endif

                            <span>
                                <strong>
                                    {{ $country->code }}
                                    — {{ $country->name_pl }}
                                </strong>

                                @if ($country->code === 'PL')
                                    <small class="shipping-small">
                                        {{ __('shipping.admin.settings.default_country') }}
                                    </small>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="cms-form-actions">
            <button class="cms-primary-button" type="submit">
                {{ __('shipping.admin.settings.save') }}
            </button>
        </div>
    </form>

    <section class="cms-panel">
        <div class="catalog-section-title">
            <div>
                <span class="admin-eyebrow">
                    {{ __('shipping.admin.rates.kicker') }}
                </span>
                <h2>{{ __('shipping.admin.rates.title') }}</h2>
                <p>{{ __('shipping.admin.rates.description') }}</p>
            </div>
        </div>

        <div class="shipping-info">
            {{ __('shipping.admin.rates.margin_note', [
                'margin' => $settings->logisticsMarginPercent(),
            ]) }}
        </div>

        <form
            class="shipping-rate-create"
            method="post"
            action="{{ route('admin.shipping.rates.store') }}"
        >
            @csrf

            <div class="cms-field">
                <label>{{ __('shipping.admin.rates.country') }}</label>
                <select name="shipping_country_id" required>
                    @foreach ($countries as $country)
                        <option
                            value="{{ $country->id }}"
                            @selected(
                                (string) old(
                                    'shipping_country_id',
                                    $countries->firstWhere('code', 'PL')?->id
                                )
                                === (string) $country->id
                            )
                        >
                            {{ $country->code }} — {{ $country->name_pl }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="cms-field">
                <label>{{ __('shipping.admin.rates.method') }}</label>
                <select name="shipping_method_id" required>
                    @foreach ($methods as $method)
                        <option
                            value="{{ $method->id }}"
                            @selected(
                                (string) old('shipping_method_id')
                                === (string) $method->id
                            )
                        >
                            {{ $method->name_pl }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="cms-field">
                <label>{{ __('shipping.admin.rates.from_kg') }}</label>
                <input
                    type="number"
                    name="weight_from_kg"
                    min="0"
                    max="1000"
                    step="0.001"
                    value="{{ old('weight_from_kg', '0.000') }}"
                    required
                >
            </div>

            <div class="cms-field">
                <label>{{ __('shipping.admin.rates.to_kg') }}</label>
                <input
                    type="number"
                    name="weight_to_kg"
                    min="0.001"
                    max="1000"
                    step="0.001"
                    value="{{ old('weight_to_kg', '1.000') }}"
                    required
                >
            </div>

            <div class="cms-field">
                <label>{{ __('shipping.admin.rates.price_pln') }}</label>
                <input
                    type="number"
                    name="price_pln"
                    min="0"
                    max="100000"
                    step="0.01"
                    value="{{ old('price_pln') }}"
                    required
                >
            </div>

            <label class="cms-checkbox">
                <input
                    type="checkbox"
                    name="is_enabled"
                    value="1"
                    checked
                >
                <span>{{ __('shipping.admin.rates.active') }}</span>
            </label>

            <button class="cms-primary-button" type="submit">
                + {{ __('shipping.admin.rates.add') }}
            </button>
        </form>

        <div class="shipping-rate-list">
            @forelse ($rates as $rate)
                <div class="shipping-rate-row">
                    <form
                        class="shipping-rate-row"
                        style="display:contents"
                        method="post"
                        action="{{ route(
                            'admin.shipping.rates.update',
                            $rate
                        ) }}"
                    >
                        @csrf
                        @method('PUT')

                        <div class="cms-field">
                            <label>{{ __('shipping.admin.rates.country') }}</label>
                            <select name="shipping_country_id" required>
                                @foreach ($countries as $country)
                                    <option
                                        value="{{ $country->id }}"
                                        @selected(
                                            $rate->shipping_country_id
                                            === $country->id
                                        )
                                    >
                                        {{ $country->code }}
                                        — {{ $country->name_pl }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="cms-field">
                            <label>{{ __('shipping.admin.rates.method') }}</label>
                            <select name="shipping_method_id" required>
                                @foreach ($methods as $method)
                                    <option
                                        value="{{ $method->id }}"
                                        @selected(
                                            $rate->shipping_method_id
                                            === $method->id
                                        )
                                    >
                                        {{ $method->name_pl }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="cms-field">
                            <label>{{ __('shipping.admin.rates.from_kg') }}</label>
                            <input
                                type="number"
                                name="weight_from_kg"
                                min="0"
                                max="1000"
                                step="0.001"
                                value="{{ $rate->weightFromKg() }}"
                                required
                            >
                        </div>

                        <div class="cms-field">
                            <label>{{ __('shipping.admin.rates.to_kg') }}</label>
                            <input
                                type="number"
                                name="weight_to_kg"
                                min="0.001"
                                max="1000"
                                step="0.001"
                                value="{{ $rate->weightToKg() }}"
                                required
                            >
                        </div>

                        <div class="cms-field">
                            <label>{{ __('shipping.admin.rates.price_pln') }}</label>
                            <input
                                type="number"
                                name="price_pln"
                                min="0"
                                max="100000"
                                step="0.01"
                                value="{{ $rate->price_pln }}"
                                required
                            >
                        </div>

                        <label class="cms-checkbox">
                            <input
                                type="checkbox"
                                name="is_enabled"
                                value="1"
                                @checked($rate->is_enabled)
                            >
                            <span>{{ __('shipping.admin.rates.active') }}</span>
                        </label>

                        <button
                            class="cms-secondary-button"
                            type="submit"
                        >
                            {{ __('shipping.admin.rates.save') }}
                        </button>
                    </form>

                    <form
                        method="post"
                        action="{{ route(
                            'admin.shipping.rates.destroy',
                            $rate
                        ) }}"
                        onsubmit="return confirm('{{ __('shipping.admin.rates.delete_confirm') }}')"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            class="cms-action-button cms-action-danger"
                            type="submit"
                        >
                            {{ __('shipping.admin.rates.delete') }}
                        </button>
                    </form>
                </div>
            @empty
                <p class="cms-empty">
                    {{ __('shipping.admin.rates.empty') }}
                </p>
            @endforelse
        </div>
    </section>

    <form
        class="cms-panel"
        method="post"
        action="{{ route('admin.shipping.weights.update') }}"
    >
        @csrf
        @method('PUT')

        <div class="catalog-section-title">
            <div>
                <span class="admin-eyebrow">
                    {{ __('shipping.admin.weights.kicker') }}
                </span>
                <h2>{{ __('shipping.admin.weights.title') }}</h2>
                <p>{{ __('shipping.admin.weights.description') }}</p>
            </div>
        </div>

        @if ($missingWeightCount > 0)
            <div class="shipping-info shipping-warning">
                {{ __('shipping.admin.weights.warning', [
                    'count' => $missingWeightCount,
                ]) }}
            </div>
        @endif

        <div class="cms-table-wrap">
            <table class="cms-table shipping-weight-table">
                <thead>
                    <tr>
                        <th>{{ __('shipping.admin.weights.product') }}</th>
                        <th>SKU</th>
                        <th>{{ __('shipping.admin.weights.variant') }}</th>
                        <th>{{ __('shipping.admin.weights.weight') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($variants as $variant)
                        <tr>
                            <td>
                                {{ $variant->product?->sourceTranslation()?->name ?? '—' }}
                            </td>
                            <td>{{ $variant->sku }}</td>
                            <td>{{ $variant->name ?: '—' }}</td>
                            <td>
                                <input
                                    type="number"
                                    name="weights[{{ $variant->id }}]"
                                    min="1"
                                    max="1000000"
                                    step="1"
                                    value="{{ old(
                                        'weights.' . $variant->id,
                                        $variant->weight_grams
                                    ) }}"
                                    placeholder="{{ __('shipping.admin.weights.placeholder') }}"
                                >
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="cms-empty">
                                {{ __('shipping.admin.weights.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($variants->isNotEmpty())
            <div class="cms-form-actions">
                <button class="cms-primary-button" type="submit">
                    {{ __('shipping.admin.weights.save') }}
                </button>
            </div>
        @endif
    </form>

    <div class="shipping-info">
        <strong>{{ __('shipping.admin.next_step.title') }}</strong><br>
        {{ __('shipping.admin.next_step.description') }}
    </div>
</section>
@endsection
