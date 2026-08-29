<?php

namespace App\Console\Commands;

use App\Services\ReleaseReadinessService;
use Illuminate\Console\Command;

class ReleaseCheck extends Command
{
    protected $signature = 'app:release-check
        {--production : Apply strict production requirements}';

    protected $description =
        'Check application readiness before deployment or release';

    public function handle(
        ReleaseReadinessService $readiness
    ): int {
        $production = (bool) $this->option(
            'production'
        );

        $checks = $readiness->fullChecks(
            $production
        );

        $rows = [];

        foreach ($checks as $name => $check) {
            $status = $check['ok']
                ? 'OK'
                : ($check['required']
                    ? 'FAIL'
                    : 'WARN');

            $rows[] = [
                $name,
                $status,
                $check['message'],
            ];
        }

        $this->table(
            ['Check', 'Status', 'Details'],
            $rows
        );

        if (
            ! $readiness->requiredChecksPass(
                $checks
            )
        ) {
            $this->error(
                'Release check failed.'
            );

            return self::FAILURE;
        }

        $this->info(
            $production
                ? 'Production release check passed.'
                : 'Local release check passed.'
        );

        return self::SUCCESS;
    }
}
