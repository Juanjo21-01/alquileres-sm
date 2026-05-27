<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoPago extends Model
{
    protected $table = 'tipos_pago';

    protected $fillable = ['nombre', 'codigo', 'requiere_mes', 'activo'];

    protected $casts = [
        'requiere_mes' => 'boolean',
        'activo' => 'boolean',
    ];

    public const COD_ANTICIPO = 'anticipo';

    public const COD_MENSUALIDAD = 'mensualidad';

    public const COD_EXTRA = 'extra';

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
