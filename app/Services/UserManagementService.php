<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        Gate::authorize('create', User::class);

        $role = $this->extractRole($data);

        return DB::transaction(function () use ($data, $role): User {
            $user = new User(Arr::only($data, ['name', 'email', 'password']));
            $user->role = $role;
            $user->save();

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        Gate::authorize('update', $user);

        $role = $this->extractRole($data);

        return DB::transaction(function () use ($user, $data, $role): User {
            User::query()
                ->where('role', UserRole::Administrator->value)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            $targetUser = User::query()
                ->lockForUpdate()
                ->whereKey($user->getKey())
                ->firstOrFail();

            Gate::authorize('update', $targetUser);
            Gate::authorize('assignRole', [$targetUser, $role]);

            $targetUser->fill(Arr::only($data, ['name', 'email', 'password']));
            $targetUser->role = $role;
            $targetUser->save();

            return $targetUser;
        });
    }

    public function delete(User $user): bool
    {
        return DB::transaction(function () use ($user): bool {
            $administrators = User::query()
                ->where('role', UserRole::Administrator->value)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            $targetUser = User::query()
                ->lockForUpdate()
                ->whereKey($user->getKey())
                ->firstOrFail();

            Gate::authorize('delete', $targetUser);

            if ($targetUser->isAdministrator() && $administrators->count() <= 1) {
                throw new AuthorizationException('The last Administrator cannot be deleted.');
            }

            return (bool) $targetUser->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractRole(array &$data): UserRole
    {
        $role = $data['role'] ?? null;
        unset($data['role']);

        if ($role instanceof UserRole) {
            return $role;
        }

        if (is_string($role) && ($role = UserRole::tryFrom($role)) !== null) {
            return $role;
        }

        throw ValidationException::withMessages([
            'role' => 'Select a valid user role.',
        ]);
    }
}
