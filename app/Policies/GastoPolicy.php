<?php

namespace App\Policies;

use App\Models\Gasto;
use App\Models\User;

class GastoPolicy
{
    /** Todos los usuarios autenticados pueden listar, ver y registrar gastos. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Gasto $gasto): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /** Solo administrador puede editar y eliminar gastos. */
    public function update(User $user, Gasto $gasto): bool
    {
        return $user->esAdministrador();
    }

    public function delete(User $user, Gasto $gasto): bool
    {
        return $user->esAdministrador();
    }
}
