<?php

namespace App\Http\Controllers;

use App\Enums\TipoItem;
use App\Models\Equipo;
use App\Models\NotaEntrega;
use App\Models\NotaEntregaDetalle;
use App\Services\NotasEntrega\OcupacionEquipos;
use App\Services\NotasEntrega\ServicioBorradorNota;
use App\Services\NotasEntrega\SugeridorEquipos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Equipos de una nota: búsqueda, sugerencia por tipo de cirugía,
 * agregar y retirar.
 */
class NotaEntregaEquipoController extends Controller
{
    public function __construct(
        private ServicioBorradorNota $borrador,
        private SugeridorEquipos $sugeridor,
        private OcupacionEquipos $ocupacion,
    ) {
    }

    /**
     * Búsqueda por código o nombre (JSON), con disponibilidad.
     */
    public function buscar(Request $request, NotaEntrega $nota)
    {
        Gate::authorize('alistar', $nota);

        $texto = trim((string) $request->query('q'));

        if (mb_strlen($texto) < 2) {
            return response()->json(['equipos' => []]);
        }

        $equipos = Equipo::query()
            ->where(fn ($q) => $q->where('codigo', 'like', "%{$texto}%")
                ->orWhere('nombre', 'like', "%{$texto}%")
                ->orWhere('codigo_referencia', 'like', "%{$texto}%"))
            ->orderBy('codigo')
            ->limit(20)
            ->get();

        $ocupaciones = $this->ocupacion->ocupaciones($equipos->pluck('id')->all(), $nota->id);
        $enNota = $nota->lineasEquipo()->pluck('equipo_id')->all();

        return response()->json([
            'equipos' => $equipos->map(function (Equipo $e) use ($ocupaciones, $enNota) {
                $ocup = $ocupaciones->get($e->id, collect());
                $bloqueante = $ocup->firstWhere('bloquea', true);

                return [
                    'id' => $e->id,
                    'codigo' => $e->codigo,
                    'nombre' => $e->nombre,
                    'codigo_referencia' => $e->codigo_referencia,
                    'en_nota' => in_array($e->id, $enNota, true),
                    'disponible' => $e->estaOperativo() && !$bloqueante,
                    'motivo' => !$e->estaOperativo()
                        ? "Estado {$e->estado}"
                        : ($bloqueante ? 'Ocupado en ' . OcupacionEquipos::describir($bloqueante) : null),
                    'en_borradores' => $ocup->where('bloquea', false)->pluck('numero')->values(),
                ];
            }),
        ]);
    }

    public function sugerencias(NotaEntrega $nota)
    {
        Gate::authorize('alistar', $nota);

        return response()->json(['sugerencias' => $this->sugeridor->sugerir($nota)]);
    }

    /**
     * Confirma los equipos seleccionados de la sugerencia.
     */
    public function aceptarSugerencias(Request $request, NotaEntrega $nota)
    {
        Gate::authorize('alistar', $nota);

        $datos = $request->validate([
            'equipos' => ['required', 'array', 'min:1'],
            'equipos.*' => ['integer', 'exists:equipos,id'],
        ]);

        $resultado = $this->borrador->aceptarSugerencias($nota, $datos['equipos'], $request->user());

        $respuesta = [
            'agregados' => collect($resultado['agregados'])->pluck('codigo'),
            'rechazados' => $resultado['rechazados'],
        ];

        if ($request->expectsJson()) {
            return response()->json($respuesta);
        }

        $redirect = back()->with('success', count($resultado['agregados']) . ' equipo(s) agregado(s).');

        return $resultado['rechazados']
            ? $redirect->withErrors(['negocio' => collect($resultado['rechazados'])->pluck('motivo')->implode(' ')])
            : $redirect;
    }

    public function store(Request $request, NotaEntrega $nota)
    {
        Gate::authorize('alistar', $nota);

        $datos = $request->validate(['equipo_id' => ['required', 'integer', 'exists:equipos,id']]);

        $linea = $this->borrador->agregarEquipo($nota, Equipo::findOrFail($datos['equipo_id']), $request->user());

        return $request->expectsJson()
            ? response()->json(['linea_id' => $linea->id, 'codigo' => $linea->codigo], 201)
            : back()->with('success', "Equipo {$linea->codigo} agregado.");
    }

    public function destroy(Request $request, NotaEntrega $nota, NotaEntregaDetalle $linea)
    {
        Gate::authorize('alistar', $nota);

        abort_unless($linea->nota_entrega_id === $nota->id && $linea->tipo_linea === TipoItem::EQUIPO, 404);

        $datos = $request->validate(['motivo' => ['nullable', 'string', 'max:500']]);

        $this->borrador->quitarEquipo($nota, $linea, $request->user(), $datos['motivo'] ?? null);

        return back()->with('success', "Equipo {$linea->codigo} retirado.");
    }
}
