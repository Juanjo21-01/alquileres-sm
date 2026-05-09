<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'direccion', 'zona', 'referencia', 'notas', 'activo'])]
class Propiedad extends Model
{
    protected $table = 'propiedades';

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function cuartos(): HasMany
    {
        return $this->hasMany(Cuarto::class);
    }
}
