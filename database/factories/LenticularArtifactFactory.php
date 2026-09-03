<?php

namespace Database\Factories;

use App\Models\LenticularArtifact;
use App\Models\LenticularJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LenticularArtifact>
 */
class LenticularArtifactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lenticular_job_id' => LenticularJob::factory(),
            'kind' => 'frames',
            'disk' => 'local',
            'path' => 'lenticular/results/test/frames.zip',
            'media_type' => 'application/zip',
            'size_bytes' => 3,
            'sha256' => hash('sha256', 'zip'),
        ];
    }
}
