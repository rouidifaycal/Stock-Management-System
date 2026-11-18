<?php

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\UserManagementService;
use Filament\Auth\Pages\Login;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('allows all roles to read inventory and only administrators and stock managers to manage it', function (UserRole $role, bool $canManageInventory) {
    $user = User::factory()->create(['role' => $role]);
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();
    $supplier = Supplier::factory()->create();
    $movement = StockMovement::factory()->for($user)->for($product)->create();

    foreach ([Category::class, Product::class, Supplier::class, StockMovement::class] as $model) {
        expect($user->can('viewAny', $model))->toBeTrue();
    }

    expect($user->can('create', Category::class))->toBe($canManageInventory)
        ->and($user->can('update', $category))->toBe($canManageInventory)
        ->and($user->can('delete', $category))->toBe($canManageInventory)
        ->and($user->can('create', Product::class))->toBe($canManageInventory)
        ->and($user->can('update', $product))->toBe($canManageInventory)
        ->and($user->can('delete', $product))->toBe($canManageInventory)
        ->and($user->can('create', Supplier::class))->toBe($canManageInventory)
        ->and($user->can('update', $supplier))->toBe($canManageInventory)
        ->and($user->can('delete', $supplier))->toBe($canManageInventory)
        ->and($user->can('create', StockMovement::class))->toBe($canManageInventory)
        ->and($user->can('update', $movement))->toBeFalse()
        ->and($user->can('delete', $movement))->toBeFalse();

    expect(Gate::forUser($user)->allows('viewInventoryReports'))->toBe($canManageInventory)
        ->and(Gate::forUser($user)->allows('manageSensitiveStockOperations'))->toBe($canManageInventory)
        ->and(Gate::forUser($user)->allows('manageAdministrativeSettings'))->toBe($role === UserRole::Administrator)
        ->and($user->can('viewAny', User::class))->toBe($role === UserRole::Administrator);
})->with([
    'Administrator' => [UserRole::Administrator, true],
    'Stock Manager' => [UserRole::StockManager, true],
    'Employee' => [UserRole::Employee, false],
]);

it('preserves the Filament login and requires authentication for the panel', function () {
    $this->get('/admin')->assertRedirect(route('filament.admin.auth.login'));
    $this->get('/admin/login')->assertOk();
});

it('authenticates valid Filament credentials and grants an employee read-only panel access', function () {
    $employee = User::factory()->create([
        'email' => 'employee@example.test',
        'password' => 'StrongPassword42!',
    ]);

    Livewire::test(Login::class)
        ->set('data.email', 'employee@example.test')
        ->set('data.password', 'StrongPassword42!')
        ->call('authenticate')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($employee);
    $this->get('/admin')->assertOk();
    $this->get('/admin/users')->assertForbidden();
});

it('keeps user management inaccessible to non-administrators', function (UserRole $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get('/admin/users')
        ->assertForbidden();
})->with([
    'Stock Manager' => [UserRole::StockManager],
    'Employee' => [UserRole::Employee],
]);

it('allows administrators to manage users through the authorized Filament resource', function () {
    $administrator = User::factory()->administrator()->create();
    $this->actingAs($administrator);
    $this->get('/admin/users')->assertOk();

    Livewire::test(ManageUsers::class)
        ->callAction('create', data: [
            'name' => 'Created Employee',
            'email' => 'created.employee@example.test',
            'password' => 'StrongPassword42!',
            'role' => UserRole::Employee->value,
        ])
        ->assertHasNoActionErrors();

    $employee = User::query()->where('email', 'created.employee@example.test')->firstOrFail();

    expect($employee->role)->toBe(UserRole::Employee)
        ->and(Hash::check('StrongPassword42!', $employee->password))->toBeTrue();

    Livewire::test(ManageUsers::class)
        ->mountTableAction('edit', $employee)
        ->assertTableActionDataSet(['password' => '']);

    Livewire::test(ManageUsers::class)
        ->callTableAction('edit', $employee, data: [
            'name' => $employee->name,
            'email' => $employee->email,
            'password' => '',
            'role' => UserRole::StockManager->value,
        ])
        ->assertHasNoTableActionErrors();

    expect($employee->fresh()->role)->toBe(UserRole::StockManager);
});

it('allows administrators to create users with a role without mass-assigning the role field', function () {
    $administrator = User::factory()->administrator()->create();
    $this->actingAs($administrator);

    $employee = app(UserManagementService::class)->create([
        'name' => 'New Employee',
        'email' => 'new.employee@example.test',
        'password' => 'StrongPassword42!',
        'role' => UserRole::Employee->value,
    ]);

    expect($employee->role)->toBe(UserRole::Employee)
        ->and(Hash::check('StrongPassword42!', $employee->password))->toBeTrue();

    $employee->fill(['role' => UserRole::Administrator->value]);
    $employee->save();

    expect($employee->fresh()->role)->toBe(UserRole::Employee);
});

it('prevents non-administrators from creating or promoting users', function () {
    $employee = User::factory()->create();
    $this->actingAs($employee);

    expect(fn () => app(UserManagementService::class)->create([
        'name' => 'Unauthorized Admin',
        'email' => 'unauthorized.admin@example.test',
        'password' => 'StrongPassword42!',
        'role' => UserRole::Administrator->value,
    ]))->toThrow(AuthorizationException::class);

    expect(User::query()->where('email', 'unauthorized.admin@example.test')->exists())->toBeFalse();
});

it('prevents the last administrator from being deleted or demoted', function () {
    $administrator = User::factory()->administrator()->create();
    $this->actingAs($administrator);
    $service = app(UserManagementService::class);

    expect(fn () => $service->update($administrator, [
        'name' => $administrator->name,
        'email' => $administrator->email,
        'role' => UserRole::Employee->value,
    ]))->toThrow(AuthorizationException::class);

    expect(fn () => $service->delete($administrator))->toThrow(AuthorizationException::class)
        ->and($administrator->fresh()->role)->toBe(UserRole::Administrator)
        ->and(User::query()->where('role', UserRole::Administrator->value)->count())->toBe(1);
});

it('protects the last administrator through direct Eloquent changes', function () {
    $administrator = User::factory()->administrator()->create();

    expect(fn () => $administrator->delete())
        ->toThrow(AuthorizationException::class);

    $administrator->role = UserRole::Employee;

    expect(fn () => $administrator->save())
        ->toThrow(AuthorizationException::class);
});

it('allows an administrator to remove one administrator only when another remains', function () {
    $actor = User::factory()->administrator()->create();
    $target = User::factory()->administrator()->create();
    $this->actingAs($actor);

    app(UserManagementService::class)->update($target, [
        'name' => $target->name,
        'email' => $target->email,
        'role' => UserRole::StockManager->value,
    ]);

    expect($target->fresh()->role)->toBe(UserRole::StockManager)
        ->and(User::query()->where('role', UserRole::Administrator->value)->count())->toBe(1);

    expect(fn () => app(UserManagementService::class)->delete($actor))
        ->toThrow(AuthorizationException::class);
});

it('creates the first administrator only after local explicit confirmation and is idempotent', function () {
    $this->app['env'] = 'local';

    $this->artisan('stockflow:bootstrap-administrator', [
        '--confirm' => true,
        '--name' => 'Bootstrap Administrator',
        '--email' => 'bootstrap.admin@example.test',
    ])
        ->expectsQuestion('Administrator password', 'StrongPassword42!')
        ->expectsOutputToContain('The first Administrator account was created.')
        ->doesntExpectOutputToContain('StrongPassword42!')
        ->assertExitCode(0);

    $administrator = User::query()->where('email', 'bootstrap.admin@example.test')->firstOrFail();

    expect($administrator->role)->toBe(UserRole::Administrator)
        ->and(Hash::check('StrongPassword42!', $administrator->password))->toBeTrue();

    $this->artisan('stockflow:bootstrap-administrator')
        ->expectsOutputToContain('already complete; no changes were made.')
        ->assertExitCode(0);

    expect(User::query()->where('role', UserRole::Administrator->value)->count())->toBe(1);
});

it('refuses administrator bootstrap outside the local environment', function () {
    $this->app['env'] = 'production';

    $this->artisan('stockflow:bootstrap-administrator', [
        '--confirm' => true,
        '--name' => 'Bootstrap Administrator',
        '--email' => 'bootstrap.admin@example.test',
    ])
        ->expectsOutputToContain('available only in the local environment.')
        ->assertExitCode(1);

    expect(User::query()->count())->toBe(0);
});
