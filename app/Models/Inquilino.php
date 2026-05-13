<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inquilino extends Model
{
    use SoftDeletes;

    protected $table = 'inquilinos';

    protected $fillable = [
        'nombres',
        'apellidos',
        'dpi',
        'telefono',
        'email',
        'ocupacion',
        'institucion',
        'contacto_emergencia_nombre',
        'contacto_emergencia_telefono',
        'notas',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function estancias(): HasMany
    {
        return $this->hasMany(Estancia::class);
    }

    public function estanciaActiva(): HasOne
    {
        return $this->hasOne(Estancia::class)->where('estado', Estancia::ESTADO_ACTIVA);
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
