<?php

namespace App\Models;

use App\Enums\TipoItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaEntregaDetalle extends Model
{
    protected $table = 'nota_entrega_detalles';

    protected $fillable = [
        'nota_entrega_id',
        'equipo_id',
        'producto_id',
        'lote_id',
        'activo_id',
        'tipo_linea',
        'codigo',
        'descripcion',
        'cantidad_enviada',
        'cantidad_recibida',
        'cantidad_consumida',
        'cantidad_faltante',
        'precio_unitario',
        'subtotal',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'tipo_linea' => TipoItem::class,
        'cantidad_enviada' => 'decimal:2',
        'cantidad_recibida' => 'decimal:2',
        'cantidad_consumida' => 'decimal:2',
        'cantidad_faltante' => 'decimal:2',
        'precio_unitario' => 'decimal:4',
        'subtotal' => 'decimal:4',
    ];

    public function nota(): BelongsTo
    {
        return $this->belongsTo(NotaEntrega::class, 'nota_entrega_id');
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    public function activo(): BelongsTo
    {
        return $this->belongsTo(Activo::class, 'activo_id');
    }
}
