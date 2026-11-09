<?php

namespace App\Services\NotasEntrega;

use App\Exceptions\ReglaNegocioException;

/**
 * Otro usuario modificó la nota desde que se cargó la pantalla
 * (bloqueo optimista por la columna version).
 */
class NotaModificadaException extends ReglaNegocioException
{
}
