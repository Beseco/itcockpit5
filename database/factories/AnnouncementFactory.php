<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['info', 'maintenance', 'critical']),
            'message' => fake()->sentence(),
            'starts_at' => null,
            'ends_at' => null,
            'is_fixed' => false,
            'fixed_at' => null,
        ];
    }

    /**
     * Indicate that the announcement is critical.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'critical',
        ]);
    }

    /**
     * Indicate that the announcement is for maintenance.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'maintenance',
        ]);
    }

    /**
     * Indicate that the announcement is informational.
     */
    public function info(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'info',
        ]);
    }

    /**
     * Indicate that the announcement has been fixed.
     */
    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_fixed' => true,
            'fixed_at' => now(),
        ]);
    }

    /**
     * Indicate that the announcement has a time window.
     */
    public function withTimeWindow(): static
    {
        $startsAt = fake()->dateTimeBetween('-1 week', '+1 week');
        $endsAt = fake()->dateTimeBetween($startsAt, '+2 weeks');

        return $this->state(fn (array $attributes) => [
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }
}
