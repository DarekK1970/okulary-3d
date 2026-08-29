<?php

use App\Enums\OrchestratorItemStatus;
use App\Enums\OrchestratorPlanStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'orchestrator_plans',
            function (Blueprint $table) {
                $table->id();

                $table->date('week_start')
                    ->unique();

                $table->date('week_end');

                $table->string(
                    'status',
                    30
                )->default(
                    OrchestratorPlanStatus::Draft->value
                )->index();

                $table->string(
                    'provider',
                    30
                );

                $table->string(
                    'model',
                    120
                );

                $table->text(
                    'editorial_summary'
                )->nullable();

                $table->foreignId(
                    'created_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId(
                    'approved_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp(
                    'approved_at'
                )->nullable();

                $table->timestamps();
            }
        );

        Schema::create(
            'orchestrator_plan_items',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId(
                    'orchestrator_plan_id'
                )
                    ->constrained(
                        'orchestrator_plans'
                    )
                    ->cascadeOnDelete();

                $table->foreignId(
                    'discovery_candidate_id'
                )
                    ->constrained(
                        'discovery_candidates'
                    )
                    ->restrictOnDelete();

                $table->foreignId(
                    'article_id'
                )
                    ->nullable()
                    ->constrained('articles')
                    ->nullOnDelete();

                $table->unsignedSmallInteger(
                    'position'
                )->default(1);

                $table->timestamp(
                    'planned_for'
                )->nullable()->index();

                $table->string(
                    'planned_title',
                    255
                );

                $table->text(
                    'editorial_angle'
                )->nullable();

                $table->text(
                    'rationale'
                )->nullable();

                $table->string(
                    'suggested_section',
                    60
                )->nullable();

                $table->string(
                    'status',
                    30
                )->default(
                    OrchestratorItemStatus::Planned->value
                )->index();

                $table->timestamp(
                    'generated_at'
                )->nullable();

                $table->timestamps();

                $table->unique(
                    'discovery_candidate_id',
                    'orchestrator_candidate_unique'
                );

                $table->index([
                    'orchestrator_plan_id',
                    'position',
                ]);
            }
        );

        Schema::create(
            'orchestrator_runs',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId(
                    'user_id'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId(
                    'orchestrator_plan_id'
                )
                    ->nullable()
                    ->constrained(
                        'orchestrator_plans'
                    )
                    ->nullOnDelete();

                $table->foreignId(
                    'orchestrator_plan_item_id'
                )
                    ->nullable()
                    ->constrained(
                        'orchestrator_plan_items'
                    )
                    ->nullOnDelete();

                $table->string(
                    'action',
                    30
                )->index();

                $table->string(
                    'provider',
                    30
                );

                $table->string(
                    'model',
                    120
                );

                $table->string(
                    'status',
                    30
                )->default('started')
                    ->index();

                $table->unsignedInteger(
                    'input_tokens'
                )->nullable();

                $table->unsignedInteger(
                    'output_tokens'
                )->nullable();

                $table->unsignedInteger(
                    'total_tokens'
                )->nullable();

                $table->unsignedInteger(
                    'request_chars'
                )->nullable();

                $table->unsignedInteger(
                    'response_chars'
                )->nullable();

                $table->timestamp(
                    'started_at'
                )->nullable();

                $table->timestamp(
                    'completed_at'
                )->nullable();

                $table->text(
                    'error_message'
                )->nullable();

                $table->longText(
                    'raw_response'
                )->nullable();

                $table->timestamps();

                $table->index([
                    'action',
                    'created_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'orchestrator_runs'
        );

        Schema::dropIfExists(
            'orchestrator_plan_items'
        );

        Schema::dropIfExists(
            'orchestrator_plans'
        );
    }
};
