<?php

namespace App\Policies;

use App\Models\Cuarto;
use App\Models\User;

class CuartoPolicy
{
    /** Todos los usuarios autenticados pueden listar y ver cuartos. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Cuarto $cuarto): bool
    {
        return true;
    }

    /** Solo administrador puede crear, editar y eliminar. */
    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, Cuarto $cuarto): bool
    {
        return $user->esAdministrador();
    }

    public function delete(User $user, Cuarto $cuarto): bool
    {
        return $user->esAdministrador();
    }
}
