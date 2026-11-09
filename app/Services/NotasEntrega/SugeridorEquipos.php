<?php

namespace App\Services\NotasEntrega;

use App\Models\Equipo;
use App\Models\NotaEntrega;
use App\Models\TipoCirugiaEquipo;
use Illuminate\Support\Collection;

/**
 * Sugerencia de equipos según la plantilla del tipo de cirugía.
 *
 * SOLO LECTURA: nunca agrega equipos a la nota. El preparador confirma
 * cuáles acepta (ServicioBorradorNota::aceptarSugerencias).
 *
 * Por cada línea de plantilla (modelo de equipo = codigo_referencia):
 * - Ordena las copias físicas por preferencia: misma bodega de la nota,
 *   misma regional, resto; y dentro de cada grupo por código (N.1, N.2…).
 * - Propone la primera copia disponible.
 * - Informa las copias ocupadas (nota, estado, institución) y las que
 *   están en mantenimiento, para que el preparador decida.
 */
class SugeridorEquipos
{
    public const DISPONIBLE = 'DISPONIBLE';
    public const YA_EN_NOTA = 'YA_EN_NOTA';
    public const SIN_DISPONIBLE = 'SIN_DISPONIBLE';
    public const SIN_EQUIPOS = 'SIN_EQUIPOS';

    public function __construct(private OcupacionEquipos $ocupacion)
    {
    }

    /**
     * @return array<int, array>
     */
    public function sugerir(NotaEntrega $nota): array
    {
        if (!$nota->tipo_cirugia_id) {
            return [];
        }

        $plantilla = TipoCirugiaEquipo::where('tipo_cirugia_id', $nota->tipo_cirugia_id)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        if ($plantilla->isEmpty()) {
            return [];
        }

        $copias = Equipo::whereIn('codigo_referencia', $plantilla->pluck('codigo_referencia'))
            ->get()
            ->groupBy('codigo_referencia');

        $idsCopias = $copias->flatten()->pluck('id')->all();

        $ocupaciones = $this->ocupacion->ocupaciones($idsCopias, $nota->id);

        $enEstaNota = $nota->lineasEquipo()->pluck('equipo_id')->filter()->all();

        return $plantilla->map(function (TipoCirugiaEquipo $linea) use ($copias, $ocupaciones, $enEstaNota, $nota) {
            /** @var Collection<int, Equipo> $equipos */
            $equipos = $this->ordenarPorPreferencia($copias->get($linea->codigo_referencia, collect()), $nota);

            $base = [
                'plantilla_id' => $linea->id,
                'codigo_referencia' => $linea->codigo_referencia,
                'obligatorio' => $linea->obligatorio,
                'observacion' => $linea->observacion,
                'propuesto' => null,
                'no_disponibles' => [],
                'otras_disponibles' => [],
            ];

            if ($equipos->isEmpty()) {
                return $base + ['estado' => self::SIN_EQUIPOS];
            }

            $yaAgregado = $equipos->first(fn (Equipo $e) => in_array($e->id, $enEstaNota, true));

            if ($yaAgregado) {
                return array_merge($base, [
                    'estado' => self::YA_EN_NOTA,
                    'propuesto' => $this->resumen($yaAgregado, $nota),
                ]);
            }

            $disponibles = [];
            $noDisponibles = [];

            foreach ($equipos as $equipo) {
                $ocup = $ocupaciones->get($equipo->id, collect());
                $bloqueantes = $ocup->where('bloquea', true)->values();

                $item = $this->resumen($equipo, $nota) + [
                    'ocupaciones' => $bloqueantes->all(),
                    'en_borradores' => $ocup->where('bloquea', false)->values()->all(),
                ];

                if (!$equipo->estaOperativo()) {
                    $noDisponibles[] = $item + ['motivo' => 'Equipo en estado ' . $equipo->estado];
                } elseif ($bloqueantes->isNotEmpty()) {
                    $noDisponibles[] = $item + [
                        'motivo' => 'Ocupado en ' . OcupacionEquipos::describir($bloqueantes->first()),
                    ];
                } else {
                    $disponibles[] = $item;
                }
            }

            return array_merge($base, [
                'estado' => $disponibles ? self::DISPONIBLE : self::SIN_DISPONIBLE,
                'propuesto' => $disponibles[0] ?? null,
                'otras_disponibles' => array_slice($disponibles, 1),
                'no_disponibles' => $noDisponibles,
            ]);
        })->all();
    }

    private function ordenarPorPreferencia(Collection $equipos, NotaEntrega $nota): Collection
    {
        return $equipos->sortBy([
            fn (Equipo $a, Equipo $b) => $this->rango($a, $nota) <=> $this->rango($b, $nota),
            fn (Equipo $a, Equipo $b) => strnatcmp($a->codigo, $b->codigo),
        ])->values();
    }

    private function rango(Equipo $equipo, NotaEntrega $nota): int
    {
        if ($nota->bodega_origen_id && $equipo->bodega_id === $nota->bodega_origen_id) {
            return 0;
        }

        if ($nota->regional_id && $equipo->regional_id === $nota->regional_id) {
            return 1;
        }

        return 2;
    }

    private function resumen(Equipo $equipo, NotaEntrega $nota): array
    {
        return [
            'equipo_id' => $equipo->id,
            'codigo' => $equipo->codigo,
            'nombre' => $equipo->nombre,
            'estado_equipo' => $equipo->estado,
            'misma_bodega' => $nota->bodega_origen_id !== null && $equipo->bodega_id === $nota->bodega_origen_id,
        ];
    }
}
