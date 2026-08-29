<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NewsletterCampaignStatus;
use App\Enums\NewsletterSubscriberStatus;
use App\Http\Controllers\Controller;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                'string',
                'in:' . implode(',', NewsletterSubscriberStatus::values()),
            ],
            'locale' => [
                'nullable',
                'string',
                'in:' . implode(',', array_keys(config('locales.supported', []))),
            ],
        ]);

        $subscribers = NewsletterSubscriber::query()
            ->when(
                filled($validated['q'] ?? null),
                fn ($query) => $query->where(
                    'email',
                    'like',
                    '%' . trim($validated['q']) . '%'
                )
            )
            ->when(
                filled($validated['status'] ?? null),
                fn ($query) => $query->where('status', $validated['status'])
            )
            ->when(
                filled($validated['locale'] ?? null),
                fn ($query) => $query->where('locale', $validated['locale'])
            )
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.newsletter.index', [
            'subscribers' => $subscribers,
            'campaigns' => NewsletterCampaign::query()
                ->with('creator')
                ->latest('id')
                ->limit(20)
                ->get(),
            'counts' => [
                'active' => NewsletterSubscriber::query()
                    ->where('status', NewsletterSubscriberStatus::Active->value)
                    ->count(),
                'pending' => NewsletterSubscriber::query()
                    ->where('status', NewsletterSubscriberStatus::Pending->value)
                    ->count(),
                'unsubscribed' => NewsletterSubscriber::query()
                    ->where('status', NewsletterSubscriberStatus::Unsubscribed->value)
                    ->count(),
            ],
            'statuses' => NewsletterSubscriberStatus::cases(),
            'campaignStatuses' => NewsletterCampaignStatus::cases(),
            'supportedLocales' => config('locales.supported', []),
            'filters' => $validated,
        ]);
    }

    public function export(Request $request)
    {
        $query = NewsletterSubscriber::query()->orderBy('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('locale')) {
            $query->where('locale', $request->string('locale')->toString());
        }

        return response()->streamDownload(
            function () use ($query): void {
                $handle = fopen('php://output', 'wb');
                fputcsv($handle, [
                    'email',
                    'locale',
                    'status',
                    'source',
                    'confirmed_at',
                    'unsubscribed_at',
                ], ';');

                $query->chunkById(500, function ($rows) use ($handle): void {
                    foreach ($rows as $subscriber) {
                        fputcsv($handle, [
                            $subscriber->email,
                            $subscriber->locale,
                            $subscriber->status->value,
                            $subscriber->source,
                            $subscriber->confirmed_at?->format('Y-m-d H:i:s'),
                            $subscriber->unsubscribed_at?->format('Y-m-d H:i:s'),
                        ], ';');
                    }
                });

                fclose($handle);
            },
            'newsletter-subscribers-' . now()->format('Y-m-d-His') . '.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }
}
