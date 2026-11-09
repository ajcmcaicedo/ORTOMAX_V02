<?php

namespace App\Services\Inventario;

use InvalidArgumentException;

/**
 * Lugar desde/hacia donde se mueve inventario.
 *
 * - stockLote: fila de stock_lotes (bodega + ubicación + producto + lote).
 *   Como destino, se crea la fila si no existe.
 * - equipoInventario: fila existente de equipo_inventario.
 * - enEquipo: contenido de un equipo por producto/lote (se crea como destino).
 * - externo: fuera del inventario (entrada de proveedor, consumo, baja).
 */
final class PuntoStock
{
    public const STOCK_LOTE = 'STOCK_LOTE';
    public const EQUIPO_INVENTARIO = 'EQUIPO_INVENTARIO';

    private function __construct(
        public readonly string $tipo,
        public readonly ?int $id = null,
        public readonly ?int $bodegaId = null,
        public readonly ?int $ubicacionId = null,
        public readonly ?int $equipoId = null,
        public readonly ?int $productoId = null,
        public readonly ?int $loteId = null,
    ) {
    }

    public static function stockLoteId(int $stockLoteId): self
    {
        return new self(self::STOCK_LOTE, id: $stockLoteId);
    }

    public static function stockLote(int $bodegaId, int $ubicacionId, int $productoId, int $loteId): self
    {
        return new self(
            self::STOCK_LOTE,
            bodegaId: $bodegaId,
            ubicacionId: $ubicacionId,
            productoId: $productoId,
            loteId: $loteId,
        );
    }

    public static function equipoInventarioId(int $equipoInventarioId): self
    {
        return new self(self::EQUIPO_INVENTARIO, id: $equipoInventarioId);
    }

    public static function enEquipo(int $equipoId, int $productoId, ?int $loteId): self
    {
        return new self(
            self::EQUIPO_INVENTARIO,
            equipoId: $equipoId,
            productoId: $productoId,
            loteId: $loteId,
        );
    }

    public function esStockLote(): bool
    {
        return $this->tipo === self::STOCK_LOTE;
    }

    public function validar(): void
    {
        if ($this->id !== null) {
            return;
        }

        $completo = $this->esStockLote()
            ? $this->bodegaId && $this->ubicacionId && $this->productoId && $this->loteId
            : $this->equipoId && $this->productoId;

        if (!$completo) {
            throw new InvalidArgumentException('Punto de stock incompleto.');
        }
    }
}
