<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Solo el administrador gestiona usuarios del sistema. */
    public function viewAny(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function view(User $user, User $model): bool
    {
        return $user->esAdministrador();
    }

    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, User $model): bool
    {
        return $user->esAdministrador();
    }

    /** Los usuarios no se eliminan; se desactivan (toggle activo). */
    public function delete(User $user, User $model): bool
    {
        return false;
    }
}
