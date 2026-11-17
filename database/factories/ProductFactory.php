<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->bothify('SKU-??##??##'),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->randomFloat(2, 1, 1000),
            'quantity' => 0,
            'min_stock' => fake()->numberBetween(0, 20),
            'image' => null,
        ];
    }
}
