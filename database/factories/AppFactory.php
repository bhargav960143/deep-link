<?php

namespace Database\Factories;

use App\Models\App;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppFactory extends Factory
{
    protected $model = App::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true) . ' App',
            'platform' => 'both',
            'ios_bundle_id' => 'com.' . fake()->domainWord() . '.' . fake()->domainWord(),
            'ios_team_id' => strtoupper(fake()->bothify('??########')),
            'ios_store_url' => 'https://apps.apple.com/app/id' . fake()->numerify('#########'),
            'android_package_name' => 'com.' . fake()->domainWord() . '.' . fake()->domainWord(),
            'android_sha256_fingerprints' => [$this->fakeSha256Fingerprint()],
            'android_store_url' => 'https://play.google.com/store/apps/details?id=com.' . fake()->domainWord(),
            'uri_scheme' => fake()->domainWord() . 'app',
            'web_fallback_url' => 'https://example.com',
            'is_active' => true,
        ];
    }

    public function ios(): static
    {
        return $this->state([
            'platform' => 'ios',
            'android_package_name' => null,
            'android_sha256_fingerprints' => null,
            'android_store_url' => null,
        ]);
    }

    public function android(): static
    {
        return $this->state([
            'platform' => 'android',
            'ios_bundle_id' => null,
            'ios_team_id' => null,
            'ios_store_url' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    private function fakeSha256Fingerprint(): string
    {
        $pairs = [];
        for ($i = 0; $i < 32; $i++) {
            $pairs[] = strtoupper(fake()->hexColor()[1] . fake()->hexColor()[2]);
        }
        return implode(':', $pairs);
    }
}
