<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estancia extends Model
{
    use SoftDeletes;

    protected $table = 'estancias';

    protected $fillable = [
        'inquilino_id',
        'cuarto_id',
        'fecha_inicio',
        'fecha_fin',
        'fecha_fin_estimada',
        'precio_acordado',
        'deposito',
        'estado',
        'motivo_cierre',
        'notas',
        'user_registro_id',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_fin_estimada' => 'date',
        'precio_acordado' => 'decimal:2',
        'deposito' => 'decimal:2',
    ];

    public const ESTADO_ACTIVA = 'activa';

    public const ESTADO_FINALIZADA = 'finalizada';

    public const ESTADO_CANCELADA = 'cancelada';

    public function inquilino(): BelongsTo
    {
        return $this->belongsTo(Inquilino::class);
    }

    public function cuarto(): BelongsTo
    {
        return $this->belongsTo(Cuarto::class);
    }

    public function userRegistro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_registro_id');
    }

    public function extras(): HasMany
    {
        return $this->hasMany(ExtraEstancia::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function estaActiva(): bool
    {
        return $this->estado === self::ESTADO_ACTIVA;
    }
}
