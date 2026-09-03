<?php

namespace Database\Factories;

use App\Enums\LenticularProjectStatus;
use App\Models\LenticularProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LenticularProject>
 */
class LenticularProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'status' => LenticularProjectStatus::Draft,
            'settings' => [],
        ];
    }
}
