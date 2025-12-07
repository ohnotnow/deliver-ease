<?php

namespace Database\Factories;

use App\Enums\RunStatus;
use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Run>
 */
class RunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pin = $this->faker->numerify('######');

        return [
            'business_id' => Business::factory(),
            'created_by_user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'pin_hash' => Hash::make($pin),
            'pin_hint' => substr($pin, -4),
            'status' => RunStatus::Pending,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RunStatus::InProgress,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RunStatus::Completed,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }
}
