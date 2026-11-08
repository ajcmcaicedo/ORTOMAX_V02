<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLote extends Model
{
    protected $table = 'stock_lotes';

    protected $fillable = [
        'bodega_id',
        'ubicacion_id',
        'producto_id',
        'lote_id',
        'cantidad',
        'cantidad_reservada',
        'estado',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'cantidad_reservada' => 'decimal:2',
        'cantidad_disponible' => 'decimal:2',
    ];

    /**
     * Bodega donde se encuentra físicamente el stock.
     */
    public function bodega(): BelongsTo
    {
        return $this->belongsTo(
            Bodega::class,
            'bodega_id'
        );
    }

    /**
     * Ubicación física dentro de la bodega.
     */
    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(
            Ubicacion::class,
            'ubicacion_id'
        );
    }

    /**
     * Producto al que pertenece el stock.
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class,
            'producto_id'
        );
    }

    /**
     * Lote al que pertenece el stock.
     */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(
            Lote::class,
            'lote_id'
        );
    }
}