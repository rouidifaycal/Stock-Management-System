<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;

it('provides the inventory relationships and records movement ownership', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();
    $supplier = Supplier::factory()->create();
    $user = User::factory()->create();
    $movement = StockMovement::factory()
        ->for($product)
        ->for($supplier)
        ->for($user)
        ->create();

    expect($category->products)->toHaveCount(1)
        ->and($product->category->is($category))->toBeTrue()
        ->and($product->stockMovements->first()->is($movement))->toBeTrue()
        ->and($movement->product->is($product))->toBeTrue()
        ->and($movement->supplier->is($supplier))->toBeTrue()
        ->and($movement->user->is($user))->toBeTrue()
        ->and($user->stockMovements->first()->is($movement))->toBeTrue();
});

it('enforces unique category names and product skus', function () {
    Category::factory()->create(['name' => 'Unique category']);
    Product::factory()->create(['sku' => 'UNIQUE-SKU']);

    expect(fn () => Category::factory()->create(['name' => 'Unique category']))
        ->toThrow(QueryException::class)
        ->and(fn () => Product::factory()->create(['sku' => 'UNIQUE-SKU']))
        ->toThrow(QueryException::class);
});

it('preserves stock movement history and supplier attribution when related records are deleted', function () {
    $product = Product::factory()->create();
    $supplier = Supplier::factory()->create();
    $user = User::factory()->create();
    $movement = StockMovement::factory()
        ->for($product)
        ->for($supplier)
        ->for($user)
        ->create();

    expect(fn () => $product->category->delete())->toThrow(QueryException::class)
        ->and(fn () => $product->delete())->toThrow(QueryException::class)
        ->and(fn () => $user->delete())->toThrow(QueryException::class)
        ->and(fn () => $supplier->delete())->toThrow(AuthorizationException::class);

    expect($movement->fresh()->supplier_id)->toBe($supplier->id)
        ->and(StockMovement::query()->whereKey($movement->id)->exists())->toBeTrue();
});
