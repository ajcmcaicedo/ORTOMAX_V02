<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consumo extends Model
{
    protected $table = 'consumos';

    protected $fillable = [
        'nota_entrega_id',
        'nota_entrega_detalle_id',
        'equipo_id',
        'producto_id',
        'lote_id',
        'instrumentador_id',
        'cantidad',
        'fecha_hora',
        'estado',
        'es_correccion',
        'consumo_original_id',
        'motivo_correccion',
        'observaciones',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'fecha_hora' => 'datetime',
        'es_correccion' => 'boolean',
    ];

    public function nota(): BelongsTo
    {
        return $this->belongsTo(NotaEntrega::class, 'nota_entrega_id');
    }

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(NotaEntregaDetalle::class, 'nota_entrega_detalle_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    public function instrumentador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instrumentador_id');
    }

    public function consumoOriginal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'consumo_original_id');
    }
}
