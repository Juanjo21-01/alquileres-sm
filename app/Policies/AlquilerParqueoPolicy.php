<?php

namespace App\Policies;

use App\Models\AlquilerParqueo;
use App\Models\User;

class AlquilerParqueoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AlquilerParqueo $alquilerParqueo): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AlquilerParqueo $alquilerParqueo): bool
    {
        return true;
    }

    public function delete(User $user, AlquilerParqueo $alquilerParqueo): bool
    {
        return $user->esAdministrador();
    }
}
