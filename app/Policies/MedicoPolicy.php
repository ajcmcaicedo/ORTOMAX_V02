<?php

namespace App\Policies;

use App\Models\Medico;
use App\Models\User;

/**
 * Catálogo de médicos: quien crea o alista notas puede registrar un
 * médico nuevo al momento (llega por teléfono o WhatsApp).
 */
class MedicoPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->gestiona($user);
    }

    public function create(User $user): bool
    {
        return $this->gestiona($user);
    }

    public function update(User $user, Medico $medico): bool
    {
        return $this->gestiona($user);
    }

    private function gestiona(User $user): bool
    {
        return $user->tienePermiso('notas.crear')
            || $user->tienePermiso('notas.alistar')
            || $user->tienePermiso('plantillas_cirugia.editar');
    }
}
