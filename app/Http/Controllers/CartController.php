<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\CurrencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(
        string $locale,
        CartService $cart,
        CurrencyService $currencies
    ): View {
        $items = $cart->resolvedItems($locale);

        return view('cart.index', [
            'items' => $items,
            'subtotalCents' => (int) $items->sum('line_total_cents'),
            'currency' => $items->first()['currency']
                ?? $currencies->selectedCode(),
            'currencyService' => $currencies,
        ]);
    }

    public function store(
        Request $request,
        string $locale,
        CartService $cart
    ): RedirectResponse {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $variant = ProductVariant::query()
            ->findOrFail((int) $validated['variant_id']);

        $cart->add(
            $variant,
            (int) $validated['quantity'],
            $locale
        );

        return redirect()
            ->route('cart.index', ['locale' => $locale])
            ->with('status', __('cart.messages.added'));
    }

    public function update(
        Request $request,
        string $locale,
        ProductVariant $variant,
        CartService $cart
    ): RedirectResponse {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $cart->update(
            $variant,
            (int) $validated['quantity'],
            $locale
        );

        return back()->with('status', __('cart.messages.updated'));
    }

    public function destroy(
        string $locale,
        ProductVariant $variant,
        CartService $cart
    ): RedirectResponse {
        $cart->remove($variant->id);

        return back()->with('status', __('cart.messages.removed'));
    }

    public function clear(
        string $locale,
        CartService $cart
    ): RedirectResponse {
        $cart->clear();

        return back()->with('status', __('cart.messages.cleared'));
    }
}
