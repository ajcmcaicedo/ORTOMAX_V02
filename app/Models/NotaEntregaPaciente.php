<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Datos del paciente de una nota. Columnas sensibles cifradas.
 *
 * No acceder directamente desde vistas: usar
 * ServicioBorradorNota::verPaciente(), que valida y audita el acceso.
 */
class NotaEntregaPaciente extends Model
{
    protected $table = 'nota_entrega_pacientes';

    protected $fillable = [
        'nota_entrega_id',
        'identificacion',
        'nombres',
        'historia_clinica',
        'aseguradora',
        'confirmado',
    ];

    protected $hidden = [
        'identificacion',
        'identificacion_hash',
        'nombres',
        'historia_clinica',
    ];

    protected $casts = [
        'identificacion' => 'encrypted',
        'nombres' => 'encrypted',
        'historia_clinica' => 'encrypted',
        'confirmado' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $paciente) {
            if ($paciente->isDirty('identificacion')) {
                $paciente->identificacion_hash = static::hashIdentificacion($paciente->identificacion);
            }
        });
    }

    /**
     * HMAC de la identificación normalizada: permite buscar por cédula
     * sin descifrar todos los registros.
     */
    public static function hashIdentificacion(?string $identificacion): ?string
    {
        $normalizada = preg_replace('/[^0-9A-Z]/', '', strtoupper((string) $identificacion));

        return $normalizada === ''
            ? null
            : hash_hmac('sha256', $normalizada, (string) config('app.key'));
    }

    public function nota(): BelongsTo
    {
        return $this->belongsTo(NotaEntrega::class, 'nota_entrega_id');
    }
}
