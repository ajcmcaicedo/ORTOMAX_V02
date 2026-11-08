<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bodega extends Model
{
    protected $table = 'bodegas';

    protected $fillable = [
        'regional_id',
        'codigo',
        'nombre',
        'tipo_bodega',
        'direccion',
        'telefono',
        'activo',
        'contifico_id',
        'contifico_venta',
        'contifico_produccion',
        'contifico_compra',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'contifico_venta' => 'boolean',
        'contifico_produccion' => 'boolean',
        'contifico_compra' => 'boolean',
    ];

    /**
     * Regional a la que pertenece la bodega.
     */
    public function regional(): BelongsTo
    {
        return $this->belongsTo(
            Regional::class,
            'regional_id'
        );
    }

    /**
     * Ubicaciones pertenecientes a esta bodega.
     */
    public function ubicaciones(): HasMany
    {
        return $this->hasMany(
            Ubicacion::class,
            'bodega_id'
        );
    }

    /**
     * Equipos actualmente asignados a esta bodega.
     */
    public function equipos(): HasMany
    {
        return $this->hasMany(
            Equipo::class,
            'bodega_id'
        );
    }

    /**
     * Activos actualmente ubicados en esta bodega.
     */
    public function activos(): HasMany
    {
        return $this->hasMany(
            Activo::class,
            'bodega_id'
        );
    }
}