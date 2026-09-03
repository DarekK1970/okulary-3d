<?php

namespace Database\Factories;

use App\Models\ProcessingMachine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcessingMachine>
 */
class ProcessingMachineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'machine_id' => fake()->unique()->slug(2),
            'api_key_id' => fake()->unique()->uuid(),
            'api_secret' => str_repeat('s', 32),
            'capabilities' => ['analyze_video:v1', 'extract_video_frames:v1'],
            'is_active' => true,
        ];
    }
}
