<?php

namespace Database\Factories;

use App\Modules\Network\Models\IpAddress;
use App\Modules\Network\Models\Vlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Network\Models\IpAddress>
 */
class IpAddressFactory extends Factory
{
    protected $model = IpAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vlan_id' => Vlan::factory(),
            'ip_address' => fake()->unique()->ipv4(),
            'dns_name' => fake()->optional()->domainName(),
            'mac_address' => fake()->optional()->macAddress(),
            'is_online' => fake()->boolean(),
            'last_online_at' => fake()->optional()->dateTime(),
            'last_scanned_at' => fake()->optional()->dateTime(),
            'ping_ms' => fake()->optional()->randomFloat(2, 0.1, 100),
            'comment' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the IP address is online.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_online' => true,
            'last_online_at' => now(),
            'last_scanned_at' => now(),
            'ping_ms' => fake()->randomFloat(2, 0.1, 50),
        ]);
    }

    /**
     * Indicate that the IP address is offline.
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_online' => false,
            'last_scanned_at' => now(),
            'ping_ms' => null,
        ]);
    }

    /**
     * Indicate that the IP address has never been scanned.
     */
    public function neverScanned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_online' => false,
            'last_online_at' => null,
            'last_scanned_at' => null,
            'ping_ms' => null,
        ]);
    }
}
