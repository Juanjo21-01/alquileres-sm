<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['propiedad_id', 'codigo', 'nivel', 'tamano', 'precio_base', 'estado', 'descripcion', 'activo'])]
class Cuarto extends Model
{
    use HasFactory;

    protected $table = 'cuartos';

    public const ESTADO_DISPONIBLE = 'disponible';

    public const ESTADO_OCUPADO = 'ocupado';

    public const ESTADO_RESERVADO = 'reservado';

    public const ESTADO_MANTENIMIENTO = 'mantenimiento';

    protected function casts(): array
    {
        return [
            'precio_base' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function estados(): array
    {
        return [
            self::ESTADO_DISPONIBLE => 'Disponible',
            self::ESTADO_OCUPADO => 'Ocupado',
            self::ESTADO_RESERVADO => 'Reservado',
            self::ESTADO_MANTENIMIENTO => 'Mantenimiento',
        ];
    }

    public function propiedad(): BelongsTo
    {
        return $this->belongsTo(Propiedad::class);
    }

    public function estancias(): HasMany
    {
        return $this->hasMany(Estancia::class);
    }

    public function estanciaActiva(): HasOne
    {
        return $this->hasOne(Estancia::class)->where('estado', Estancia::ESTADO_ACTIVA);
    }

    public function estaDisponible(): bool
    {
        return $this->estado === self::ESTADO_DISPONIBLE;
    }
}
