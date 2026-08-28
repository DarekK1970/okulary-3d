<?php

namespace App\Console\Commands;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Console\Command;

class PublishScheduledArticles extends Command
{
    protected $signature = 'articles:publish-scheduled';

    protected $description = 'Publikuje artykuły, których zaplanowana data publikacji już minęła';

    public function handle(): int
    {
        $count = Article::query()
            ->where('status', ArticleStatus::Scheduled->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update([
                'status' => ArticleStatus::Published->value,
                'updated_at' => now(),
            ]);

        $this->info("Opublikowano zaplanowanych artykułów: {$count}");

        return self::SUCCESS;
    }
}
