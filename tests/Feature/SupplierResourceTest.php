<?php

use App\Enums\UserRole;
use App\Filament\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Resources\Suppliers\Pages\ViewSupplier;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

it('lets administrators and stock managers create view edit and delete suppliers', function (UserRole $role) {
    $this->actingAs(User::factory()->create(['role' => $role]));

    Livewire::test(CreateSupplier::class)
        ->fillForm([
            'name' => 'Northwind Supply',
            'company_name' => 'Northwind Trading',
            'email' => 'orders@northwind.example',
            'phone' => '555-0101',
            'address' => '10 Warehouse Road',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $supplier = Supplier::query()->where('name', 'Northwind Supply')->firstOrFail();

    Livewire::test(ViewSupplier::class, ['record' => $supplier->getKey()])
        ->assertOk()
        ->assertSee('Northwind Trading');

    Livewire::test(EditSupplier::class, ['record' => $supplier->getKey()])
        ->fillForm([
            'name' => 'Northwind Supply Co.',
            'company_name' => 'Northwind Trading',
            'email' => 'orders@northwind.example',
            'phone' => '555-0102',
            'address' => '12 Warehouse Road',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($supplier->fresh()->name)->toBe('Northwind Supply Co.')
        ->and($supplier->fresh()->phone)->toBe('555-0102');

    Livewire::test(ListSuppliers::class)
        ->callTableAction('delete', $supplier);

    $this->assertModelMissing($supplier);
})->with([
    'Administrator' => [UserRole::Administrator],
    'Stock Manager' => [UserRole::StockManager],
]);

it('allows employees to read suppliers but denies supplier mutations', function () {
    $employee = User::factory()->create(['role' => UserRole::Employee]);
    $supplier = Supplier::factory()->create();

    $this->actingAs($employee);

    $this->get('/admin/suppliers')->assertOk();
    $this->get("/admin/suppliers/{$supplier->getKey()}")->assertOk();
    $this->get('/admin/suppliers/create')->assertForbidden();
    $this->get("/admin/suppliers/{$supplier->getKey()}/edit")->assertForbidden();

    Livewire::test(ListSuppliers::class)
        ->assertActionHidden('create')
        ->assertTableActionHidden('edit', $supplier)
        ->assertTableActionHidden('delete', $supplier);
});

it('searches and filters suppliers by stock history', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Employee]));

    $supplierWithHistory = Supplier::factory()->create(['name' => 'Northwind Supply']);
    $supplierWithoutHistory = Supplier::factory()->create(['name' => 'Southwind Supply']);
    $product = Product::factory()->create();

    StockMovement::factory()
        ->for($supplierWithHistory)
        ->for($product)
        ->create();

    Livewire::test(ListSuppliers::class)
        ->searchTable('Northwind')
        ->assertCanSeeTableRecords([$supplierWithHistory])
        ->assertCanNotSeeTableRecords([$supplierWithoutHistory]);

    Livewire::test(ListSuppliers::class)
        ->filterTable('has_stock_movements', true)
        ->assertCanSeeTableRecords([$supplierWithHistory])
        ->assertCanNotSeeTableRecords([$supplierWithoutHistory]);
});

it('shows related stock movement counts on supplier details', function () {
    $employee = User::factory()->create(['role' => UserRole::Employee]);
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create(['name' => 'Cordless drill']);

    StockMovement::factory()
        ->for($employee)
        ->for($product)
        ->for($supplier)
        ->create([
            'type' => 'entry',
            'quantity' => 6,
        ]);

    $this->actingAs($employee);

    Livewire::test(ViewSupplier::class, ['record' => $supplier->getKey()])
        ->assertOk()
        ->assertSee('Related stock operations');
});

it('prevents deleting suppliers with stock movement history', function () {
    $administrator = User::factory()->administrator()->create();
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create();
    $movement = StockMovement::factory()
        ->for($administrator)
        ->for($product)
        ->for($supplier)
        ->create();

    $this->actingAs($administrator);

    Livewire::test(ListSuppliers::class)
        ->assertTableActionDisabled('delete', $supplier);

    expect(fn () => $supplier->delete())
        ->toThrow(AuthorizationException::class);

    $this->assertModelExists($supplier);
    $this->assertModelExists($movement);
    $this->assertDatabaseHas('stock_movements', [
        'id' => $movement->getKey(),
        'supplier_id' => $supplier->getKey(),
    ]);
});
