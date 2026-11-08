<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipoDetalle extends Model
{
    protected $table = 'equipo_detalles';

    protected $fillable = [
        'equipo_id',
        'producto_id',
        'instrumental_id',
        'activo_id',
        'tipo_contenido',
        'grupo',
        'subgrupo',
        'bandeja',
        'orden',
        'cantidad_configurada',
        'requiere_lote',
        'requiere_serial',
        'activo',
    ];

    protected $casts = [
        'cantidad_configurada' => 'decimal:2',
        'requiere_lote' => 'boolean',
        'requiere_serial' => 'boolean',
        'activo' => 'boolean',
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
}