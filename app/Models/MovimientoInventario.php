<?php

namespace App\Models;

use App\Enums\TipoMovimiento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

/**
 * Cabecera de movimiento de inventario.
 *
 * Solo App\Services\Inventario\MotorInventario debe crearlos.
 * Un movimiento registrado no se modifica ni se elimina: se reversa.
 */
class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'fecha_hora',
        'tipo_movimiento',
        'bodega_origen_id',
        'equipo_origen_id',
        'bodega_destino_id',
        'equipo_destino_id',
        'nota_entrega_id',
        'usuario_id',
        'referencia',
        'idempotency_key',
        'movimiento_reversado_id',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'tipo_movimiento' => TipoMovimiento::class,
    ];

    protected static function booted(): void
    {
        static::deleting(function () {
            throw new LogicException(
                'Los movimientos de inventario no se eliminan: se reversan.'
            );
        });
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(MovimientoInventarioDetalle::class, 'movimiento_inventario_id');
    }

    public function nota(): BelongsTo
    {
        return $this->belongsTo(NotaEntrega::class, 'nota_entrega_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function movimientoReversado(): BelongsTo
    {
        return $this->belongsTo(self::class, 'movimiento_reversado_id');
    }

    public function reversa(): HasOne
    {
        return $this->hasOne(self::class, 'movimiento_reversado_id');
    }
}
