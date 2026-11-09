<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Violación de una regla del negocio (no un error de programación).
 *
 * Se muestra al usuario tal cual: en peticiones JSON responde 422 con
 * { message, detalles }; en formularios vuelve atrás con el mensaje.
 */
class ReglaNegocioException extends RuntimeException
{
    public function __construct(string $mensaje, public readonly array $detalles = [])
    {
        parent::__construct($mensaje);
    }
}
