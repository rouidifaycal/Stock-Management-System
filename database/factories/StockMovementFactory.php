<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'supplier_id' => fake()->boolean(50) ? Supplier::factory() : null,
            'type' => fake()->randomElement(['entry', 'exit']),
            'quantity' => fake()->numberBetween(1, 50),
            'reason' => fake()->optional()->randomElement(['sale', 'damage', 'return']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
