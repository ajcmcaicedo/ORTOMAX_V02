<?php

namespace App\Models;

use App\Enums\EstadoNotaEntrega;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NotaEntrega extends Model
{
    protected $table = 'notas_entrega';

    protected $fillable = [
        'numero',
        'institucion_id',
        'regional_id',
        'bodega_origen_id',
        'tipo_cirugia_id',
        'medico_id',
        'solicitado_por_id',
        'creado_por_id',
        'preparador_id',
        'instrumentador_id',
        'fecha_emision',
        'fecha_cirugia_programada',
        'fecha_cirugia_efectiva',
        'fecha_despacho',
        'fecha_entrega',
        'fecha_cierre',
        'fecha_anulacion',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'estado' => EstadoNotaEntrega::class,
        'fecha_emision' => 'datetime',
        'fecha_cirugia_programada' => 'datetime',
        'fecha_cirugia_efectiva' => 'datetime',
        'fecha_despacho' => 'datetime',
        'fecha_entrega' => 'datetime',
        'fecha_cierre' => 'datetime',
        'fecha_anulacion' => 'datetime',
        'version' => 'integer',
    ];

    protected $attributes = [
        'estado' => 'BORRADOR',
        'version' => 1,
    ];

    public function institucion(): BelongsTo
    {
        return $this->belongsTo(Institucion::class, 'institucion_id');
    }

    public function regional(): BelongsTo
    {
        return $this->belongsTo(Regional::class, 'regional_id');
    }

    public function bodegaOrigen(): BelongsTo
    {
        return $this->belongsTo(Bodega::class, 'bodega_origen_id');
    }

    public function tipoCirugia(): BelongsTo
    {
        return $this->belongsTo(TipoCirugia::class, 'tipo_cirugia_id');
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    /**
     * Datos sensibles. Para mostrarlos usar ServicioBorradorNota::verPaciente().
     */
    public function paciente(): HasOne
    {
        return $this->hasOne(NotaEntregaPaciente::class, 'nota_entrega_id');
    }

    /**
     * Líneas cabecera de equipo (tipo_linea = EQUIPO).
     */
    public function lineasEquipo(): HasMany
    {
        return $this->hasMany(NotaEntregaDetalle::class, 'nota_entrega_id')
            ->where('tipo_linea', 'EQUIPO')
            ->orderBy('id');
    }

    public function instrumentador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instrumentador_id');
    }

    public function preparador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'preparador_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por_id');
    }

    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(NotaEntregaDetalle::class, 'nota_entrega_id');
    }

    public function consumos(): HasMany
    {
        return $this->hasMany(Consumo::class, 'nota_entrega_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'nota_entrega_id');
    }

    public function bitacora(): HasMany
    {
        return $this->hasMany(Bitacora::class, 'nota_entrega_id');
    }

    /**
     * Notas que mantienen equipos ocupados.
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->whereIn(
            'estado',
            collect(EstadoNotaEntrega::cases())
                ->filter(fn (EstadoNotaEntrega $e) => $e->ocupaEquipos())
                ->map(fn (EstadoNotaEntrega $e) => $e->value)
                ->values()
                ->all()
        );
    }

    public function estaAsignadaA(User $user): bool
    {
        return in_array($user->id, array_filter([
            $this->instrumentador_id,
            $this->preparador_id,
            $this->creado_por_id,
            $this->solicitado_por_id,
        ]), true);
    }
}
