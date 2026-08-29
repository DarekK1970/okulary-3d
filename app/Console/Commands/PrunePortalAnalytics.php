<?php

namespace App\Console\Commands;

use App\Models\PortalAnalyticsSession;
use Illuminate\Console\Command;

class PrunePortalAnalytics extends Command
{
    protected $signature = 'portal:analytics-prune
        {--days= : Retention in days}';

    protected $description =
        'Delete expired anonymous analytics sessions and their events';

    public function handle(): int
    {
        $days = (int) (
            $this->option('days')
            ?: config(
                'release.analytics_retention_days',
                180
            )
        );

        $days = max(
            30,
            min(3650, $days)
        );

        $cutoff = now()->subDays(
            $days
        );

        $deleted = PortalAnalyticsSession::query()
            ->where(
                'last_seen_at',
                '<',
                $cutoff
            )
            ->delete();

        $this->info(
            "Deleted {$deleted} expired analytics session(s); retention={$days} days."
        );

        return self::SUCCESS;
    }
}
