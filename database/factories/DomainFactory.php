<?php

namespace Database\Factories;

use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

class DomainFactory extends Factory
{
    protected $model = Domain::class;

    public function definition(): array
    {
        return [
            'domain' => fake()->unique()->domainWord() . '.deeplink.test',
            'type' => 'subdomain',
            'is_primary' => true,
            'status' => 'active',
            'verified_at' => now(),
        ];
    }

    public function custom(): static
    {
        return $this->state([
            'domain' => fake()->unique()->domainName(),
            'type' => 'custom',
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'status' => 'pending',
            'verified_at' => null,
        ]);
    }
}
