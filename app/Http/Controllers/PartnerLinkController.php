<?php

namespace App\Http\Controllers;

use App\Enums\PartnerLinkStatus;
use App\Mail\PartnerVerificationMail;
use App\Models\PartnerLink;
use App\Models\StaticPage;
use App\Services\PartnerLogoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PartnerLinkController extends Controller
{
    private const VERIFICATION_TTL_HOURS = 48;

    public function create(string $locale): View
    {
        $page = StaticPage::query()
            ->with('translations')
            ->where('key', 'partner-program')
            ->where('is_active', true)
            ->firstOrFail();

        $translation = $page->translationOrSource($locale);
        abort_unless($translation, 404);

        return view('partners.create', [
            'page' => $page,
            'translation' => $translation,
        ]);
    }

    public function store(
        Request $request,
        string $locale,
        PartnerLogoService $logoService
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'website_url' => ['required', 'url:http,https', 'max:2048'],
            'backlink_url' => ['nullable', 'url:http,https', 'max:2048'],
            'description' => ['required', 'string', 'max:300'],
            'logo' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:100',
                'dimensions:max_width=3000,max_height=3000',
            ],
            'email' => ['required', 'email:rfc', 'max:255'],
            'commercial' => ['required', Rule::in(['0', '1', 0, 1])],
            'contact_person' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:60'],
            'backlink_commitment' => ['accepted'],
            'privacy_consent' => ['accepted'],
            'website_confirm' => ['nullable', 'max:0'],
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
                ->where('domain', $domain)
                ->where('status', PartnerLinkStatus::Banned)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'website_url' => __('partners.validation.domain_banned'),
            ]);
        }

        $logoPath = $logoService->store($request->file('logo'));
        $token = Str::random(64);

        try {
            $partner = PartnerLink::create([
                'source_locale' => $locale,
                'name' => trim($validated['name']),
                'website_url' => $websiteUrl,
                'domain' => $domain,
                'backlink_url' => $backlinkUrl,
                'description' => trim($validated['description']),
                'logo_path' => $logoPath,
                'email' => mb_strtolower(trim($validated['email'])),
                'commercial' => (bool) $validated['commercial'],
                'contact_person' => filled($validated['contact_person'] ?? null)
                    ? trim($validated['contact_person'])
                    : null,
                'phone' => filled($validated['phone'] ?? null)
                    ? trim($validated['phone'])
                    : null,
                'status' => PartnerLinkStatus::EmailPending,
                'backlink_commitment_at' => now(),
                'privacy_accepted_at' => now(),
                'verification_token_hash' => hash('sha256', $token),
                'verification_sent_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($logoPath);
            throw $exception;
        }

        $mailSent = $this->sendVerificationMail($partner, $token);

        return redirect()
            ->route('partners.create', ['locale' => $locale])
            ->with('partner_verification_id', $partner->id)
            ->with(
                'status',
                $mailSent
                    ? __('partners.messages.submitted')
                    : __('partners.messages.submitted_mail_problem')
            );
    }

    public function verify(
        string $locale,
        PartnerLink $partner,
        string $token
    ): RedirectResponse {
        if ($partner->email_verified_at) {
            return redirect()
                ->route('partners.create', ['locale' => $locale])
                ->with('status', __('partners.messages.already_verified'));
        }

        abort_unless(
            $partner->verification_token_hash
            && hash_equals(
                $partner->verification_token_hash,
                hash('sha256', $token)
            ),
            404
        );

        if (
            ! $partner->verification_sent_at
            || $partner->verification_sent_at->lt(
                now()->subHours(self::VERIFICATION_TTL_HOURS)
            )
        ) {
            return redirect()
                ->route('partners.create', ['locale' => $locale])
                ->with('status', __('partners.messages.verification_expired'));
        }

        $partner->forceFill([
            'email_verified_at' => now(),
            'status' => PartnerLinkStatus::Pending,
            'verification_token_hash' => null,
        ])->save();

        return redirect()
            ->route('partners.create', ['locale' => $locale])
            ->with('status', __('partners.messages.verified'));
    }

    public function resend(
        Request $request,
        string $locale,
        PartnerLink $partner
    ): RedirectResponse {
        abort_unless(
            (int) $request->session()->get('partner_verification_id') === $partner->id,
            403
        );

        if ($partner->email_verified_at) {
            return back()->with('status', __('partners.messages.already_verified'));
        }

        abort_if($partner->status === PartnerLinkStatus::Banned, 403);

        $token = Str::random(64);
        $partner->forceFill([
            'verification_token_hash' => hash('sha256', $token),
            'verification_sent_at' => now(),
        ])->save();

        return back()->with(
            'status',
            $this->sendVerificationMail($partner, $token)
                ? __('partners.messages.resent')
                : __('partners.messages.resend_failed')
        );
    }

    public function go(string $locale, PartnerLink $partner): RedirectResponse
    {
        abort_unless(
            $partner->status === PartnerLinkStatus::Active
            && $partner->email_verified_at
            && $partner->approved_at,
            404
        );

        $partner->increment('click_count');

        return redirect()->away($partner->website_url);
    }

    private function sendVerificationMail(
        PartnerLink $partner,
        string $token
    ): bool {
        $locale = in_array(
            $partner->source_locale,
            array_keys(config('locales.supported', [])),
            true
        ) ? $partner->source_locale : config('locales.default', 'pl');

        $url = route('partners.verify', [
            'locale' => $locale,
            'partner' => $partner->id,
            'token' => $token,
        ]);

        try {
            Mail::to($partner->email)
                ->locale($locale)
                ->send(new PartnerVerificationMail($partner, $url));

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Partner verification e-mail could not be sent.', [
                'partner_id' => $partner->id,
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }
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
