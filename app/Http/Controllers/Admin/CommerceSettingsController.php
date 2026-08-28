<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CommerceSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommerceSettingsController extends Controller
{
    public function index(
        CommerceSettingsService $settings
    ): View {
        return view('admin.settings.commerce', [
            'settings' => $settings,
            'payNowApiKeyMasked' =>
                $settings->maskedSecret('paynow.api_key'),
            'payNowSignatureKeyMasked' =>
                $settings->maskedSecret('paynow.signature_key'),
            'bank' => $settings->bankTransfer(),
            'seller' => $settings->seller(),
        ]);
    }

    public function update(
        Request $request,
        CommerceSettingsService $settings
    ): RedirectResponse {
        $validated = $request->validate([
            'paynow_enabled' => ['nullable', 'boolean'],
            'paynow_sandbox' => ['nullable', 'boolean'],
            'paynow_api_key' => ['nullable', 'string', 'max:255'],
            'paynow_signature_key' => [
                'nullable',
                'string',
                'max:255',
            ],
            'paynow_timeout' => [
                'required',
                'integer',
                'min:3',
                'max:60',
            ],
            'clear_paynow_api_key' => [
                'nullable',
                'boolean',
            ],
            'clear_paynow_signature_key' => [
                'nullable',
                'boolean',
            ],

            'bank_recipient' => [
                'nullable',
                'string',
                'max:255',
            ],
            'bank_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'bank_account' => [
                'nullable',
                'string',
                'max:100',
            ],
            'bank_swift' => [
                'nullable',
                'string',
                'max:40',
            ],

            'seller_name' => [
                'required',
                'string',
                'max:255',
            ],
            'seller_address' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'seller_tax_id' => [
                'nullable',
                'string',
                'max:50',
            ],
            'seller_email' => [
                'nullable',
                'email:rfc',
                'max:255',
            ],
        ]);

        $settings->setMany([
            'paynow.enabled' =>
                $request->boolean('paynow_enabled')
                    ? '1'
                    : '0',
            'paynow.sandbox' =>
                $request->boolean('paynow_sandbox')
                    ? '1'
                    : '0',
            'paynow.timeout' =>
                (string) $validated['paynow_timeout'],

            'bank.recipient' =>
                $this->nullable($validated['bank_recipient'] ?? null),
            'bank.name' =>
                $this->nullable($validated['bank_name'] ?? null),
            'bank.account' =>
                $this->nullable($validated['bank_account'] ?? null),
            'bank.swift' =>
                $this->nullable($validated['bank_swift'] ?? null),

            'seller.name' => trim($validated['seller_name']),
            'seller.address' =>
                $this->nullable($validated['seller_address'] ?? null),
            'seller.tax_id' =>
                $this->nullable($validated['seller_tax_id'] ?? null),
            'seller.email' =>
                $this->nullable($validated['seller_email'] ?? null),
        ]);

        if ($request->boolean('clear_paynow_api_key')) {
            $settings->set(
                'paynow.api_key',
                null,
                true
            );
        } elseif (filled($validated['paynow_api_key'] ?? null)) {
            $settings->set(
                'paynow.api_key',
                trim($validated['paynow_api_key']),
                true
            );
        }

        if ($request->boolean('clear_paynow_signature_key')) {
            $settings->set(
                'paynow.signature_key',
                null,
                true
            );
        } elseif (
            filled($validated['paynow_signature_key'] ?? null)
        ) {
            $settings->set(
                'paynow.signature_key',
                trim($validated['paynow_signature_key']),
                true
            );
        }

        return back()->with(
            'status',
            __('commerce_settings.messages.saved')
        );
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
