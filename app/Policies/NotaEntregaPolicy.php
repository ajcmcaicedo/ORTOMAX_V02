<?php

namespace App\Policies;

use App\Enums\EstadoNotaEntrega;
use App\Models\NotaEntrega;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Reglas de acceso a Notas de Entrega.
 *
 * Combina: permiso del rol + estado de la nota + asignación del usuario.
 * Las transiciones de estado se validan además en TransicionEstadoNota.
 */
class NotaEntregaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tienePermiso('notas.ver_todas')
            || $user->tienePermiso('notas.ver');
    }

    public function view(User $user, NotaEntrega $nota): bool
    {
        if ($user->tienePermiso('notas.ver_todas')) {
            return true;
        }

        return $user->tienePermiso('notas.ver') && $nota->estaAsignadaA($user);
    }

    public function create(User $user): bool
    {
        return $user->tienePermiso('notas.crear');
    }

    /**
     * Editar cabecera y contenido (equipos, ítems, lotes, cantidades).
     */
    public function alistar(User $user, NotaEntrega $nota): Response
    {
        if (!$user->tienePermiso('notas.alistar')) {
            return Response::deny('No tiene permiso para alistar notas.');
        }

        if (!$nota->estado->permiteEditarContenido()) {
            return Response::deny(
                'La nota está ' . $nota->estado->etiqueta()
                . '; el contenido solo cambia mediante un ajuste autorizado.'
            );
        }

        return Response::allow();
    }

    public function despachar(User $user, NotaEntrega $nota): Response
    {
        if (!$user->tienePermiso('notas.despachar')) {
            return Response::deny('No tiene permiso para despachar.');
        }

        return $nota->estado === EstadoNotaEntrega::ALISTADA
            ? Response::allow()
            : Response::deny('Solo se despachan notas en estado Alistada.');
    }

    public function confirmarEntrega(User $user, NotaEntrega $nota): Response
    {
        if (!$user->tienePermiso('notas.confirmar_entrega')) {
            return Response::deny('No tiene permiso para confirmar entregas.');
        }

        if ($nota->estado !== EstadoNotaEntrega::DESPACHADA) {
            return Response::deny('Solo se confirman notas despachadas.');
        }

        // Quien ve todas (gerente) puede confirmar; si no, debe estar asignado.
        return $user->tienePermiso('notas.ver_todas') || $nota->instrumentador_id === $user->id
            ? Response::allow()
            : Response::deny('La nota no está asignada a usted.');
    }

    public function registrarConsumo(User $user, NotaEntrega $nota): Response
    {
        if (!$user->tienePermiso('consumos.registrar')) {
            return Response::deny('No tiene permiso para registrar consumos.');
        }

        if ($nota->instrumentador_id !== $user->id) {
            return Response::deny('La nota no está asignada a usted.');
        }

        return $nota->estado === EstadoNotaEntrega::ENTREGADA
            ? Response::allow()
            : Response::deny('El consumo solo se registra con la nota Entregada.');
    }

    /**
     * Ajuste autorizado después del despacho.
     */
    public function ajustar(User $user, NotaEntrega $nota): Response
    {
        if (!$user->tienePermiso('notas.ajustar')) {
            return Response::deny('No tiene permiso para autorizar ajustes.');
        }

        return $nota->estado->inventarioDespachado()
            ? Response::allow()
            : Response::deny('Los ajustes aplican solo a notas ya despachadas.');
    }

    public function verificarDevolucion(User $user, NotaEntrega $nota): Response
    {
        if (!$user->tienePermiso('devoluciones.verificar')) {
            return Response::deny('No tiene permiso para verificar devoluciones.');
        }

        return in_array($nota->estado, [
            EstadoNotaEntrega::EN_CONCILIACION,
            EstadoNotaEntrega::EN_DEVOLUCION,
        ], true)
            ? Response::allow()
            : Response::deny('La nota no está en conciliación ni en devolución.');
    }

    /**
     * Anular. Antes del despacho basta con poder alistar o crear;
     * si ya salió inventario se exige notas.anular (gerente).
     */
    public function anular(User $user, NotaEntrega $nota): Response
    {
        if (!$nota->estado->puedePasarA(EstadoNotaEntrega::ANULADA)) {
            return Response::deny('La nota ya no se puede anular en estado ' . $nota->estado->etiqueta() . '.');
        }

        if ($nota->estado->inventarioDespachado()) {
            return $user->tienePermiso('notas.anular')
                ? Response::allow()
                : Response::deny('Anular una nota despachada requiere autorización del gerente.');
        }

        return $user->tienePermiso('notas.anular')
            || $user->tienePermiso('notas.alistar')
            || $user->tienePermiso('notas.crear')
            ? Response::allow()
            : Response::deny('No tiene permiso para anular notas.');
    }

    public function verPaciente(User $user, NotaEntrega $nota): bool
    {
        return $user->tienePermiso('pacientes.ver') && $this->view($user, $nota);
    }
}
