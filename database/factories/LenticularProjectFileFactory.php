<?php

namespace Database\Factories;

use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LenticularProjectFile>
 */
class LenticularProjectFileFactory extends Factory
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
            'kind' => 'source_video',
            'disk' => 'local',
            'path' => 'lenticular/sources/test.mp4',
            'original_name' => 'test.mp4',
            'media_type' => 'video/mp4',
            'size_bytes' => 4,
            'sha256' => hash('sha256', 'test'),
            'metadata' => [],
        ];
    }
}
