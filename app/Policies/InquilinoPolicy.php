<?php

namespace App\Policies;

use App\Models\Inquilino;
use App\Models\User;

class InquilinoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Inquilino $inquilino): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Inquilino $inquilino): bool
    {
        return true;
    }

    /** Solo administrador puede eliminar (soft delete). */
    public function delete(User $user, Inquilino $inquilino): bool
    {
        return $user->esAdministrador();
    }
}
