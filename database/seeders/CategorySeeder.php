<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Electronics', 'description' => 'Electronic devices and components.'],
            ['name' => 'Office Supplies', 'description' => 'Everyday office equipment and stationery.'],
            ['name' => 'Packaging', 'description' => 'Materials used for packing and shipping.'],
            ['name' => 'Tools', 'description' => 'General-purpose maintenance tools.'],
            ['name' => 'Safety Equipment', 'description' => 'Protective and workplace safety supplies.'],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']],
            );
        }
    }
}
