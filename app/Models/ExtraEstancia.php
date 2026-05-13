<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraEstancia extends Model
{
    protected $table = 'extras_estancia';

    protected $fillable = [
        'estancia_id',
        'descripcion',
        'monto',
        'periodicidad',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public const PERIODICIDAD_UNICO = 'unico';

    public const PERIODICIDAD_MENSUAL = 'mensual';

    public function estancia(): BelongsTo
    {
        return $this->belongsTo(Estancia::class);
    }
}
