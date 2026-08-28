<?php

namespace App\Console\Commands;

use App\Services\DiscoveryService;
use App\Services\DiscoverySettingsService;
use Illuminate\Console\Command;
use Throwable;

class RunDiscoveryAgent extends Command
{
    protected $signature = 'discovery:run
        {topic? : Topic or preset research area}
        {--query= : Additional editorial query}
        {--days= : Freshness window in days}
        {--limit= : Maximum number of candidates}';

    protected $description =
        'Run the web Discovery Agent and save researched topic candidates.';

    public function handle(
        DiscoveryService $discovery,
        DiscoverySettingsService $settings
    ): int {
        $topic = trim(
            (string) ($this->argument('topic') ?: '')
        );

        if ($topic === '') {
            $topic = $settings->topics()[0] ?? '';
        }

        if ($topic === '') {
            $this->error('No discovery topic configured.');

            return self::FAILURE;
        }

        try {
            $run = $discovery->run(
                $topic,
                (string) ($this->option('query') ?: $topic),
                null,
                $this->option('days') !== null
                    ? (int) $this->option('days')
                    : null,
                $this->option('limit') !== null
                    ? (int) $this->option('limit')
                    : null
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(
            sprintf(
                'Discovery completed. Saved: %d, skipped: %d, duplicates: %d.',
                $run->saved_candidates,
                $run->skipped_candidates,
                $run->duplicate_candidates
            )
        );

        return self::SUCCESS;
    }
}
