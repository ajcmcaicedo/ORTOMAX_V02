<?php

namespace App\Services\NotasEntrega;

use App\Exceptions\ReglaNegocioException;

/**
 * El equipo está en mantenimiento/inactivo o en otra nota activa.
 * detalles: ocupaciones [['nota_id', 'numero', 'estado', 'institucion', ...], ...]
 */
class EquipoNoDisponibleException extends ReglaNegocioException
{
}
