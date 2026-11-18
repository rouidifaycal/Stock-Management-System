<?php

use App\Enums\UserRole;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Reports\CurrentInventoryReport;
use App\Filament\Pages\Reports\StockMovementReport;
use App\Filament\Widgets\DashboardWelcome;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryReportService;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

it('calculates current inventory value and filters products by category and stock status', function () {
    $this->actingAs(User::factory()->stockManager()->create());

    $tools = Category::factory()->create(['name' => 'Tools']);
    $furniture = Category::factory()->create(['name' => 'Furniture']);
    $normal = Product::factory()->for($tools)->create([
        'name' => 'Bench',
        'quantity' => 10,
        'min_stock' => 5,
        'price' => '2.50',
    ]);
    $low = Product::factory()->for($tools)->create([
        'name' => 'Drill',
        'quantity' => 4,
        'min_stock' => 5,
        'price' => '3.00',
    ]);
    $out = Product::factory()->for($furniture)->create([
        'name' => 'Stool',
        'quantity' => 0,
        'min_stock' => 0,
        'price' => '20.00',
    ]);

    $inventoryValue = app(InventoryReportService::class)
        ->inventory()
        ->whereKey($normal->getKey())
        ->value('inventory_value');

    expect((float) $inventoryValue)->toBe(25.0);

    Livewire::test(CurrentInventoryReport::class)
        ->assertCanSeeTableRecords([$normal, $low, $out]);

    Livewire::test(CurrentInventoryReport::class)
        ->filterTable('category', $tools->getKey())
        ->assertCanSeeTableRecords([$normal, $low])
        ->assertCanNotSeeTableRecords([$out]);

    Livewire::test(CurrentInventoryReport::class)
        ->filterTable('stock_status', 'low')
        ->assertCanSeeTableRecords([$low])
        ->assertCanNotSeeTableRecords([$normal, $out]);

    Livewire::test(CurrentInventoryReport::class)
        ->filterTable('stock_status', 'out_of_stock')
        ->assertCanSeeTableRecords([$out])
        ->assertCanNotSeeTableRecords([$normal, $low]);
});

it('filters stock movements by date, category, supplier, type, and product', function () {
    $this->actingAs(User::factory()->administrator()->create());

    $tools = Category::factory()->create(['name' => 'Tools']);
    $furniture = Category::factory()->create(['name' => 'Furniture']);
    $toolProduct = Product::factory()->for($tools)->create(['name' => 'Drill']);
    $secondTool = Product::factory()->for($tools)->create(['name' => 'Saw']);
    $furnitureProduct = Product::factory()->for($furniture)->create(['name' => 'Stool']);
    $supplier = Supplier::factory()->create(['name' => 'North Supply']);
    $otherSupplier = Supplier::factory()->create(['name' => 'South Supply']);
    $user = auth()->user();

    $toolEntry = StockMovement::factory()->for($user)->for($toolProduct)->for($supplier)->create([
        'type' => 'entry',
        'created_at' => '2026-11-12 08:00:00',
        'updated_at' => '2026-11-12 08:00:00',
    ]);
    $secondToolEntry = StockMovement::factory()->for($user)->for($secondTool)->for($supplier)->create([
        'type' => 'entry',
        'created_at' => '2026-11-13 08:00:00',
        'updated_at' => '2026-11-13 08:00:00',
    ]);
    $wrongCategory = StockMovement::factory()->for($user)->for($furnitureProduct)->for($supplier)->create([
        'type' => 'entry',
        'created_at' => '2026-11-13 08:00:00',
        'updated_at' => '2026-11-13 08:00:00',
    ]);
    $wrongSupplier = StockMovement::factory()->for($user)->for($toolProduct)->for($otherSupplier)->create([
        'type' => 'entry',
        'created_at' => '2026-11-13 08:00:00',
        'updated_at' => '2026-11-13 08:00:00',
    ]);
    $wrongType = StockMovement::factory()->for($user)->for($toolProduct)->for($supplier)->create([
        'type' => 'exit',
        'created_at' => '2026-11-13 08:00:00',
        'updated_at' => '2026-11-13 08:00:00',
    ]);
    $outsideDateRange = StockMovement::factory()->for($user)->for($toolProduct)->for($supplier)->create([
        'type' => 'entry',
        'created_at' => '2026-10-30 08:00:00',
        'updated_at' => '2026-10-30 08:00:00',
    ]);

    Livewire::test(StockMovementReport::class)
        ->filterTable('movement_date', [
            'from' => '2026-11-01',
            'until' => '2026-11-15',
        ])
        ->filterTable('category', $tools->getKey())
        ->filterTable('supplier', $supplier->getKey())
        ->filterTable('type', 'entry')
        ->assertCanSeeTableRecords([$toolEntry, $secondToolEntry])
        ->assertCanNotSeeTableRecords([$wrongCategory, $wrongSupplier, $wrongType, $outsideDateRange]);

    Livewire::test(StockMovementReport::class)
        ->filterTable('product', $toolProduct->getKey())
        ->assertCanSeeTableRecords([$toolEntry, $wrongSupplier, $wrongType, $outsideDateRange])
        ->assertCanNotSeeTableRecords([$secondToolEntry, $wrongCategory]);
});

it('allows administrators and stock managers to access reports', function (UserRole $role) {
    $this->actingAs(User::factory()->create(['role' => $role]));

    $this->get(CurrentInventoryReport::getUrl(isAbsolute: false))->assertOk();
    $this->get(StockMovementReport::getUrl(isAbsolute: false))->assertOk();
})->with([
    'Administrator' => [UserRole::Administrator],
    'Stock Manager' => [UserRole::StockManager],
]);

it('denies employees direct report access and report queries', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Employee]));

    $this->get(CurrentInventoryReport::getUrl(isAbsolute: false))->assertForbidden();
    $this->get(StockMovementReport::getUrl(isAbsolute: false))->assertForbidden();

    expect(fn () => app(InventoryReportService::class)->inventory())
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(InventoryReportService::class)->stockMovements())
        ->toThrow(AuthorizationException::class);
});

it('shows branded dashboard content and helpful empty report states', function () {
    $this->actingAs(User::factory()->administrator()->create());

    $this->get(Dashboard::getUrl(isAbsolute: false))
        ->assertOk()
        ->assertSeeLivewire(DashboardWelcome::class)
        ->assertSee('StockFlow')
        ->assertDontSee('Documentation')
        ->assertDontSee('GitHub');

    Livewire::test(DashboardWelcome::class)
        ->assertSee('Welcome back');

    Livewire::test(CurrentInventoryReport::class)
        ->assertSee('No inventory records found');

    Livewire::test(StockMovementReport::class)
        ->assertSee('No stock movements found');
});
