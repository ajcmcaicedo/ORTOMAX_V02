<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activo extends Model
{
    protected $table = 'activos';

    protected $fillable = [
        'producto_id',
        'codigo',
        'numero_serial',
        'nombre',
        'tipo_activo',
        'marca',
        'modelo',
        'serie',
        'estado',
        'bodega_id',
        'equipo_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Producto de catálogo al que pertenece
     * este activo.
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class,
            'producto_id'
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

    /**
     * Equipo al que pertenece actualmente.
     */
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(
            Equipo::class,
            'equipo_id'
        );
    }

    /**
     * Configuraciones de equipos donde
     * aparece este activo.
     */
    public function equipoDetalles(): HasMany
    {
        return $this->hasMany(
            EquipoDetalle::class,
            'activo_id'
        );
    }

    /**
     * Registros de inventario de equipos
     * donde aparece este activo.
     */
    public function equipoInventario(): HasMany
    {
        return $this->hasMany(
            EquipoInventario::class,
            'activo_id'
        );
    }
}