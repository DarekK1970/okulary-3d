<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PaymentMethodService;
use App\Services\PayNowService;
use App\Services\ShippingMethodService;
use App\Services\TransactionalMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CheckoutController extends Controller
{
    public function create(
        Request $request,
        string $locale,
        CartService $cart,
        ShippingMethodService $shippingMethods,
        PaymentMethodService $paymentMethods
    ): View|RedirectResponse {
        $items = $cart->resolvedItems($locale);

        if ($items->isEmpty()) {
            return redirect()
                ->route('cart.index', ['locale' => $locale])
                ->withErrors([
                    'cart' => __('cart.messages.empty'),
                ]);
        }

        $currency = $items->first()['currency'] ?? 'PLN';

        return view('checkout.create', [
            'items' => $items,
            'subtotalCents' => (int) $items->sum(
                'line_total_cents'
            ),
            'currency' => $currency,
            'user' => $request->user(),
            'shippingMethods' => $shippingMethods->available(
                $locale,
                $currency
            ),
            'paymentMethods' => $paymentMethods->available(
                $locale,
                $currency
            ),
        ]);
    }

    public function store(
        Request $request,
        string $locale,
        CheckoutService $checkout,
        PayNowService $payNow,
        TransactionalMailService $mail
    ): RedirectResponse {
        $validated = $request->validate([
            'customer_email' => ['required', 'email:rfc', 'max:255'],
            'customer_first_name' => ['required', 'string', 'max:120'],
            'customer_last_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:40'],

            'billing_company' => ['nullable', 'string', 'max:180'],
            'billing_tax_id' => ['nullable', 'string', 'max:40'],
            'billing_address_line1' => ['required', 'string', 'max:255'],
            'billing_address_line2' => ['nullable', 'string', 'max:255'],
            'billing_postal_code' => ['required', 'string', 'max:30'],
            'billing_city' => ['required', 'string', 'max:120'],
            'billing_country_code' => [
                'required',
                'string',
                'size:2',
                'regex:/^[A-Za-z]{2}$/',
            ],

            'shipping_same_as_billing' => ['required', 'boolean'],
            'shipping_first_name' => [
                'required_if:shipping_same_as_billing,0',
                'nullable',
                'string',
                'max:120',
            ],
            'shipping_last_name' => [
                'required_if:shipping_same_as_billing,0',
                'nullable',
                'string',
                'max:120',
            ],
            'shipping_company' => ['nullable', 'string', 'max:180'],
            'shipping_address_line1' => [
                'required_if:shipping_same_as_billing,0',
                'nullable',
                'string',
                'max:255',
            ],
            'shipping_address_line2' => ['nullable', 'string', 'max:255'],
            'shipping_postal_code' => [
                'required_if:shipping_same_as_billing,0',
                'nullable',
                'string',
                'max:30',
            ],
            'shipping_city' => [
                'required_if:shipping_same_as_billing,0',
                'nullable',
                'string',
                'max:120',
            ],
            'shipping_country_code' => [
                'required_if:shipping_same_as_billing,0',
                'nullable',
                'string',
                'size:2',
                'regex:/^[A-Za-z]{2}$/',
            ],

            'shipping_method' => ['required', 'string', 'max:60'],
            'shipping_point' => ['nullable', 'string', 'max:160'],
            'payment_method' => ['required', 'string', 'max:60'],

            'customer_note' => ['nullable', 'string', 'max:2000'],
            'accept_terms' => ['accepted'],
        ]);

        $order = $checkout->place(
            $validated,
            $request->user(),
            $locale
        );

        $mail->orderPlaced($order);

        if ($order->payment_method === 'paynow') {
            try {
                $payment = $payNow->start($order);

                return redirect()->away(
                    $payment['redirectUrl']
                );
            } catch (Throwable $exception) {
                report($exception);

                return redirect()
                    ->route('order.success', [
                        'locale' => $locale,
                        'order' => $order->public_token,
                    ])
                    ->withErrors([
                        'payment' => __(
                            'checkout71.paynow.start_failed'
                        ),
                    ]);
            }
        }

        return redirect()->route('order.success', [
            'locale' => $locale,
            'order' => $order->public_token,
        ]);
    }

    public function success(
        string $locale,
        \App\Models\Order $order
    ): View {
        $order->load([
            'items',
            'salesDocuments',
        ]);

        return view('checkout.success', [
            'order' => $order,
            'bankTransfer' => config('shop.bank_transfer', []),
        ]);
    }
}
