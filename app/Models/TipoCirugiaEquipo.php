<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Línea de plantilla: un MODELO de equipo (codigo_referencia)
 * sugerido para un tipo de cirugía.
 */
class TipoCirugiaEquipo extends Model
{
    protected $table = 'tipo_cirugia_equipos';

    protected $fillable = [
        'tipo_cirugia_id',
        'codigo_referencia',
        'orden',
        'obligatorio',
        'observacion',
    ];

    protected $casts = [
        'obligatorio' => 'boolean',
        'orden' => 'integer',
    ];

    public function tipoCirugia(): BelongsTo
    {
        return $this->belongsTo(TipoCirugia::class, 'tipo_cirugia_id');
    }

    /**
     * Copias físicas (N.1, N.2, ...) de este modelo de equipo.
     */
    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'codigo_referencia', 'codigo_referencia');
    }
}
