<?php

namespace App\Services;

use App\Enums\NewsletterCampaignStatus;
use App\Enums\NewsletterDeliveryStatus;
use App\Enums\NewsletterSubscriberStatus;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class NewsletterCampaignService
{
    public function schedule(
        NewsletterCampaign $campaign,
        ?\DateTimeInterface $when = null
    ): NewsletterCampaign {
        if ($campaign->status === NewsletterCampaignStatus::Sent) {
            return $campaign;
        }

        $campaign->forceFill([
            'status' => NewsletterCampaignStatus::Scheduled,
            'scheduled_at' => $when ?: now(),
        ])->save();

        return $campaign;
    }

    public function sendTest(
        NewsletterCampaign $campaign,
        string $email,
        string $locale
    ): void {
        $unsubscribeUrl = route('home', [
            'locale' => $locale,
        ]) . '#newsletter';

        Mail::to($email)
            ->locale($locale)
            ->send(new NewsletterCampaignMail(
                $campaign,
                $unsubscribeUrl,
                true
            ));
    }

    public function processDueCampaigns(
        int $limit = 100
    ): int {
        $processed = 0;

        $campaigns = NewsletterCampaign::query()
            ->whereIn('status', [
                NewsletterCampaignStatus::Scheduled->value,
                NewsletterCampaignStatus::Sending->value,
            ])
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->get();

        foreach ($campaigns as $campaign) {
            if ($processed >= $limit) {
                break;
            }

            $processed += $this->processCampaign(
                $campaign,
                $limit - $processed
            );
        }

        return $processed;
    }

    public function processCampaign(
        NewsletterCampaign $campaign,
        int $limit = 100
    ): int {
        if ($campaign->status === NewsletterCampaignStatus::Sent) {
            return 0;
        }

        $this->prepareDeliveries($campaign);

        $campaign->forceFill([
            'status' => NewsletterCampaignStatus::Sending,
        ])->save();

        $deliveries = NewsletterDelivery::query()
            ->where('newsletter_campaign_id', $campaign->id)
            ->where(function ($query) {
                $query->where('status', NewsletterDeliveryStatus::Pending->value)
                    ->orWhere(function ($failed) {
                        $failed->where('status', NewsletterDeliveryStatus::Failed->value)
                            ->where('attempts', '<', 3);
                    });
            })
            ->with('subscriber')
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();

        $processed = 0;

        foreach ($deliveries as $delivery) {
            $processed++;
            $subscriber = $delivery->subscriber;

            if (! $subscriber || ! $subscriber->isActive()) {
                $delivery->forceFill([
                    'status' => NewsletterDeliveryStatus::Skipped,
                    'error_message' => __('newsletter.admin.subscriber_inactive'),
                ])->save();
                continue;
            }

            try {
                $unsubscribeUrl = route('newsletter.unsubscribe.form', [
                    'locale' => $subscriber->locale,
                    'subscriber' => $subscriber,
                    'token' => (string) $subscriber->unsubscribe_token,
                ]);

                Mail::to($delivery->email_snapshot)
                    ->locale($campaign->locale)
                    ->send(new NewsletterCampaignMail(
                        $campaign,
                        $unsubscribeUrl
                    ));

                $delivery->forceFill([
                    'status' => NewsletterDeliveryStatus::Sent,
                    'attempts' => $delivery->attempts + 1,
                    'sent_at' => now(),
                    'error_message' => null,
                ])->save();

                $subscriber->forceFill([
                    'last_sent_at' => now(),
                ])->save();
            } catch (Throwable $exception) {
                report($exception);

                $delivery->forceFill([
                    'status' => NewsletterDeliveryStatus::Failed,
                    'attempts' => $delivery->attempts + 1,
                    'error_message' => Str::limit(
                        $exception->getMessage(),
                        60000,
                        ''
                    ),
                ])->save();
            }
        }

        $this->refreshCampaignProgress($campaign);

        return $processed;
    }

    private function prepareDeliveries(
        NewsletterCampaign $campaign
    ): void {
        if ($campaign->deliveries()->exists()) {
            return;
        }

        DB::transaction(function () use ($campaign): void {
            NewsletterSubscriber::query()
                ->where('status', NewsletterSubscriberStatus::Active->value)
                ->where('locale', $campaign->locale)
                ->orderBy('id')
                ->chunkById(500, function ($subscribers) use ($campaign): void {
                    $rows = $subscribers->map(fn ($subscriber): array => [
                        'newsletter_campaign_id' => $campaign->id,
                        'newsletter_subscriber_id' => $subscriber->id,
                        'email_snapshot' => $subscriber->email,
                        'status' => NewsletterDeliveryStatus::Pending->value,
                        'attempts' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])->all();

                    if ($rows !== []) {
                        NewsletterDelivery::query()->insertOrIgnore($rows);
                    }
                });

            $campaign->forceFill([
                'recipient_count' => $campaign->deliveries()->count(),
            ])->save();
        });
    }

    private function refreshCampaignProgress(
        NewsletterCampaign $campaign
    ): void {
        $sent = $campaign->deliveries()
            ->where('status', NewsletterDeliveryStatus::Sent->value)
            ->count();

        $failed = $campaign->deliveries()
            ->where('status', NewsletterDeliveryStatus::Failed->value)
            ->where('attempts', '>=', 3)
            ->count();

        $remaining = $campaign->deliveries()
            ->where(function ($query) {
                $query->where('status', NewsletterDeliveryStatus::Pending->value)
                    ->orWhere(function ($retry) {
                        $retry->where('status', NewsletterDeliveryStatus::Failed->value)
                            ->where('attempts', '<', 3);
                    });
            })
            ->exists();

        $campaign->forceFill([
            'sent_count' => $sent,
            'failed_count' => $failed,
            'status' => $remaining
                ? NewsletterCampaignStatus::Sending
                : NewsletterCampaignStatus::Sent,
            'sent_at' => $remaining ? null : now(),
        ])->save();
    }
}
