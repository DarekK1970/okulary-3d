<?php

namespace App\Console\Commands;

use App\Services\NewsletterCampaignService;
use Illuminate\Console\Command;

class SendDueNewsletterCampaigns extends Command
{
    protected $signature = 'newsletter:send-due {--limit=100 : Maksymalna liczba wiadomości na jedno uruchomienie}';

    protected $description = 'Wysyła zaplanowane kampanie newslettera partiami.';

    public function handle(
        NewsletterCampaignService $service
    ): int {
        $limit = max(1, min(1000, (int) $this->option('limit')));

        $processed = $service->processDueCampaigns($limit);

        $this->info("Przetworzono wiadomości: {$processed}");

        return self::SUCCESS;
    }
}
