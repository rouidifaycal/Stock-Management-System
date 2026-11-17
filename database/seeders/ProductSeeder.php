<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'category' => 'Electronics',
                'name' => 'USB-C Hub 7-in-1',
                'sku' => 'ELEC-USBHUB-001',
                'description' => 'Multiport USB-C hub with HDMI and card reader.',
                'price' => 49.95,
                'quantity' => 24,
                'min_stock' => 5,
            ],
            [
                'category' => 'Electronics',
                'name' => 'Wireless Optical Mouse',
                'sku' => 'ELEC-MOUSE-001',
                'description' => 'Ergonomic wireless mouse with USB receiver.',
                'price' => 24.50,
                'quantity' => 18,
                'min_stock' => 6,
            ],
            [
                'category' => 'Office Supplies',
                'name' => 'A4 Copy Paper Ream',
                'sku' => 'OFF-PAPER-A4-01',
                'description' => '500-sheet ream of standard A4 copy paper.',
                'price' => 6.25,
                'quantity' => 40,
                'min_stock' => 10,
            ],
            [
                'category' => 'Packaging',
                'name' => 'Shipping Box Medium',
                'sku' => 'PKG-BOX-MED-01',
                'description' => 'Corrugated cardboard box for parcel shipping.',
                'price' => 1.80,
                'quantity' => 32,
                'min_stock' => 8,
            ],
            [
                'category' => 'Safety Equipment',
                'name' => 'Protective Work Gloves',
                'sku' => 'SAFE-GLOVE-001',
                'description' => 'General-purpose coated work gloves.',
                'price' => 12.75,
                'quantity' => 0,
                'min_stock' => 4,
            ],
        ];

        foreach ($products as $product) {
            $category = Category::query()
                ->where('name', $product['category'])
                ->firstOrFail();

            Product::query()->updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'category_id' => $category->id,
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'quantity' => $product['quantity'],
                    'min_stock' => $product['min_stock'],
                    'image' => null,
                ],
            );
        }
    }
}
