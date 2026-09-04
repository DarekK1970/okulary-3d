<?php

namespace App\Http\Controllers;

use App\Models\PlanPurchase;
use App\Services\PayNowService;
use App\Services\PlanSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class PlanController extends Controller
{
    public function index(PlanSettingsService $settings): View
    {
        return view('plans.index', ['plans' => $settings->plans()]);
    }

    public function purchase(Request $request, PlanSettingsService $settings, PayNowService $payNow): RedirectResponse
    {
        $data = $request->validate(['plan' => ['required', Rule::in(['pro', 'premium'])], 'auto_renew' => ['nullable', 'boolean']]);
        $plan = $settings->plans()[$data['plan']];
        $purchase = PlanPurchase::query()->create(['public_token' => Str::uuid(), 'user_id' => $request->user()->id, 'plan' => $data['plan'], 'duration_months' => 3, 'token_lens' => $plan['tokens'], 'price' => $plan['price'], 'currency' => 'PLN', 'auto_renew' => $request->boolean('auto_renew'), 'status' => 'unpaid']);
        try {
            $payment = $payNow->startPlanPurchase($purchase, app()->getLocale());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['payment' => __('plans.payment_failed')]);
        }

        return redirect()->away($payment['redirectUrl']);
    }

    public function paymentReturn(Request $request, PlanPurchase $purchase, PayNowService $payNow): View
    {
        abort_unless($purchase->user_id === $request->user()->id, 403);
        $payNow->refreshPlanPurchase($purchase);

        return view('plans.return', ['purchase' => $purchase->fresh()]);
    }
}
