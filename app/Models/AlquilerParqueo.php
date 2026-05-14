<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlquilerParqueo extends Model
{
    protected $table = 'alquileres_parqueo';

    protected $fillable = [
        'arrendatario_parqueo_id',
        'mes',
        'monto',
        'pagado',
        'fecha_pago',
        'metodo_pago',
        'notas',
        'user_registro_id',
    ];

    protected $casts = [
        'mes' => 'date',
        'fecha_pago' => 'date',
        'monto' => 'decimal:2',
        'pagado' => 'boolean',
    ];

    public const METODO_EFECTIVO = 'efectivo';

    public const METODO_CUENTA = 'cuenta';

    public function arrendatario(): BelongsTo
    {
        return $this->belongsTo(ArrendatarioParqueo::class, 'arrendatario_parqueo_id');
    }

    public function userRegistro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_registro_id');
    }
}
