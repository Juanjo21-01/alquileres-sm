<?php

namespace App\Policies;

use App\Models\Propiedad;
use App\Models\User;

class PropiedadPolicy
{
    /** Todos los usuarios autenticados pueden listar y ver propiedades. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Propiedad $propiedad): bool
    {
        return true;
    }

    /** Solo administrador puede crear, editar y eliminar. */
    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, Propiedad $propiedad): bool
    {
        return $user->esAdministrador();
    }

    public function delete(User $user, Propiedad $propiedad): bool
    {
        return $user->esAdministrador();
    }
}
