<?php

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Models\Run;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Delivery>
 */
class DeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'run_id' => Run::factory(),
            'email' => fake()->safeEmail(),
            'name' => fake()->name(),
            'position' => 1,
            'status' => DeliveryStatus::Pending,
        ];
    }

    public function notified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryStatus::Notified,
            'notified_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryStatus::Completed,
            'notified_at' => now()->subMinutes(30),
            'completed_at' => now(),
        ]);
    }

    public function withoutName(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => null,
        ]);
    }
}
