<?php

namespace App\Services\Inventario;

use App\Enums\TipoMovimiento;
use InvalidArgumentException;

final class SolicitudMovimiento
{
    /**
     * @param array<int, LineaMovimiento> $lineas
     */
    public function __construct(
        public readonly TipoMovimiento $tipo,
        public readonly array $lineas,
        public readonly ?int $usuarioId,
        public readonly ?int $notaEntregaId = null,
        /**
         * Misma clave = mismo movimiento. Un segundo intento devuelve
         * el movimiento existente sin tocar el stock.
         */
        public readonly ?string $idempotencyKey = null,
        public readonly ?string $referencia = null,
        public readonly ?string $observaciones = null,
    ) {
        if ($lineas === []) {
            throw new InvalidArgumentException('El movimiento no tiene líneas.');
        }
    }
}
