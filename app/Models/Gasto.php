<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gasto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'gastos';

    protected $fillable = [
        'categoria_gasto_id',
        'propiedad_id',
        'cuarto_id',
        'fecha',
        'monto',
        'descripcion',
        'proveedor',
        'metodo_pago',
        'comprobante_path',
        'notas',
        'user_registro_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    public const METODO_EFECTIVO = 'efectivo';

    public const METODO_CUENTA = 'cuenta';

    public function categoria()
    {
        return $this->belongsTo(CategoriaGasto::class, 'categoria_gasto_id');
    }

    public function propiedad()
    {
        return $this->belongsTo(Propiedad::class);
    }

    public function cuarto()
    {
        return $this->belongsTo(Cuarto::class);
    }

    public function userRegistro()
    {
        return $this->belongsTo(User::class, 'user_registro_id');
    }

    public function tieneComprobante(): bool
    {
        return ! is_null($this->comprobante_path);
    }
}
