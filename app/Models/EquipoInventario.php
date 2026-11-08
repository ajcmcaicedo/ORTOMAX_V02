<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipoInventario extends Model
{
    protected $table = 'equipo_inventario';

    protected $fillable = [
        'equipo_id',
        'producto_id',
        'instrumental_id',
        'activo_id',
        'lote_id',
        'serie',
        'grupo',
        'subgrupo',
        'bandeja',
        'orden',
        'cantidad',
        'cantidad_reservada',
        'estado',
        'fecha_ingreso',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'cantidad_reservada' => 'decimal:2',
        'cantidad_disponible' => 'decimal:2',
        'fecha_ingreso' => 'datetime',
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function instrumental(): BelongsTo
    {
        return $this->belongsTo(Instrumental::class, 'instrumental_id');
    }

    public function activo(): BelongsTo
    {
        return $this->belongsTo(Activo::class, 'activo_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }
}