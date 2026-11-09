<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Línea de Kardex. Inmutable una vez creada.
 */
class MovimientoInventarioDetalle extends Model
{
    protected $table = 'movimiento_inventario_detalles';

    protected $fillable = [
        'movimiento_inventario_id',
        'nota_entrega_detalle_id',
        'producto_id',
        'lote_id',
        'instrumental_id',
        'activo_id',
        'cantidad',
        'bodega_origen_id',
        'ubicacion_origen_id',
        'equipo_origen_id',
        'stock_lote_origen_id',
        'equipo_inventario_origen_id',
        'saldo_origen_resultante',
        'bodega_destino_id',
        'ubicacion_destino_id',
        'equipo_destino_id',
        'stock_lote_destino_id',
        'equipo_inventario_destino_id',
        'saldo_destino_resultante',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'saldo_origen_resultante' => 'decimal:2',
        'saldo_destino_resultante' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('El detalle de un movimiento no se modifica.');
        });

        static::deleting(function () {
            throw new LogicException('El detalle de un movimiento no se elimina.');
        });
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(MovimientoInventario::class, 'movimiento_inventario_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    public function notaEntregaDetalle(): BelongsTo
    {
        return $this->belongsTo(NotaEntregaDetalle::class, 'nota_entrega_detalle_id');
    }
}
