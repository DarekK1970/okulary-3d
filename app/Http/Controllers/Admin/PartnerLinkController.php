<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PartnerLinkStatus;
use App\Http\Controllers\Controller;
use App\Models\PartnerLink;
use App\Services\PartnerBacklinkMonitor;
use App\Services\PartnerLogoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PartnerLinkController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $query = trim($request->string('q')->toString());
        $allowedStatuses = array_map(
            static fn (PartnerLinkStatus $case): string => $case->value,
            PartnerLinkStatus::cases()
        );

        $partners = PartnerLink::query()
            ->when(
                in_array($status, $allowedStatuses, true),
                fn ($builder) => $builder->where('status', $status)
            )
            ->when($query !== '', function ($builder) use ($query): void {
                $builder->where(function ($nested) use ($query): void {
                    $nested
                        ->where('name', 'like', '%' . $query . '%')
                        ->orWhere('domain', 'like', '%' . $query . '%')
                        ->orWhere('email', 'like', '%' . $query . '%');
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.partners.index', [
            'partners' => $partners,
            'selectedStatus' => $status,
            'query' => $query,
            'statuses' => PartnerLinkStatus::cases(),
        ]);
    }

    public function edit(PartnerLink $partner): View
    {
        return view('admin.partners.edit', compact('partner'));
    }

    public function update(
        Request $request,
        PartnerLink $partner,
        PartnerLogoService $logoService
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'website_url' => ['required', 'url:http,https', 'max:2048'],
            'backlink_url' => ['nullable', 'url:http,https', 'max:2048'],
            'description' => ['required', 'string', 'max:300'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'commercial' => ['required', Rule::in(['0', '1', 0, 1])],
            'contact_person' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:60'],
            'logo' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:100',
                'dimensions:max_width=3000,max_height=3000',
            ],
        ]);

        $websiteUrl = $this->normalizeUrl($validated['website_url']);
        $backlinkUrl = filled($validated['backlink_url'] ?? null)
            ? $this->normalizeUrl($validated['backlink_url'])
            : $websiteUrl;
        $domain = $this->domainFromUrl($websiteUrl);

        if ($domain === '') {
            throw ValidationException::withMessages([
                'website_url' => __('partners.validation.invalid_domain'),
            ]);
        }

        if (
            PartnerLink::query()
                ->whereKeyNot($partner->id)
                ->where('domain', $domain)
                ->where('status', PartnerLinkStatus::Banned)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'website_url' => __('partners.validation.domain_banned'),
            ]);
        }

        $oldLogoPath = $partner->logo_path;
        $newLogoPath = null;

        if ($request->hasFile('logo')) {
            $newLogoPath = $logoService->store($request->file('logo'));
        }

        try {
            $partner->fill([
                'name' => trim($validated['name']),
                'website_url' => $websiteUrl,
                'domain' => $domain,
                'backlink_url' => $backlinkUrl,
                'description' => trim($validated['description']),
                'email' => mb_strtolower(trim($validated['email'])),
                'commercial' => (bool) $validated['commercial'],
                'contact_person' => filled($validated['contact_person'] ?? null)
                    ? trim($validated['contact_person'])
                    : null,
                'phone' => filled($validated['phone'] ?? null)
                    ? trim($validated['phone'])
                    : null,
            ]);

            if ($newLogoPath) {
                $partner->logo_path = $newLogoPath;
            }

            $partner->save();
        } catch (\Throwable $exception) {
            if ($newLogoPath) {
                Storage::disk('public')->delete($newLogoPath);
            }

            throw $exception;
        }

        if ($newLogoPath && $oldLogoPath !== $newLogoPath) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        return redirect()
            ->route('admin.partners.edit', $partner)
            ->with('status', __('partners.admin.messages.updated'));
    }

    public function approve(Request $request, PartnerLink $partner): RedirectResponse
    {
        if (! $partner->email_verified_at) {
            return back()->withErrors([
                'partner' => __('partners.admin.messages.cannot_approve_unverified'),
            ]);
        }

        $partner->forceFill([
            'status' => PartnerLinkStatus::Active,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
            'rejected_at' => null,
            'banned_at' => null,
            'banned_by' => null,
            'banned_reason' => null,
        ])->save();

        return back()->with('status', __('partners.admin.messages.approved'));
    }

    public function revoke(PartnerLink $partner): RedirectResponse
    {
        $partner->forceFill([
            'status' => $partner->email_verified_at
                ? PartnerLinkStatus::Pending
                : PartnerLinkStatus::EmailPending,
            'approved_at' => null,
            'approved_by' => null,
        ])->save();

        return back()->with('status', __('partners.admin.messages.revoked'));
    }

    public function reject(PartnerLink $partner): RedirectResponse
    {
        $partner->forceFill([
            'status' => PartnerLinkStatus::Rejected,
            'rejected_at' => now(),
            'approved_at' => null,
            'approved_by' => null,
        ])->save();

        return back()->with('status', __('partners.admin.messages.rejected'));
    }

    public function ban(Request $request, PartnerLink $partner): RedirectResponse
    {
        $validated = $request->validate([
            'banned_reason' => ['required', 'string', 'max:1000'],
        ]);

        $partner->forceFill([
            'status' => PartnerLinkStatus::Banned,
            'banned_at' => now(),
            'banned_by' => $request->user()->id,
            'banned_reason' => trim($validated['banned_reason']),
            'approved_at' => null,
            'approved_by' => null,
        ])->save();

        return back()->with('status', __('partners.admin.messages.banned'));
    }

    public function checkBacklink(
        PartnerLink $partner,
        PartnerBacklinkMonitor $monitor
    ): RedirectResponse {
        $result = $monitor->check($partner);

        $messageKey = $result['backlink_found']
            ? 'backlink_found'
            : ($result['reachable'] ? 'backlink_missing' : 'backlink_unreachable');

        return back()->with(
            'status',
            __('partners.admin.messages.' . $messageKey, [
                'failures' => $result['consecutive_failures'],
                'status' => $result['http_status'] ?? '—',
            ])
        );
    }

    public function destroy(PartnerLink $partner): RedirectResponse
    {
        $logoPath = $partner->logo_path;
        $partner->delete();

        if ($logoPath) {
            Storage::disk('public')->delete($logoPath);
        }

        return redirect()
            ->route('admin.partners.index')
            ->with('status', __('partners.admin.messages.deleted'));
    }

    private function normalizeUrl(string $url): string
    {
        return rtrim(trim($url), '/');
    }

    private function domainFromUrl(string $url): string
    {
        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return trim($host, '.');
    }
}
