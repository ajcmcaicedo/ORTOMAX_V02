<?php

namespace App\Services\NotasEntrega;

use RuntimeException;

/**
 * Otro usuario modificó la nota desde que se cargó la pantalla
 * (bloqueo optimista por la columna version).
 */
class NotaModificadaException extends RuntimeException
{
}
