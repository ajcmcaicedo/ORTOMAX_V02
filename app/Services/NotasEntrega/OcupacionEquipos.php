<?php

namespace App\Services\NotasEntrega;

use App\Enums\EstadoNotaEntrega;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ¿En qué notas está cada equipo?
 *
 * La ocupación se deduce de las líneas EQUIPO de notas no finales
 * (no se duplica en equipos.estado):
 * - bloquea = true: nota desde EN_ALISTAMIENTO hasta EN_DEVOLUCION.
 * - bloquea = false: otra nota en BORRADOR (solo advertencia).
 */
class OcupacionEquipos
{
    /**
     * @param array<int> $equipoIds
     * @param bool $lecturaBloqueante usar dentro de transacciones que validan
     *             (lee el último dato confirmado y bloquea las filas leídas)
     * @return Collection<int, Collection<int, array>> equipo_id => ocupaciones
     */
    public function ocupaciones(array $equipoIds, ?int $excluirNotaId = null, bool $lecturaBloqueante = false): Collection
    {
        if ($equipoIds === []) {
            return collect();
        }

        $estados = collect(EstadoNotaEntrega::cases())
            ->reject(fn (EstadoNotaEntrega $e) => $e->esFinal())
            ->map(fn (EstadoNotaEntrega $e) => $e->value)
            ->values()
            ->all();

        $query = DB::table('nota_entrega_detalles as d')
            ->join('notas_entrega as n', 'n.id', '=', 'd.nota_entrega_id')
            ->leftJoin('instituciones as i', 'i.id', '=', 'n.institucion_id')
            ->where('d.tipo_linea', 'EQUIPO')
            ->whereIn('d.equipo_id', $equipoIds)
            ->whereIn('n.estado', $estados)
            ->when($excluirNotaId, fn ($q) => $q->where('n.id', '!=', $excluirNotaId))
            ->orderBy('n.fecha_cirugia_programada')
            ->select([
                'd.equipo_id',
                'n.id as nota_id',
                'n.numero',
                'n.estado',
                'n.fecha_cirugia_programada',
                DB::raw('COALESCE(i.nombre_comercial, i.razon_social) as institucion'),
            ]);

        if ($lecturaBloqueante) {
            $query->sharedLock();
        }

        return $query->get()
            ->map(fn ($fila) => [
                'equipo_id' => (int) $fila->equipo_id,
                'nota_id' => (int) $fila->nota_id,
                'numero' => trim($fila->numero),
                'estado' => $fila->estado,
                'institucion' => $fila->institucion,
                'fecha_cirugia_programada' => $fila->fecha_cirugia_programada,
                'bloquea' => EstadoNotaEntrega::from($fila->estado)->ocupaEquipos(),
            ])
            ->groupBy('equipo_id');
    }

    /**
     * Solo las ocupaciones que impiden usar el equipo.
     */
    public function bloqueantes(array $equipoIds, ?int $excluirNotaId = null, bool $lecturaBloqueante = false): Collection
    {
        return $this->ocupaciones($equipoIds, $excluirNotaId, $lecturaBloqueante)
            ->map(fn (Collection $o) => $o->where('bloquea', true)->values())
            ->filter(fn (Collection $o) => $o->isNotEmpty());
    }

    public static function describir(array $ocupacion): string
    {
        return sprintf(
            'nota %s (%s) en %s',
            $ocupacion['numero'],
            EstadoNotaEntrega::from($ocupacion['estado'])->etiqueta(),
            $ocupacion['institucion'] ?? 'institución sin nombre'
        );
    }
}
