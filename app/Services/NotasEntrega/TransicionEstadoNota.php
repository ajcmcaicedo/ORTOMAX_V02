<?php

namespace App\Services\NotasEntrega;

use App\Enums\EstadoNotaEntrega;
use App\Models\Bitacora;
use App\Models\NotaEntrega;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ReglaNegocioException;

/**
 * Único punto para cambiar el estado de una Nota de Entrega.
 *
 * - Bloquea la fila de la nota (lockForUpdate) durante toda la operación.
 * - Valida la transición contra EstadoNotaEntrega::transicionesPermitidas().
 * - Bloqueo optimista opcional (versión que el usuario tenía en pantalla).
 * - Ejecuta los efectos (reservas, despacho, etc.) dentro de la misma
 *   transacción: si fallan, el estado no cambia.
 * - Idempotente: si la nota ya está en el estado destino, no repite
 *   efectos (doble clic).
 * - Registra el cambio en la bitácora.
 *
 * La autorización (Policy) se valida antes, en el controlador.
 */
class TransicionEstadoNota
{
    /**
     * @param Closure(NotaEntrega): void|null $efectos
     */
    public function ejecutar(
        NotaEntrega $nota,
        EstadoNotaEntrega $destino,
        User $usuario,
        ?string $motivo = null,
        ?int $versionEsperada = null,
        ?Closure $efectos = null,
    ): NotaEntrega {
        if ($destino === EstadoNotaEntrega::ANULADA && trim((string) $motivo) === '') {
            throw new ReglaNegocioException('La anulación requiere un motivo.');
        }

        return DB::transaction(function () use ($nota, $destino, $usuario, $motivo, $versionEsperada, $efectos) {
            /** @var NotaEntrega $bloqueada */
            $bloqueada = NotaEntrega::whereKey($nota->getKey())->lockForUpdate()->firstOrFail();

            $origen = $bloqueada->estado;

            if ($origen === $destino) {
                return $bloqueada;
            }

            if ($versionEsperada !== null && $bloqueada->version !== $versionEsperada) {
                throw new NotaModificadaException(
                    'La nota fue modificada por otro usuario. Recargue la pantalla.'
                );
            }

            if (!$origen->puedePasarA($destino)) {
                throw new TransicionNoPermitidaException(sprintf(
                    'No se puede pasar de %s a %s.',
                    $origen->etiqueta(),
                    $destino->etiqueta()
                ));
            }

            if ($efectos) {
                $efectos($bloqueada);
            }

            $cambios = [
                'estado' => $destino,
                'version' => $bloqueada->version + 1,
            ];

            if ($columna = $destino->columnaFecha()) {
                $cambios[$columna] = now();
            }

            $bloqueada->forceFill($cambios)->save();

            Bitacora::registrar(
                evento: 'NOTA_ESTADO',
                auditable: $bloqueada,
                anterior: ['estado' => $origen->value],
                nuevo: ['estado' => $destino->value],
                motivo: $motivo,
                usuarioId: $usuario->id,
            );

            $nota->setRawAttributes($bloqueada->getAttributes(), true);

            return $bloqueada;
        });
    }
}
