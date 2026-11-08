<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ubicacion extends Model
{
    protected $table = 'ubicaciones';

    protected $fillable = [
        'bodega_id',
        'ubicacion_padre_id',
        'codigo',
        'nombre',
        'tipo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Bodega a la que pertenece
    public function bodega(): BelongsTo
    {
        return $this->belongsTo(
            Bodega::class,
            'bodega_id'
        );
    }

    // Percha o ubicación superior
    public function padre(): BelongsTo
    {
        return $this->belongsTo(
            Ubicacion::class,
            'ubicacion_padre_id'
        );
    }

    // Ubicaciones que dependen de esta percha
    public function hijos(): HasMany
    {
        return $this->hasMany(
            Ubicacion::class,
            'ubicacion_padre_id'
        );
    }
}
