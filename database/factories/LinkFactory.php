<?php

namespace Database\Factories;

use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LinkFactory extends Factory
{
    protected $model = Link::class;

    public function definition(): array
    {
        return [
            'short_code' => Str::random(6),
            'destination_path' => '/product/' . fake()->slug(2),
            'link_type' => 'both',
            'is_active' => true,
            'title' => fake()->sentence(3),
        ];
    }

    public function expired(): static
    {
        return $this->state([
            'expires_at' => now()->subDay(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function maxClicksReached(): static
    {
        return $this->state([
            'max_clicks' => 10,
            'click_count' => 10,
        ]);
    }

    public function withPassword(string $password = 'secret'): static
    {
        return $this->state(['password' => $password]);
    }
}
