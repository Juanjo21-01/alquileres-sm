<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaGasto extends Model
{
    protected $table = 'categorias_gasto';

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'requiere_propiedad',
        'requiere_cuarto',
        'activo',
    ];

    protected $casts = [
        'requiere_propiedad' => 'boolean',
        'requiere_cuarto' => 'boolean',
        'activo' => 'boolean',
    ];

    public function gastos()
    {
        return $this->hasMany(Gasto::class);
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
