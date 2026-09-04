<?php

namespace Database\Factories;

use App\Enums\FalAiJobOperation;
use App\Enums\FalAiJobStatus;
use App\Models\FalAiJob;
use App\Models\LenticularProject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<FalAiJob> */
class FalAiJobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lenticular_project_id' => LenticularProject::factory(),
            'user_id' => null,
            'operation' => FalAiJobOperation::ImageToVideo,
            'status' => FalAiJobStatus::Queued,
            'idempotency_key' => (string) Str::uuid(),
            'endpoint' => 'bytedance/seedance-2.5/image-to-video',
            'parameters' => ['resolution' => '720p', 'duration' => 4],
        ];
    }
}
