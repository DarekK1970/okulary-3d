<?php

namespace App\Services;

use App\Enums\PartnerLinkStatus;
use App\Mail\PartnerBacklinkStatusMail;
use App\Models\PartnerLink;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PartnerBacklinkMonitor
{
    private const UNREACHABLE_FAILURES_BEFORE_SUSPEND = 2;

    public function __construct(
        private readonly PartnerBacklinkChecker $checker
    ) {
    }

    /**
     * @return array{reachable:bool,backlink_found:bool,http_status:?int,error:?string,checked_url:string,status_changed:bool,consecutive_failures:int,current_status:string}
     */
    public function check(PartnerLink $partner, bool $notify = true): array
    {
        $partner->refresh();
        $previousStatus = $partner->status;
        $result = $this->checker->check($partner);
        $managed = $this->isManagedPartner($partner);

        $updates = [
            'last_checked_at' => now(),
            'last_http_status' => $result['http_status'],
        ];

        if ($result['backlink_found']) {
            $updates['last_backlink_found_at'] = now();
            $updates['consecutive_failures'] = 0;
            $updates['last_check_error'] = null;

            if (
                $managed
                && in_array($partner->status, [
                    PartnerLinkStatus::SuspendedBacklink,
                    PartnerLinkStatus::SuspendedUnreachable,
                ], true)
            ) {
                $updates['status'] = PartnerLinkStatus::Active;
            }
        } elseif ($result['reachable']) {
            $updates['consecutive_failures'] = 0;
            $updates['last_check_error'] = 'backlink_missing';

            if ($managed) {
                $updates['status'] = PartnerLinkStatus::SuspendedBacklink;
            }
        } else {
            $failures = min(255, ((int) $partner->consecutive_failures) + 1);
            $updates['consecutive_failures'] = $failures;
            $updates['last_check_error'] = $result['error'];

            if (
                $managed
                && $failures >= self::UNREACHABLE_FAILURES_BEFORE_SUSPEND
            ) {
                $updates['status'] = PartnerLinkStatus::SuspendedUnreachable;
            }
        }

        $partner->forceFill($updates)->save();
        $partner->refresh();

        $statusChanged = $partner->status !== $previousStatus;

        if ($notify && $statusChanged) {
            $this->sendStatusNotification($partner, $previousStatus);
        }

        return array_merge($result, [
            'status_changed' => $statusChanged,
            'consecutive_failures' => (int) $partner->consecutive_failures,
            'current_status' => $partner->status->value,
        ]);
    }

    private function isManagedPartner(PartnerLink $partner): bool
    {
        return $partner->email_verified_at !== null
            && $partner->approved_at !== null
            && ! in_array($partner->status, [
                PartnerLinkStatus::Banned,
                PartnerLinkStatus::Rejected,
                PartnerLinkStatus::EmailPending,
                PartnerLinkStatus::Pending,
            ], true);
    }

    private function sendStatusNotification(
        PartnerLink $partner,
        PartnerLinkStatus $previousStatus
    ): void {
        $event = match ($partner->status) {
            PartnerLinkStatus::SuspendedBacklink => 'suspended_backlink',
            PartnerLinkStatus::SuspendedUnreachable => 'suspended_unreachable',
            PartnerLinkStatus::Active => in_array($previousStatus, [
                PartnerLinkStatus::SuspendedBacklink,
                PartnerLinkStatus::SuspendedUnreachable,
            ], true) ? 'restored' : null,
            default => null,
        };

        if ($event === null || ! $partner->email) {
            return;
        }

        $locale = in_array(
            $partner->source_locale,
            array_keys(config('locales.supported', [])),
            true
        ) ? $partner->source_locale : config('locales.default', 'pl');

        try {
            Mail::to($partner->email)
                ->locale($locale)
                ->send(new PartnerBacklinkStatusMail($partner, $event));
        } catch (\Throwable $exception) {
            Log::warning('Partner backlink status e-mail could not be sent.', [
                'partner_id' => $partner->id,
                'event' => $event,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
