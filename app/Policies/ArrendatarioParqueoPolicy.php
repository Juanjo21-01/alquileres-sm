<?php

namespace App\Policies;

use App\Models\ArrendatarioParqueo;
use App\Models\User;

class ArrendatarioParqueoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ArrendatarioParqueo $arrendatarioParqueo): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ArrendatarioParqueo $arrendatarioParqueo): bool
    {
        return true;
    }

    /** En la práctica se desactiva con activo=false, no se elimina. Solo admin puede eliminar. */
    public function delete(User $user, ArrendatarioParqueo $arrendatarioParqueo): bool
    {
        return $user->esAdministrador();
    }
}
