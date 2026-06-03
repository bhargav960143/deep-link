<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'id' => $slug,
            'name' => fake()->company(),
            'plan_slug' => 'free',
        ];
    }

    public function pro(): static
    {
        return $this->state(['plan_slug' => 'pro']);
    }

    public function business(): static
    {
        return $this->state(['plan_slug' => 'business']);
    }
}
