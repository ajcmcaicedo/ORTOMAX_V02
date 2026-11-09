<?php

namespace App\Enums;

/**
 * Tipos de movimiento de inventario.
 * Los siete primeros son los documentados en la migración original.
 */
enum TipoMovimiento: string
{
    case ENTRADA = 'ENTRADA';
    case SALIDA = 'SALIDA';
    case TRASLADO = 'TRASLADO';
    case REPOSICION = 'REPOSICION';
    case DEVOLUCION = 'DEVOLUCION';
    case CONSUMO = 'CONSUMO';
    case AJUSTE = 'AJUSTE';

    /** Bodega origen → consignación en la institución. */
    case DESPACHO = 'DESPACHO';

    /** Material dañado / no apto → ubicación de cuarentena. */
    case CUARENTENA = 'CUARENTENA';

    /** Salida definitiva por baja autorizada. */
    case BAJA = 'BAJA';

    /** Anula otro movimiento invirtiendo origen y destino. */
    case REVERSA = 'REVERSA';
}
