<?php

namespace Database\Factories;

use App\Modules\Network\Models\Vlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Network\Models\Vlan>
 */
class VlanFactory extends Factory
{
    protected $model = Vlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vlan_id' => fake()->unique()->numberBetween(1, 4094),
            'vlan_name' => fake()->word() . ' VLAN',
            'network_address' => fake()->ipv4(),
            'cidr_suffix' => fake()->numberBetween(16, 30),
            'gateway' => fake()->optional()->ipv4(),
            'dhcp_from' => fake()->optional()->ipv4(),
            'dhcp_to' => fake()->optional()->ipv4(),
            'description' => fake()->sentence(),
            'internes_netz' => fake()->boolean(),
            'ipscan' => fake()->boolean(),
            'scan_interval_minutes' => 60,
            'last_scanned_at' => null,
        ];
    }

    /**
     * Indicate that the VLAN has ID 999.
     */
    public function vlan999(): static
    {
        return $this->state(fn (array $attributes) => [
            'vlan_id' => 999,
        ]);
    }

    /**
     * Indicate that the VLAN has scanning enabled.
     */
    public function withScanning(): static
    {
        return $this->state(fn (array $attributes) => [
            'ipscan' => true,
        ]);
    }

    /**
     * Indicate that the VLAN has DHCP configured.
     */
    public function withDhcp(): static
    {
        $networkParts = explode('.', $this->faker->ipv4());
        $baseIp = implode('.', array_slice($networkParts, 0, 3));

        return $this->state(fn (array $attributes) => [
            'dhcp_from' => "{$baseIp}.100",
            'dhcp_to' => "{$baseIp}.200",
        ]);
    }
}
