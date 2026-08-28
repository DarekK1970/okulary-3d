<?php

use App\Enums\DiscoveryDecision;
use App\Enums\DiscoveryRunStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovery_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('provider', 30);
            $table->string('model', 120);
            $table->string('status', 30)
                ->default(DiscoveryRunStatus::Running->value)
                ->index();
            $table->string('topic', 190)->nullable();
            $table->text('query');
            $table->unsignedSmallInteger('freshness_days')->default(7);
            $table->unsignedSmallInteger('requested_candidates')->default(10);
            $table->unsignedSmallInteger('saved_candidates')->default(0);
            $table->unsignedSmallInteger('skipped_candidates')->default(0);
            $table->unsignedSmallInteger('duplicate_candidates')->default(0);
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->longText('raw_response')->nullable();
            $table->timestamps();
        });

        Schema::create('discovery_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discovery_run_id')
                ->constrained('discovery_runs')
                ->cascadeOnDelete();
            $table->string('fingerprint', 64)->index();
            $table->string('cluster_key', 190);
            $table->string('title', 255);
            $table->text('angle')->nullable();
            $table->text('summary');
            $table->string('suggested_section', 60)->nullable()->index();
            $table->unsignedTinyInteger('relevance_score')->default(0)->index();
            $table->unsignedTinyInteger('novelty_score')->default(0);
            $table->unsignedTinyInteger('confidence_score')->default(0);
            $table->json('facts')->nullable();
            $table->json('keywords')->nullable();
            $table->string('decision', 30)
                ->default(DiscoveryDecision::Pending->value)
                ->index();
            $table->foreignId('decision_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();

            $table->index([
                'decision',
                'relevance_score',
            ]);
        });

        Schema::create('discovery_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discovery_candidate_id')
                ->constrained('discovery_candidates')
                ->cascadeOnDelete();
            $table->string('url', 1200);
            $table->string('url_hash', 64)->index();
            $table->string('title', 500)->nullable();
            $table->string('domain', 255)->index();
            $table->string('language', 10)->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->text('excerpt')->nullable();
            $table->string('source_type', 50)->nullable();
            $table->unsignedTinyInteger('credibility_score')->default(0);
            $table->timestamps();

            $table->unique([
                'discovery_candidate_id',
                'url_hash',
            ], 'discovery_source_candidate_url_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discovery_sources');
        Schema::dropIfExists('discovery_candidates');
        Schema::dropIfExists('discovery_runs');
    }
};
