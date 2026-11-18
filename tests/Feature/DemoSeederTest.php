<?php

use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoSeeder;

it('creates realistic demo stock levels and movement history without duplicating records', function () {
    User::factory()->administrator()->create();

    $this->artisan('db:seed', ['--class' => DemoSeeder::class])
        ->assertExitCode(0);

    expect(Product::query()->where('sku', 'DEMO-ELEC-DOCK-001')->value('quantity'))->toBe(26)
        ->and(Product::query()->where('sku', 'DEMO-WH-CARTON-001')->value('quantity'))->toBe(4)
        ->and(Product::query()->where('sku', 'DEMO-SAFE-GLOVE-001')->value('quantity'))->toBe(0);

    $this->assertDatabaseCount('categories', 3);
    $this->assertDatabaseCount('suppliers', 2);
    $this->assertDatabaseCount('products', 3);
    $this->assertDatabaseCount('stock_movements', 6);
    $this->assertDatabaseCount('notifications', 2);

    $this->artisan('db:seed', ['--class' => DemoSeeder::class])
        ->assertExitCode(0);

    $this->assertDatabaseCount('categories', 3);
    $this->assertDatabaseCount('suppliers', 2);
    $this->assertDatabaseCount('products', 3);
    $this->assertDatabaseCount('stock_movements', 6);
    $this->assertDatabaseCount('notifications', 2);
});
