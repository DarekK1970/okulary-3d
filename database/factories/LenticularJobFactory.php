<?php

namespace Database\Factories;

use App\Enums\LenticularJobStatus;
use App\Models\LenticularJob;
use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LenticularJob>
 */
class LenticularJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lenticular_project_id' => LenticularProject::factory(),
            'source_file_id' => LenticularProjectFile::factory(),
            'operation' => 'extract_video_frames',
            'status' => LenticularJobStatus::Queued,
            'parameters' => ['selection' => ['start' => 0, 'end' => 12, 'step' => 3, 'jpeg_quality' => 95]],
        ];
    }
}
