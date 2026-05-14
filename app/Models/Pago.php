<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pago extends Model
{
    use SoftDeletes;

    protected $table = 'pagos';

    protected $fillable = [
        'estancia_id',
        'tipo_pago_id',
        'fecha_pago',
        'mes_aplicado',
        'monto_bruto',
        'descuento',
        'motivo_descuento',
        'monto_neto',
        'metodo_pago',
        'referencia',
        'recibo_numero',
        'notas',
        'user_registro_id',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'mes_aplicado' => 'date',
        'monto_bruto' => 'decimal:2',
        'descuento' => 'decimal:2',
        'monto_neto' => 'decimal:2',
    ];

    public const METODO_EFECTIVO = 'efectivo';

    public const METODO_CUENTA = 'cuenta';

    public function estancia(): BelongsTo
    {
        return $this->belongsTo(Estancia::class);
    }

    public function tipoPago(): BelongsTo
    {
        return $this->belongsTo(TipoPago::class);
    }

    public function userRegistro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_registro_id');
    }
}
