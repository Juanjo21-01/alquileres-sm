<?php

namespace App\Policies;

use App\Models\Estancia;
use App\Models\User;

class EstanciaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Estancia $estancia): bool
    {
        return true;
    }

    /** Cualquier autenticado puede abrir estancia. */
    public function create(User $user): bool
    {
        return true;
    }

    /** Cualquier autenticado puede cerrar estancia. */
    public function update(User $user, Estancia $estancia): bool
    {
        return true;
    }

    /** Solo administrador puede eliminar o cancelar. */
    public function delete(User $user, Estancia $estancia): bool
    {
        return $user->esAdministrador();
    }

    public function cancelar(User $user, Estancia $estancia): bool
    {
        return $user->esAdministrador();
    }
}
