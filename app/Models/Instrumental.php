<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instrumental extends Model
{
    protected $table = 'instrumentales';

    protected $fillable = [
        'codigo',
        'nombre',
        'marca',
        'familia',
        'subfamilia',
        'estado',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function equipoDetalles(): HasMany
    {
        return $this->hasMany(EquipoDetalle::class, 'instrumental_id');
    }

    public function equipoInventario(): HasMany
    {
        return $this->hasMany(EquipoInventario::class, 'instrumental_id');
    }
}