<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * Bitácora de auditoría. Solo se puede insertar: el modelo bloquea
 * actualización y borrado (y en MySQL también los triggers, si se
 * pudieron crear).
 */
class Bitacora extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'bitacora';

    protected $fillable = [
        'evento',
        'auditable_type',
        'auditable_id',
        'nota_entrega_id',
        'usuario_id',
        'valores_anteriores',
        'valores_nuevos',
        'motivo',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'valores_anteriores' => 'array',
        'valores_nuevos' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('La bitácora no se puede modificar.');
        });

        static::deleting(function () {
            throw new LogicException('La bitácora no se puede eliminar.');
        });
    }

    /**
     * Registra un evento de auditoría.
     */
    public static function registrar(
        string $evento,
        ?Model $auditable = null,
        ?int $notaEntregaId = null,
        ?array $anterior = null,
        ?array $nuevo = null,
        ?string $motivo = null,
        ?int $usuarioId = null,
    ): self {
        $request = app()->bound('request') ? request() : null;

        return static::create([
            'evento' => $evento,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'nota_entrega_id' => $notaEntregaId
                ?? ($auditable instanceof NotaEntrega ? $auditable->getKey() : null),
            'usuario_id' => $usuarioId ?? Auth::id(),
            'valores_anteriores' => $anterior,
            'valores_nuevos' => $nuevo,
            'motivo' => $motivo,
            'ip' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
        ]);
    }
}
