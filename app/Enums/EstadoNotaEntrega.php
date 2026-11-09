<?php

namespace App\Enums;

/**
 * Estados unificados de la Nota de Entrega (aprobados en Fase 0).
 *
 * BORRADOR → EN_ALISTAMIENTO → ALISTADA → DESPACHADA → ENTREGADA
 *          → EN_CONCILIACION → EN_DEVOLUCION → CERRADA
 * ANULADA desde cualquier estado previo a EN_DEVOLUCION.
 */
enum EstadoNotaEntrega: string
{
    case BORRADOR = 'BORRADOR';
    case EN_ALISTAMIENTO = 'EN_ALISTAMIENTO';
    case ALISTADA = 'ALISTADA';
    case DESPACHADA = 'DESPACHADA';
    case ENTREGADA = 'ENTREGADA';
    case EN_CONCILIACION = 'EN_CONCILIACION';
    case EN_DEVOLUCION = 'EN_DEVOLUCION';
    case CERRADA = 'CERRADA';
    case ANULADA = 'ANULADA';

    /**
     * Estados a los que se puede pasar desde el actual.
     *
     * @return array<int, self>
     */
    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::BORRADOR => [self::EN_ALISTAMIENTO, self::ANULADA],
            // Volver a BORRADOR libera las reservas.
            self::EN_ALISTAMIENTO => [self::ALISTADA, self::BORRADOR, self::ANULADA],
            // Reabrir el alistamiento antes de despachar.
            self::ALISTADA => [self::DESPACHADA, self::EN_ALISTAMIENTO, self::ANULADA],
            self::DESPACHADA => [self::ENTREGADA, self::ANULADA],
            self::ENTREGADA => [self::EN_CONCILIACION, self::ANULADA],
            self::EN_CONCILIACION => [self::EN_DEVOLUCION, self::ANULADA],
            self::EN_DEVOLUCION => [self::CERRADA],
            self::CERRADA, self::ANULADA => [],
        };
    }

    public function puedePasarA(self $destino): bool
    {
        return in_array($destino, $this->transicionesPermitidas(), true);
    }

    public function esFinal(): bool
    {
        return $this === self::CERRADA || $this === self::ANULADA;
    }

    /**
     * El contenido (equipos, ítems, lotes, cantidades) se puede
     * modificar libremente solo en estos estados. Después del
     * despacho, únicamente mediante ajuste autorizado.
     */
    public function permiteEditarContenido(): bool
    {
        return $this === self::BORRADOR || $this === self::EN_ALISTAMIENTO;
    }

    /**
     * Mantiene equipos ocupados (no disponibles para otra nota).
     */
    public function ocupaEquipos(): bool
    {
        return !$this->esFinal() && $this !== self::BORRADOR;
    }

    /**
     * Inventario ya salió de la bodega (en consignación o consumido).
     */
    public function inventarioDespachado(): bool
    {
        return in_array($this, [
            self::DESPACHADA,
            self::ENTREGADA,
            self::EN_CONCILIACION,
            self::EN_DEVOLUCION,
        ], true);
    }

    /**
     * Columna de fecha que se completa al entrar en el estado.
     */
    public function columnaFecha(): ?string
    {
        return match ($this) {
            self::DESPACHADA => 'fecha_despacho',
            self::ENTREGADA => 'fecha_entrega',
            self::CERRADA => 'fecha_cierre',
            self::ANULADA => 'fecha_anulacion',
            default => null,
        };
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::BORRADOR => 'Borrador',
            self::EN_ALISTAMIENTO => 'En alistamiento',
            self::ALISTADA => 'Alistada',
            self::DESPACHADA => 'Despachada',
            self::ENTREGADA => 'Entregada',
            self::EN_CONCILIACION => 'En conciliación',
            self::EN_DEVOLUCION => 'En devolución',
            self::CERRADA => 'Cerrada',
            self::ANULADA => 'Anulada',
        };
    }
}
