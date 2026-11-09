<?php

namespace App\Policies;

use App\Models\TipoCirugia;
use App\Models\User;

/**
 * Plantillas por tipo de cirugía: todos los que trabajan notas pueden
 * consultarlas; solo usuarios autorizados las editan.
 */
class TipoCirugiaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tienePermiso('plantillas_cirugia.editar')
            || $user->tienePermiso('notas.crear')
            || $user->tienePermiso('notas.alistar');
    }

    public function view(User $user, TipoCirugia $tipo): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->tienePermiso('plantillas_cirugia.editar');
    }

    public function update(User $user, TipoCirugia $tipo): bool
    {
        return $user->tienePermiso('plantillas_cirugia.editar');
    }
}
