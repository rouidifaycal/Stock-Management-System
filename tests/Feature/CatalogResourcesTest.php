<?php

use App\Enums\UserRole;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Pages\ViewCategory;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('shows StockFlow branding on the application and Filament login pages', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<title>StockFlow | Inventory Management</title>', false)
        ->assertSee('images/stockflow-logo.svg')
        ->assertDontSee('Laravel has an incredibly rich ecosystem');

    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('StockFlow')
        ->assertSee('images/stockflow-logo.svg')
        ->assertSee('favicon.svg');
});

it('lets catalog managers create view edit and delete categories', function (UserRole $role) {
    $this->actingAs(User::factory()->create(['role' => $role]));

    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name' => 'Office supplies',
            'description' => 'Paper and desk essentials.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $category = Category::query()->where('name', 'Office supplies')->firstOrFail();

    Livewire::test(ViewCategory::class, ['record' => $category->getKey()])
        ->assertOk();

    Livewire::test(EditCategory::class, ['record' => $category->getKey()])
        ->fillForm([
            'name' => 'Office essentials',
            'description' => 'Updated catalog details.',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->fresh()->name)->toBe('Office essentials');

    Livewire::test(ListCategories::class)
        ->callTableAction('delete', $category);

    $this->assertModelMissing($category);
})->with([
    'Administrator' => [UserRole::Administrator],
    'Stock Manager' => [UserRole::StockManager],
]);

it('lets employees read catalog records but denies catalog mutations', function () {
    $employee = User::factory()->create(['role' => UserRole::Employee]);
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();

    $this->actingAs($employee);

    $this->get('/admin/categories')->assertOk();
    $this->get('/admin/products')->assertOk();
    $this->get('/admin/categories/create')->assertForbidden();
    $this->get("/admin/categories/{$category->getKey()}/edit")->assertForbidden();
    $this->get('/admin/products/create')->assertForbidden();
    $this->get("/admin/products/{$product->getKey()}/edit")->assertForbidden();

    Livewire::test(ListCategories::class)
        ->assertActionHidden('create')
        ->assertTableActionHidden('edit', $category)
        ->assertTableActionHidden('delete', $category);

    Livewire::test(ListProducts::class)
        ->assertActionHidden('create')
        ->assertTableActionHidden('edit', $product)
        ->assertTableActionHidden('delete', $product);
});

it('searches and filters categories by products', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Employee]));

    $withProducts = Category::factory()->create(['name' => 'Hardware']);
    $withoutProducts = Category::factory()->create(['name' => 'Office']);
    Product::factory()->for($withProducts)->create();

    Livewire::test(ListCategories::class)
        ->searchTable('Hardware')
        ->assertCanSeeTableRecords([$withProducts])
        ->assertCanNotSeeTableRecords([$withoutProducts]);

    Livewire::test(ListCategories::class)
        ->filterTable('has_products', true)
        ->assertCanSeeTableRecords([$withProducts])
        ->assertCanNotSeeTableRecords([$withoutProducts]);
});

it('creates products with public images and keeps stock quantity read-only', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::StockManager]));
    Storage::fake('public');

    $category = Category::factory()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'category_id' => $category->getKey(),
            'name' => 'Desk organizer',
            'sku' => 'DESK-ORG-001',
            'price' => '24.50',
            'quantity' => 999,
            'min_stock' => 3,
            'image' => UploadedFile::fake()->image('organizer.png', 100, 100),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::query()->where('sku', 'DESK-ORG-001')->firstOrFail();

    expect($product->quantity)->toBe(0)
        ->and($product->category->is($category))->toBeTrue();

    Storage::disk('public')->assertExists($product->image);

    Livewire::test(ViewProduct::class, ['record' => $product->getKey()])
        ->assertOk()
        ->assertSee($product->image);

    $product->update(['quantity' => 12]);

    Livewire::test(EditProduct::class, ['record' => $product->getKey()])
        ->fillForm([
            'name' => 'Desk organizer pro',
            'quantity' => 0,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->fresh()->name)->toBe('Desk organizer pro')
        ->and($product->fresh()->quantity)->toBe(12);
});

it('validates product SKU uniqueness and non-negative prices', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::StockManager]));

    $product = Product::factory()->create(['sku' => 'UNIQUE-ITEM']);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'category_id' => $product->category_id,
            'name' => 'Duplicate item',
            'sku' => 'UNIQUE-ITEM',
            'price' => '-1',
            'min_stock' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'sku' => 'unique',
            'price' => 'min',
        ]);

    expect(Product::query()->count())->toBe(1);
});

it('searches and filters products by SKU category and stock level', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Employee]));

    $tools = Category::factory()->create(['name' => 'Tools']);
    $furniture = Category::factory()->create(['name' => 'Furniture']);
    $lowStockProduct = Product::factory()->for($tools)->create([
        'name' => 'Cordless drill',
        'sku' => 'DRILL-LOW-01',
        'quantity' => 2,
        'min_stock' => 5,
    ]);
    $healthyProduct = Product::factory()->for($furniture)->create([
        'name' => 'Work bench',
        'sku' => 'BENCH-OK-01',
        'quantity' => 8,
        'min_stock' => 5,
    ]);

    Livewire::test(ListProducts::class)
        ->searchTable('DRILL-LOW-01')
        ->assertCanSeeTableRecords([$lowStockProduct])
        ->assertCanNotSeeTableRecords([$healthyProduct]);

    Livewire::test(ListProducts::class)
        ->filterTable('category', $tools)
        ->assertCanSeeTableRecords([$lowStockProduct])
        ->assertCanNotSeeTableRecords([$healthyProduct]);

    Livewire::test(ListProducts::class)
        ->filterTable('low_stock', true)
        ->assertCanSeeTableRecords([$lowStockProduct])
        ->assertCanNotSeeTableRecords([$healthyProduct]);
});

it('prevents deleting categories and products that preserve related records', function () {
    $this->actingAs(User::factory()->administrator()->create());

    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();
    StockMovement::factory()->for($product)->create();

    Livewire::test(ListCategories::class)
        ->assertTableActionDisabled('delete', $category);

    Livewire::test(ListProducts::class)
        ->assertTableActionDisabled('delete', $product);
});

it('allows administrators and managers to delete products without stock history', function (UserRole $role) {
    $this->actingAs(User::factory()->create(['role' => $role]));

    $product = Product::factory()->create();

    Livewire::test(ListProducts::class)
        ->callTableAction('delete', $product);

    $this->assertModelMissing($product);
})->with([
    'Administrator' => [UserRole::Administrator],
    'Stock Manager' => [UserRole::StockManager],
]);
