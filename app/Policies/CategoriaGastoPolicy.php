<?php

namespace App\Policies;

use App\Models\CategoriaGasto;
use App\Models\User;

class CategoriaGastoPolicy
{
    /** Todos los usuarios autenticados pueden listar y ver categorías. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CategoriaGasto $categoriaGasto): bool
    {
        return true;
    }

    /** Solo administrador puede crear, editar y eliminar categorías. */
    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, CategoriaGasto $categoriaGasto): bool
    {
        return $user->esAdministrador();
    }

    public function delete(User $user, CategoriaGasto $categoriaGasto): bool
    {
        return $user->esAdministrador();
    }
}
