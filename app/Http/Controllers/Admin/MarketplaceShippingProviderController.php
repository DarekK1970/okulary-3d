<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceShippingProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketplaceShippingProviderController extends Controller
{
    public function index(): View
    {
        return view('admin.marketplace.providers.index', ['providers' => MarketplaceShippingProvider::query()->with('rates')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return $this->form(new MarketplaceShippingProvider(['is_active' => true]));
    }

    public function store(Request $request): RedirectResponse
    {
        $provider = DB::transaction(function () use ($request): MarketplaceShippingProvider {
            $validated = $this->validated($request);
            $rates = $validated['rates'];
            unset($validated['rates']);
            $provider = MarketplaceShippingProvider::query()->create($validated);
            $provider->rates()->createMany($rates);

            return $provider;
        });

        return redirect()->route('admin.marketplace.providers.edit', $provider)->with('status', __('marketplace.admin.providers.created'));
    }

    public function edit(MarketplaceShippingProvider $provider): View
    {
        $provider->load('rates');

        return $this->form($provider);
    }

    public function update(Request $request, MarketplaceShippingProvider $provider): RedirectResponse
    {
        DB::transaction(function () use ($request, $provider): void {
            $validated = $this->validated($request);
            $rates = $validated['rates'];
            unset($validated['rates']);
            $provider->update($validated);
            $provider->rates()->delete();
            $provider->rates()->createMany($rates);
        });

        return back()->with('status', __('marketplace.admin.providers.updated'));
    }

    public function destroy(MarketplaceShippingProvider $provider): RedirectResponse
    {
        $provider->delete();

        return redirect()->route('admin.marketplace.providers.index')->with('status', __('marketplace.admin.providers.deleted'));
    }

    private function form(MarketplaceShippingProvider $provider): View
    {
        return view('admin.marketplace.providers.form', ['provider' => $provider, 'printSizes' => MarketplaceProduct::PRINT_SIZES]);
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'rates' => ['required', 'array', 'min:1', 'max:100'],
            'rates.*.country_code' => ['nullable', 'string', 'size:2'],
            'rates.*.print_size' => ['nullable', Rule::in(MarketplaceProduct::PRINT_SIZES)],
            'rates.*.token_cost' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['rates'] = collect($validated['rates'])->map(fn (array $rate): array => [
            'country_code' => filled($rate['country_code'] ?? null) ? strtoupper($rate['country_code']) : null,
            'print_size' => $rate['print_size'] ?? null,
            'token_cost' => (int) $rate['token_cost'],
        ])->unique(fn (array $rate): string => ($rate['country_code'] ?? '*').'|'.($rate['print_size'] ?? '*'))->values()->all();

        return $validated;
    }
}
