<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockMovementService;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public function run(StockMovementService $stockMovementService): void
    {
        $administrator = User::query()
            ->where('role', UserRole::Administrator->value)
            ->orderBy('id')
            ->first();

        if ($administrator === null) {
            throw new RuntimeException(
                'Create the first Administrator with stockflow:bootstrap-administrator before running DemoSeeder.',
            );
        }

        $electronics = Category::query()->firstOrCreate(
            ['name' => 'Demo electronics'],
            ['description' => 'Devices and accessories for the demonstration.'],
        );
        $warehouse = Category::query()->firstOrCreate(
            ['name' => 'Demo warehouse supplies'],
            ['description' => 'Storage and packing supplies for the demonstration.'],
        );
        $safety = Category::query()->firstOrCreate(
            ['name' => 'Demo safety equipment'],
            ['description' => 'Protective equipment for the demonstration.'],
        );

        $electronicsSupplier = Supplier::query()->firstOrCreate(
            ['company_name' => 'StockFlow Demo Electronics Supply'],
            [
                'name' => 'Alex Morgan',
                'email' => 'orders@electronics.example',
                'phone' => '555-0101',
                'address' => '100 Demo Avenue',
            ],
        );
        $generalSupplier = Supplier::query()->firstOrCreate(
            ['company_name' => 'StockFlow Demo General Supply'],
            [
                'name' => 'Jamie Rivera',
                'email' => 'sales@general.example',
                'phone' => '555-0102',
                'address' => '200 Sample Street',
            ],
        );

        $dockingStation = $this->product(
            $electronics,
            'USB-C Docking Station',
            'DEMO-ELEC-DOCK-001',
            'Multiport docking station for a laptop workstation.',
            '129.95',
            8,
        );
        $shippingCarton = $this->product(
            $warehouse,
            'Shipping Carton Medium',
            'DEMO-WH-CARTON-001',
            'Reusable medium carton for warehouse dispatch.',
            '2.40',
            5,
        );
        $protectiveGloves = $this->product(
            $safety,
            'Protective Work Gloves',
            'DEMO-SAFE-GLOVE-001',
            'General-purpose protective gloves for warehouse work.',
            '11.50',
            3,
        );

        $this->recordMovementOnce(
            $stockMovementService,
            $administrator,
            $dockingStation,
            $electronicsSupplier,
            'entry',
            30,
            'Demo receipt: docking station replenishment.',
        );
        $this->recordMovementOnce(
            $stockMovementService,
            $administrator,
            $dockingStation,
            null,
            'exit',
            4,
            'Demo dispatch: docking station customer order.',
            'Customer order',
        );
        $this->recordMovementOnce(
            $stockMovementService,
            $administrator,
            $shippingCarton,
            $generalSupplier,
            'entry',
            8,
            'Demo receipt: shipping carton replenishment.',
        );
        $this->recordMovementOnce(
            $stockMovementService,
            $administrator,
            $shippingCarton,
            null,
            'exit',
            4,
            'Demo dispatch: shipping carton customer order.',
            'Customer order',
        );
        $this->recordMovementOnce(
            $stockMovementService,
            $administrator,
            $protectiveGloves,
            $generalSupplier,
            'entry',
            5,
            'Demo receipt: protective glove replenishment.',
        );
        $this->recordMovementOnce(
            $stockMovementService,
            $administrator,
            $protectiveGloves,
            null,
            'exit',
            5,
            'Demo dispatch: protective glove issue.',
            'Internal use',
        );
    }

    private function product(
        Category $category,
        string $name,
        string $sku,
        string $description,
        string $price,
        int $minimumStock,
    ): Product {
        return Product::query()->firstOrCreate(
            ['sku' => $sku],
            [
                'category_id' => $category->getKey(),
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'quantity' => 0,
                'min_stock' => $minimumStock,
            ],
        );
    }

    private function recordMovementOnce(
        StockMovementService $stockMovementService,
        User $administrator,
        Product $product,
        ?Supplier $supplier,
        string $type,
        int $quantity,
        string $notes,
        ?string $reason = null,
    ): void {
        $movementExists = StockMovement::query()
            ->where('product_id', $product->getKey())
            ->where('type', $type)
            ->where('notes', $notes)
            ->exists();

        if ($movementExists) {
            return;
        }

        $stockMovementService->record($administrator, [
            'product_id' => $product->getKey(),
            'type' => $type,
            'quantity' => $quantity,
            'supplier_id' => $type === 'entry' ? $supplier?->getKey() : null,
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }
}
