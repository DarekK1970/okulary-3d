<?php

namespace App\Console\Commands;

use App\Enums\FalAiJobStatus;
use App\Jobs\ProcessFalAiWebhook;
use App\Models\FalAiJob;
use App\Services\FalAiClient;
use App\Services\FalAiJobService;
use Illuminate\Console\Command;

class SyncFalAiJobs extends Command
{
    protected $signature = 'fal-ai:sync {--limit=50}';

    protected $description = 'Synchronize unfinished fal.ai jobs as a webhook fallback';

    public function handle(FalAiClient $client, FalAiJobService $jobs): int
    {
        FalAiJob::query()->whereIn('status', [FalAiJobStatus::Submitted, FalAiJobStatus::Processing])
            ->whereNotNull('provider_request_id')->oldest()->limit(max(1, min(200, (int) $this->option('limit'))))
            ->get()->each(function (FalAiJob $job) use ($client, $jobs): void {
                $status = $client->status($job->endpoint, $job->provider_request_id);
                if (($status['status'] ?? null) === 'IN_PROGRESS' && $job->status === FalAiJobStatus::Submitted) {
                    $jobs->markProcessing($job, stage: 'provider_processing');
                }
                if (($status['status'] ?? null) === 'COMPLETED') {
                    $result = $client->result($job->endpoint, $job->provider_request_id);
                    ProcessFalAiWebhook::dispatchSync(['request_id' => $job->provider_request_id, 'status' => isset($result['error']) ? 'ERROR' : 'OK', 'error' => $result['error'] ?? null, 'payload' => $result]);
                }
            });

        return self::SUCCESS;
    }
}
