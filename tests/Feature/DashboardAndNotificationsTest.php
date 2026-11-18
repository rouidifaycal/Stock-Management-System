<?php

use App\Enums\UserRole;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\InventoryStatsOverview;
use App\Filament\Widgets\LowStockProducts;
use App\Filament\Widgets\RecentStockMovements;
use App\Filament\Widgets\StockActivityOverview;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\ProductLowStockNotification;
use App\Services\InventoryDashboardService;
use App\Services\StockMovementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

it('calculates inventory statistics and shows them to report roles', function (UserRole $role) {
    $this->actingAs(User::factory()->create(['role' => $role]));

    $tools = Category::factory()->create();
    $office = Category::factory()->create();
    Supplier::factory()->create();

    Product::factory()->for($tools)->create([
        'quantity' => 10,
        'min_stock' => 5,
        'price' => '2.50',
    ]);
    Product::factory()->for($tools)->create([
        'quantity' => 4,
        'min_stock' => 5,
        'price' => '1.25',
    ]);
    Product::factory()->for($office)->create([
        'quantity' => 0,
        'min_stock' => 3,
        'price' => '100.00',
    ]);

    $statistics = app(InventoryDashboardService::class)->statistics();

    expect($statistics['products'])->toBe(3)
        ->and($statistics['categories'])->toBe(2)
        ->and($statistics['suppliers'])->toBe(1)
        ->and($statistics['units'])->toBe(14)
        ->and((float) $statistics['inventory_value'])->toBe(30.0)
        ->and($statistics['normal'])->toBe(1)
        ->and($statistics['low'])->toBe(1)
        ->and($statistics['out_of_stock'])->toBe(1);

    Livewire::test(InventoryStatsOverview::class)
        ->assertSee('Total products')
        ->assertSee('Inventory value')
        ->assertSee('Low stock')
        ->assertSee('Out of stock');
})->with([
    'Administrator' => [UserRole::Administrator],
    'Stock Manager' => [UserRole::StockManager],
]);

it('handles an empty inventory without errors', function () {
    $this->actingAs(User::factory()->administrator()->create());

    $statistics = app(InventoryDashboardService::class)->statistics();
    $activity = app(InventoryDashboardService::class)->stockActivity('30');

    expect($statistics['products'])->toBe(0)
        ->and($statistics['categories'])->toBe(0)
        ->and($statistics['suppliers'])->toBe(0)
        ->and($statistics['units'])->toBe(0)
        ->and((float) $statistics['inventory_value'])->toBe(0.0)
        ->and($statistics['normal'])->toBe(0)
        ->and($statistics['low'])->toBe(0)
        ->and($statistics['out_of_stock'])->toBe(0)
        ->and($activity['entries'])->toBe(0)
        ->and($activity['entry_units'])->toBe(0)
        ->and($activity['exits'])->toBe(0)
        ->and($activity['exit_units'])->toBe(0);

    Livewire::test(LowStockProducts::class)
        ->assertSee('No low-stock products');
});

it('uses a MariaDB-safe alias for the out-of-stock aggregate', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $productAggregateSql = null;

    DB::listen(function (QueryExecuted $query) use (&$productAggregateSql): void {
        if (str_contains($query->sql, 'COUNT(*)') && str_contains($query->sql, 'quantity = 0')) {
            $productAggregateSql = $query->sql;
        }
    });

    $statistics = app(InventoryDashboardService::class)->statistics();

    expect($productAggregateSql)->not->toBeNull()
        ->and($productAggregateSql)->toContain('AS out_of_stock')
        ->and($productAggregateSql)->not->toContain('AS out,')
        ->and($statistics['out_of_stock'])->toBe(0);
});

it('shows only low and out of stock products with their distinct statuses', function () {
    $this->actingAs(User::factory()->stockManager()->create());

    $normal = Product::factory()->create([
        'name' => 'Healthy item',
        'quantity' => 8,
        'min_stock' => 5,
    ]);
    $low = Product::factory()->create([
        'name' => 'Low item',
        'quantity' => 3,
        'min_stock' => 5,
    ]);
    $out = Product::factory()->create([
        'name' => 'Empty item',
        'quantity' => 0,
        'min_stock' => 0,
    ]);

    Livewire::test(LowStockProducts::class)
        ->assertCanSeeTableRecords([$low, $out])
        ->assertCanNotSeeTableRecords([$normal])
        ->assertSee('Low stock')
        ->assertSee('Out of stock');
});

it('shows a paginated recent movement list with related user and supplier', function () {
    $user = User::factory()->stockManager()->create(['name' => 'Riley Manager']);
    $product = Product::factory()->create([
        'name' => 'Cordless drill',
        'sku' => 'DRILL-RECENT-01',
    ]);
    $supplier = Supplier::factory()->create(['name' => 'North Supply']);
    $oldest = null;
    $latest = null;

    foreach (range(10, 0) as $daysAgo) {
        $createdAt = now()->subDays($daysAgo);
        $movement = StockMovement::factory()
            ->for($user)
            ->for($product)
            ->create([
                'supplier_id' => $daysAgo === 0 ? $supplier->getKey() : null,
                'type' => $daysAgo === 0 ? 'entry' : 'exit',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

        if ($daysAgo === 10) {
            $oldest = $movement;
        }

        if ($daysAgo === 0) {
            $latest = $movement;
        }
    }

    $this->actingAs($user);

    Livewire::test(RecentStockMovements::class)
        ->assertCanSeeTableRecords([$latest])
        ->assertCanNotSeeTableRecords([$oldest])
        ->assertSee('Cordless drill')
        ->assertSee('Riley Manager')
        ->assertSee('North Supply')
        ->assertSee('Stock entry');
});

it('aggregates stock activity within the selected period', function () {
    $user = User::factory()->administrator()->create();
    $this->actingAs($user);
    $product = Product::factory()->create();
    $insidePeriod = now()->subDays(6);
    $outsidePeriod = now()->subDays(10);

    StockMovement::factory()->for($user)->for($product)->create([
        'type' => 'entry',
        'quantity' => 4,
        'created_at' => $insidePeriod,
        'updated_at' => $insidePeriod,
    ]);
    StockMovement::factory()->for($user)->for($product)->create([
        'type' => 'exit',
        'quantity' => 2,
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);
    StockMovement::factory()->for($user)->for($product)->create([
        'type' => 'exit',
        'quantity' => 9,
        'created_at' => $outsidePeriod,
        'updated_at' => $outsidePeriod,
    ]);

    $activity = app(InventoryDashboardService::class)->stockActivity('7');

    expect($activity['period'])->toBe('Last 7 days')
        ->and($activity['entries'])->toBe(1)
        ->and($activity['entry_units'])->toBe(4)
        ->and($activity['exits'])->toBe(1)
        ->and($activity['exit_units'])->toBe(2);

    Livewire::test(StockActivityOverview::class, [
        'pageFilters' => ['period' => '7'],
    ])
        ->assertSee('4 units received, Last 7 days')
        ->assertSee('2 units removed, Last 7 days');
});

it('includes stock activity recorded today in the selected period', function () {
    $user = User::factory()->administrator()->create();
    $this->actingAs($user);
    $product = Product::factory()->create();
    $todayAtThree = now()->copy()->startOfDay()->addHours(15);

    $this->travelTo($todayAtThree, function () use ($user, $product): void {
        $movementTime = now()->copy()->subMinutes(30);

        StockMovement::factory()->for($user)->for($product)->create([
            'type' => 'entry',
            'quantity' => 3,
            'created_at' => $movementTime,
            'updated_at' => $movementTime,
        ]);

        $activity = app(InventoryDashboardService::class)->stockActivity('7');

        expect($activity['entries'])->toBe(1)
            ->and($activity['entry_units'])->toBe(3);
    });
});

it('provides an activity period selector on the report dashboard', function () {
    $this->actingAs(User::factory()->stockManager()->create());

    Livewire::test(Dashboard::class)
        ->assertSet('filters.period', '30')
        ->assertSee('Stock activity period')
        ->assertSee('Last 30 days');
});

it('hides report widgets from employees and denies direct report queries', function () {
    $employee = User::factory()->create(['role' => UserRole::Employee]);
    $this->actingAs($employee);

    $this->get('/admin')
        ->assertOk()
        ->assertDontSee('Stock activity period')
        ->assertDontSee('Total products')
        ->assertDontSee('Low-stock products')
        ->assertDontSee('Recent stock movements');

    expect(fn () => app(InventoryDashboardService::class)->statistics())
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(InventoryDashboardService::class)->lowStockProducts())
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(InventoryDashboardService::class)->recentMovements())
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(InventoryDashboardService::class)->stockActivity('30'))
        ->toThrow(AuthorizationException::class);
});

it('notifies administrators and stock managers on each new low stock condition only', function () {
    $administrator = User::factory()->administrator()->create();
    $stockManager = User::factory()->stockManager()->create();
    $employee = User::factory()->create(['role' => UserRole::Employee]);
    $product = Product::factory()->create([
        'name' => 'Desk lamp',
        'sku' => 'LAMP-LOW-01',
        'quantity' => 6,
        'min_stock' => 5,
    ]);
    $service = app(StockMovementService::class);

    $service->record($stockManager, [
        'type' => 'exit',
        'product_id' => $product->getKey(),
        'quantity' => 1,
    ]);

    $this->assertDatabaseCount('notifications', 2);
    $this->assertDatabaseHas('notifications', [
        'type' => ProductLowStockNotification::class,
        'notifiable_type' => User::class,
        'notifiable_id' => $administrator->getKey(),
    ]);
    $this->assertDatabaseHas('notifications', [
        'type' => ProductLowStockNotification::class,
        'notifiable_type' => User::class,
        'notifiable_id' => $stockManager->getKey(),
    ]);
    $this->assertDatabaseMissing('notifications', [
        'type' => ProductLowStockNotification::class,
        'notifiable_id' => $employee->getKey(),
    ]);

    $service->record($stockManager, [
        'type' => 'exit',
        'product_id' => $product->getKey(),
        'quantity' => 1,
    ]);

    $this->assertDatabaseCount('notifications', 2);

    $service->record($stockManager, [
        'type' => 'entry',
        'product_id' => $product->getKey(),
        'quantity' => 2,
    ]);
    $service->record($stockManager, [
        'type' => 'exit',
        'product_id' => $product->getKey(),
        'quantity' => 2,
    ]);

    $this->assertDatabaseCount('notifications', 4);

    $notification = DB::table('notifications')
        ->where('notifiable_id', $administrator->getKey())
        ->firstOrFail();
    $data = json_decode($notification->data, true, flags: JSON_THROW_ON_ERROR);

    expect($data['title'])->toBe('Low stock alert')
        ->and($data['body'])->toContain('LAMP-LOW-01');
});

it('does not notify or change stock when a stock operation fails', function () {
    $stockManager = User::factory()->stockManager()->create();
    $product = Product::factory()->create([
        'quantity' => 6,
        'min_stock' => 5,
    ]);

    Product::updated(function (Product $updatedProduct): void {
        throw new RuntimeException('Simulated stock update failure.');
    });

    expect(fn () => app(StockMovementService::class)->record($stockManager, [
        'type' => 'exit',
        'product_id' => $product->getKey(),
        'quantity' => 1,
    ]))->toThrow(RuntimeException::class, 'Simulated stock update failure.');

    $this->assertDatabaseHas('products', [
        'id' => $product->getKey(),
        'quantity' => 6,
    ]);
    $this->assertDatabaseCount('stock_movements', 0);
    $this->assertDatabaseCount('notifications', 0);
});

it('does not create notifications when dashboard widgets are refreshed', function () {
    $this->actingAs(User::factory()->stockManager()->create());
    Product::factory()->create([
        'quantity' => 2,
        'min_stock' => 5,
    ]);

    Livewire::test(InventoryStatsOverview::class);
    Livewire::test(LowStockProducts::class);
    Livewire::test(StockActivityOverview::class);

    $this->assertDatabaseCount('notifications', 0);
});

it('rejects unsupported stock activity periods', function () {
    $this->actingAs(User::factory()->administrator()->create());

    expect(fn () => app(InventoryDashboardService::class)->stockActivity('all'))
        ->toThrow(ValidationException::class);
});
