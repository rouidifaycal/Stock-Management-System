<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdministrator();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdministrator();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isAdministrator() && ! $model->isLastAdministrator();
    }

    public function assignRole(User $user, User $model, UserRole $role): bool
    {
        if (! $user->isAdministrator()) {
            return false;
        }

        if ($user->is($model) && $model->role !== $role) {
            return false;
        }

        return ! $model->isAdministrator()
            || $role === UserRole::Administrator
            || ! $model->isLastAdministrator();
    }

    public function restore(User $user, User $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
