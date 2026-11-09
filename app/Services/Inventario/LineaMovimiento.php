<?php

namespace App\Services\Inventario;

use InvalidArgumentException;

/**
 * Una línea de movimiento: cantidad de un producto/lote desde un
 * origen hacia un destino. origen o destino NULL = externo.
 */
final class LineaMovimiento
{
    public function __construct(
        public readonly string|int|float $cantidad,
        public readonly ?PuntoStock $origen,
        public readonly ?PuntoStock $destino,
        /**
         * true: la cantidad estaba reservada en el origen (p. ej. despacho
         * de lo alistado); se descuenta también de cantidad_reservada.
         */
        public readonly bool $tomarDeReserva = false,
        public readonly ?int $notaEntregaDetalleId = null,
        public readonly ?int $activoId = null,
        public readonly ?int $instrumentalId = null,
    ) {
        if ($origen === null && $destino === null) {
            throw new InvalidArgumentException('Una línea necesita origen o destino.');
        }

        $origen?->validar();
        $destino?->validar();
    }
}
