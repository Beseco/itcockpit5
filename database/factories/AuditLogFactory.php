<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
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
            'module' => fake()->randomElement(['User', 'Announcement', 'Core', 'Module']),
            'action' => fake()->randomElement([
                'User created',
                'User updated',
                'User deleted',
                'Announcement created',
                'Announcement updated',
                'Login successful',
                'Module enabled',
            ]),
            'payload' => [
                'user_id' => fake()->numberBetween(1, 100),
                'user_email' => fake()->email(),
            ],
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the log is for a user action.
     */
    public function userAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'module' => 'User',
            'action' => fake()->randomElement(['User created', 'User updated', 'User deleted', 'User activated', 'User deactivated']),
        ]);
    }

    /**
     * Indicate that the log is for an announcement action.
     */
    public function announcementAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'module' => 'Announcement',
            'action' => fake()->randomElement(['Announcement created', 'Announcement updated', 'Announcement deleted', 'Announcement marked as fixed']),
        ]);
    }

    /**
     * Indicate that the log is for a login action.
     */
    public function loginAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'module' => 'Auth',
            'action' => 'Login successful',
        ]);
    }
}
