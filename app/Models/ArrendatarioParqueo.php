<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArrendatarioParqueo extends Model
{
    protected $table = 'arrendatarios_parqueo';

    protected $fillable = [
        'nombre_completo',
        'telefono',
        'ocupacion',
        'placa',
        'activo',
        'notas',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public const OCUPACION_ESTUDIANTE = 'estudiante';

    public const OCUPACION_SALUD = 'salud';

    public const OCUPACION_OTRO = 'otro';

    public function alquileres(): HasMany
    {
        return $this->hasMany(AlquilerParqueo::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
