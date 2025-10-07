<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * Only superadmin and admin can view users
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin']);
    }

    /**
     * Determine whether the user can view the model.
     *
     * Only superadmin and admin can view individual user
     */
    public function view(User $user, User $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin']);
    }

    /**
     * Determine whether the user can create models.
     *
     * Only superadmin and admin can create users
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin']);
    }

    /**
     * Determine whether the user can update the model.
     *
     * Only superadmin and admin can update users
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin']);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Only superadmin can delete users
     * Prevent self-deletion
     */
    public function delete(User $user, User $model): bool
    {
        // Prevent self-deletion
        if ($user->user_id === $model->user_id) {
            return false;
        }

        return $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can delete multiple models.
     *
     * Only superadmin can bulk delete users
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole('superadmin');
    }
}
