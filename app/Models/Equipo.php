<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipo extends Model
{
    protected $table = 'equipos';

    protected $fillable = [
        'codigo',
        'codigo_referencia',
        'nombre',
        'descripcion',
        'tipo_equipo',
        'regional_id',
        'bodega_id',
        'estado',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Configuración del equipo.
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(
            EquipoDetalle::class,
            'equipo_id'
        );
    }

    /**
     * Inventario real actualmente contenido
     * dentro del equipo.
     */
    public function inventario(): HasMany
    {
        return $this->hasMany(
            EquipoInventario::class,
            'equipo_id'
        );
    }

    /**
     * Regional a la que pertenece el equipo.
     */
    public function regional(): BelongsTo
    {
        return $this->belongsTo(
            Regional::class,
            'regional_id'
        );
    }

    /**
     * Bodega donde se encuentra actualmente.
     */
    public function bodega(): BelongsTo
    {
        return $this->belongsTo(
            Bodega::class,
            'bodega_id'
        );
    }
}