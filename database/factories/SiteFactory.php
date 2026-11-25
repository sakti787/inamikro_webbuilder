<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $businessName = fake()->company();
        $baseSlug = Str::slug($businessName) ?: 'usaha';

        return [
            'user_id' => User::factory(),
            'template_id' => fake()->randomElement(['kuliner', 'jasa', 'toko']),
            'slug' => $baseSlug.'-'.fake()->unique()->numberBetween(100, 99999),
            'status' => 'draft',
            'brand_color' => '#dc2626',
            'content' => [
                'hero' => [
                    'title' => $businessName,
                    'subtitle' => fake()->sentence(),
                ],
            ],
            'rendered_html' => null,
            'published_at' => null,
        ];
    }

    /**
     * Indicate that the site is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
            'rendered_html' => '<html><body>Published Site</body></html>',
            'published_at' => now(),
        ]);
    }
}
