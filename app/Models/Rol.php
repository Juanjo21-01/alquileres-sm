<?php

namespace App\Models;

use Database\Factories\RolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'codigo', 'descripcion', 'activo'])]
class Rol extends Model
{
    /** @use HasFactory<RolFactory> */
    use HasFactory;

    protected $table = 'roles';

    public const COD_ADMIN = 'administrador';

    public const COD_ENCARGADO = 'encargado';

    public const COD_INQUILINO = 'inquilino'; // futuro

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}
