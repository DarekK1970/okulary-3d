<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NewsletterCampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\NewsletterCampaign;
use App\Services\ArticleHtmlSanitizer;
use App\Services\NewsletterCampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewsletterCampaignController extends Controller
{
    public function create(): View
    {
        return view('admin.newsletter.campaign-form', [
            'campaign' => new NewsletterCampaign([
                'locale' => config('locales.default', 'pl'),
                'status' => NewsletterCampaignStatus::Draft,
            ]),
            'supportedLocales' => config('locales.supported', []),
        ]);
    }

    public function store(
        Request $request,
        ArticleHtmlSanitizer $sanitizer
    ): RedirectResponse {
        $validated = $this->validateCampaign($request);

        $campaign = NewsletterCampaign::create([
            ...$validated,
            'body_html' => $sanitizer->sanitize($validated['body_html']),
            'status' => NewsletterCampaignStatus::Draft,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.newsletter.campaigns.edit', $campaign)
            ->with('status', __('newsletter.admin.campaign_created'));
    }

    public function edit(NewsletterCampaign $campaign): View
    {
        $campaign->loadCount([
            'deliveries',
            'deliveries as sent_deliveries_count' => fn ($query) => $query->where('status', 'sent'),
            'deliveries as failed_deliveries_count' => fn ($query) => $query->where('status', 'failed'),
        ]);

        return view('admin.newsletter.campaign-form', [
            'campaign' => $campaign,
            'supportedLocales' => config('locales.supported', []),
        ]);
    }

    public function update(
        Request $request,
        NewsletterCampaign $campaign,
        ArticleHtmlSanitizer $sanitizer
    ): RedirectResponse {
        if (in_array(
            $campaign->status,
            [
                NewsletterCampaignStatus::Sending,
                NewsletterCampaignStatus::Sent,
            ],
            true
        )) {
            throw ValidationException::withMessages([
                'campaign' => __('newsletter.admin.sent_locked'),
            ]);
        }

        $validated = $this->validateCampaign($request);

        $campaign->update([
            ...$validated,
            'body_html' => $sanitizer->sanitize($validated['body_html']),
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('status', __('newsletter.admin.campaign_saved'));
    }

    public function schedule(
        Request $request,
        NewsletterCampaign $campaign,
        NewsletterCampaignService $service
    ): RedirectResponse {
        if (in_array(
            $campaign->status,
            [
                NewsletterCampaignStatus::Sending,
                NewsletterCampaignStatus::Sent,
            ],
            true
        )) {
            throw ValidationException::withMessages([
                'campaign' => __('newsletter.admin.sent_locked'),
            ]);
        }

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
        ]);

        $service->schedule(
            $campaign,
            new \DateTimeImmutable($validated['scheduled_at'])
        );

        return back()->with('status', __('newsletter.admin.campaign_scheduled'));
    }

    public function sendNow(
        NewsletterCampaign $campaign,
        NewsletterCampaignService $service
    ): RedirectResponse {
        $service->schedule($campaign, now());
        $service->processCampaign($campaign, 50);

        return back()->with('status', __('newsletter.admin.send_started'));
    }

    public function sendTest(
        Request $request,
        NewsletterCampaign $campaign,
        NewsletterCampaignService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'test_email' => ['required', 'email:rfc', 'max:255'],
        ]);

        $service->sendTest(
            $campaign,
            $validated['test_email'],
            $campaign->locale
        );

        return back()->with('status', __('newsletter.admin.test_sent'));
    }

    public function destroy(
        NewsletterCampaign $campaign
    ): RedirectResponse {
        if ($campaign->status !== NewsletterCampaignStatus::Draft) {
            throw ValidationException::withMessages([
                'campaign' => __('newsletter.admin.delete_locked'),
            ]);
        }

        $campaign->delete();

        return redirect()
            ->route('admin.newsletter.index')
            ->with('status', __('newsletter.admin.campaign_deleted'));
    }

    /** @return array<string, mixed> */
    private function validateCampaign(Request $request): array
    {
        return $request->validate([
            'locale' => [
                'required',
                Rule::in(array_keys(config('locales.supported', []))),
            ],
            'subject' => ['required', 'string', 'max:255'],
            'preheader' => ['nullable', 'string', 'max:500'],
            'body_html' => ['required', 'string', 'max:200000'],
        ]);
    }
}
