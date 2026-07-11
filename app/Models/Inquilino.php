<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inquilino extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inquilinos';

    protected $fillable = [
        'nombres',
        'apellidos',
        'dpi',
        'telefono',
        'email',
        'ocupacion',
        'institucion',
        'vehiculo_tipo',
        'vehiculo_placa',
        'contacto_emergencia_nombre',
        'contacto_emergencia_telefono',
        'notas',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public const VEHICULO_CARRO = 'carro';

    public const VEHICULO_MOTO = 'moto';

    public function estancias(): HasMany
    {
        return $this->hasMany(Estancia::class);
    }

    public function pagos(): HasManyThrough
    {
        return $this->hasManyThrough(Pago::class, Estancia::class);
    }

    public function estanciaActiva(): HasOne
    {
        return $this->hasOne(Estancia::class)->where('estado', Estancia::ESTADO_ACTIVA);
    }

    public function tieneVehiculo(): bool
    {
        return ! is_null($this->vehiculo_tipo);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    public static function institucionesDisponibles(): array
    {
        return self::whereNotNull('institucion')
            ->distinct()
            ->orderBy('institucion')
            ->pluck('institucion')
            ->values()
            ->toArray();
    }
}
