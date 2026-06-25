<?php

namespace Database\Factories;

use App\Models\WpSite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WpSite>
 */
class WpSiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'path' => fake()->filePath(),
        ];
    }
}
