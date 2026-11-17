<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()
            ->where('email', 'test@example.com')
            ->firstOrFail();
        $supplier = Supplier::query()
            ->where('company_name', 'Northstar Components')
            ->firstOrFail();

        Product::query()
            ->where('quantity', '>', 0)
            ->each(function (Product $product) use ($user, $supplier): void {
                StockMovement::query()->firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'user_id' => $user->id,
                        'type' => 'entry',
                        'reason' => 'Initial stock',
                        'notes' => 'Opening stock recorded during sample data setup.',
                    ],
                    [
                        'supplier_id' => $supplier->id,
                        'quantity' => $product->quantity,
                    ],
                );
            });
    }
}
