<?php

namespace Database\Factories;

use App\Models\LenticularJob;
use App\Models\LenticularJobEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LenticularJobEvent>
 */
class LenticularJobEventFactory extends Factory
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
            'type' => 'created',
            'payload' => [],
        ];
    }
}
