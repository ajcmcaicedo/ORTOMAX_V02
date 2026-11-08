<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lote extends Model
{
    protected $table = 'lotes';

    protected $fillable = [
        'producto_id',
        'numero_lote',
        'fecha_fabricacion',
        'fecha_vencimiento',
        'registro_sanitario',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_fabricacion' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class,
            'producto_id'
        );
    }

    public function inventarioEquipos(): HasMany
    {
        return $this->hasMany(
            EquipoInventario::class,
            'lote_id'
        );
    }
}