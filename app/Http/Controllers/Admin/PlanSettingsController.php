<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlanSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanSettingsController extends Controller
{
    public function edit(PlanSettingsService $settings): View
    {
        return view('admin.settings.plans', ['plans' => $settings->plans()]);
    }

    public function update(Request $request, PlanSettingsService $settings): RedirectResponse
    {
        $data = $request->validate(['free_tokens' => ['required', 'integer', 'min:0', 'max:100000'], 'pro_price' => ['required', 'numeric', 'min:0.01', 'max:100000'], 'pro_tokens' => ['required', 'integer', 'min:0', 'max:100000'], 'premium_price' => ['required', 'numeric', 'min:0.01', 'max:100000'], 'premium_tokens' => ['required', 'integer', 'min:0', 'max:100000']]);
        $settings->update($data);

        return back()->with('status', __('plans.admin.saved'));
    }
}
