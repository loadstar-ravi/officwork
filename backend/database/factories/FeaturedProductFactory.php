<?php

namespace Database\Factories;

use App\Models\FeaturedProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeaturedProduct>
 */
class FeaturedProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => fake()->unique()->numberBetween(1, 20),
            'position' => fake()->numberBetween(1, 5),
        ];
    }
}
