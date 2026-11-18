<?php

use App\Enums\UserRole;
use App\Filament\Resources\StockMovements\Pages\CreateStockMovement;
use App\Filament\Resources\StockMovements\Pages\ListStockMovements;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockMovementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

it('records stock entries and exits for administrators and stock managers', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $unrelatedUser = User::factory()->create(['role' => UserRole::Employee]);
    $product = Product::factory()->create(['quantity' => 10]);
    $supplier = Supplier::factory()->create();
    $service = app(StockMovementService::class);

    $entry = $service->record($user, [
        'type' => 'entry',
        'product_id' => $product->getKey(),
        'quantity' => '5',
        'supplier_id' => $supplier->getKey(),
        'reason' => 'This reason is only for exits.',
        'notes' => 'Received against purchase order.',
        'user_id' => $unrelatedUser->getKey(),
    ]);

    expect($product->fresh()->quantity)->toBe(15)
        ->and($entry->type)->toBe('entry')
        ->and($entry->user_id)->toBe($user->getKey())
        ->and($entry->supplier_id)->toBe($supplier->getKey())
        ->and($entry->reason)->toBeNull()
        ->and($entry->notes)->toBe('Received against purchase order.');

    $exit = $service->record($user, [
        'type' => 'exit',
        'product_id' => $product->getKey(),
        'quantity' => 3,
        'reason' => 'Order fulfillment',
        'notes' => 'Customer order 1042.',
    ]);

    expect($product->fresh()->quantity)->toBe(12)
        ->and($exit->type)->toBe('exit')
        ->and($exit->user_id)->toBe($user->getKey())
        ->and($exit->supplier_id)->toBeNull()
        ->and($exit->reason)->toBe('Order fulfillment');

    $this->assertDatabaseCount('stock_movements', 2);
    $this->assertDatabaseHas('products', [
        'id' => $product->getKey(),
        'quantity' => 12,
    ]);
})->with([
    'Administrator' => [UserRole::Administrator],
    'Stock Manager' => [UserRole::StockManager],
]);

it('records a stock entry through the reviewed Filament workflow', function () {
    $user = User::factory()->stockManager()->create();
    $product = Product::factory()->create(['quantity' => 7]);
    $supplier = Supplier::factory()->create();

    $this->actingAs($user);

    Livewire::test(CreateStockMovement::class)
        ->fillForm([
            'type' => 'entry',
            'product_id' => $product->getKey(),
            'quantity' => 4,
            'supplier_id' => $supplier->getKey(),
            'notes' => 'Received in good condition.',
        ])
        ->assertSee('Review and confirm')
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $movement = StockMovement::query()->firstOrFail();

    expect($product->fresh()->quantity)->toBe(11)
        ->and($movement->user_id)->toBe($user->getKey())
        ->and($movement->supplier_id)->toBe($supplier->getKey())
        ->and($movement->type)->toBe('entry');
});

it('rejects oversized stock exits through the Filament form without changing stock', function () {
    $user = User::factory()->stockManager()->create();
    $product = Product::factory()->create(['quantity' => 4]);

    $this->actingAs($user);

    Livewire::test(CreateStockMovement::class)
        ->fillForm([
            'type' => 'exit',
            'product_id' => $product->getKey(),
            'quantity' => 5,
            'reason' => 'Order fulfillment',
        ])
        ->call('create')
        ->assertHasFormErrors(['quantity' => 'max']);

    $this->assertDatabaseHas('products', [
        'id' => $product->getKey(),
        'quantity' => 4,
    ]);
    $this->assertDatabaseCount('stock_movements', 0);
});

it('rejects zero negative and fractional quantities without changing stock', function (mixed $quantity) {
    $user = User::factory()->stockManager()->create();
    $product = Product::factory()->create(['quantity' => 8]);

    expect(fn () => app(StockMovementService::class)->record($user, [
        'type' => 'exit',
        'product_id' => $product->getKey(),
        'quantity' => $quantity,
    ]))->toThrow(ValidationException::class);

    $this->assertDatabaseHas('products', [
        'id' => $product->getKey(),
        'quantity' => 8,
    ]);
    $this->assertDatabaseCount('stock_movements', 0);
})->with([
    'zero' => [0],
    'negative' => [-1],
    'fractional' => [1.5],
]);

it('rejects an exit that exceeds locked available stock without recording a movement', function () {
    $user = User::factory()->stockManager()->create();
    $product = Product::factory()->create(['quantity' => 4]);

    expect(fn () => app(StockMovementService::class)->record($user, [
        'type' => 'exit',
        'product_id' => $product->getKey(),
        'quantity' => 5,
    ]))->toThrow(ValidationException::class);

    $this->assertDatabaseHas('products', [
        'id' => $product->getKey(),
        'quantity' => 4,
    ]);
    $this->assertDatabaseCount('stock_movements', 0);
});

it('rejects stock entries that exceed the product quantity column capacity', function () {
    $user = User::factory()->stockManager()->create();
    $product = Product::factory()->create(['quantity' => 4294967295]);

    expect(fn () => app(StockMovementService::class)->record($user, [
        'type' => 'entry',
        'product_id' => $product->getKey(),
        'quantity' => 1,
    ]))->toThrow(ValidationException::class);

    $this->assertDatabaseHas('products', [
        'id' => $product->getKey(),
        'quantity' => 4294967295,
    ]);
    $this->assertDatabaseCount('stock_movements', 0);
});

it('keeps employees read only for stock operations', function () {
    $employee = User::factory()->create(['role' => UserRole::Employee]);
    $product = Product::factory()->create(['quantity' => 8]);

    $this->actingAs($employee);

    $this->get('/admin/stock-movements')->assertOk();
    $this->get('/admin/stock-movements/create')->assertForbidden();

    Livewire::test(ListStockMovements::class)
        ->assertActionHidden('create');

    expect(fn () => app(StockMovementService::class)->record($employee, [
        'type' => 'exit',
        'product_id' => $product->getKey(),
        'quantity' => 1,
    ]))->toThrow(AuthorizationException::class);

    $this->assertDatabaseHas('products', [
        'id' => $product->getKey(),
        'quantity' => 8,
    ]);
    $this->assertDatabaseCount('stock_movements', 0);
});

it('searches and filters stock movement history', function () {
    $employee = User::factory()->create(['role' => UserRole::Employee]);
    $entryProduct = Product::factory()->create([
        'name' => 'Cordless drill',
        'sku' => 'DRILL-ENTRY-01',
    ]);
    $exitProduct = Product::factory()->create([
        'name' => 'Work bench',
        'sku' => 'BENCH-EXIT-01',
    ]);
    $entry = StockMovement::factory()
        ->for($employee)
        ->for($entryProduct)
        ->create(['type' => 'entry']);
    $exit = StockMovement::factory()
        ->for($employee)
        ->for($exitProduct)
        ->create(['type' => 'exit']);

    $this->actingAs($employee);

    Livewire::test(ListStockMovements::class)
        ->searchTable('DRILL-ENTRY-01')
        ->assertCanSeeTableRecords([$entry])
        ->assertCanNotSeeTableRecords([$exit]);

    Livewire::test(ListStockMovements::class)
        ->filterTable('type', 'exit')
        ->assertCanSeeTableRecords([$exit])
        ->assertCanNotSeeTableRecords([$entry]);
});

it('keeps stock movement records immutable', function () {
    $movement = StockMovement::factory()->create([
        'type' => 'entry',
        'quantity' => 3,
        'reason' => null,
    ]);

    expect(fn () => $movement->update(['reason' => 'Changed history.']))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $movement->delete())
        ->toThrow(AuthorizationException::class);

    $this->assertModelExists($movement);
    $this->assertDatabaseHas('stock_movements', [
        'id' => $movement->getKey(),
        'type' => 'entry',
        'quantity' => 3,
        'reason' => null,
    ]);
});
