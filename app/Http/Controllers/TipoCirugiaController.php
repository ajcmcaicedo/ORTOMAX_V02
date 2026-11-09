<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Equipo;
use App\Models\TipoCirugia;
use App\Models\TipoCirugiaEquipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Tipos de cirugía y su plantilla de equipos sugeridos.
 * Consulta: quien trabaja notas. Edición: plantillas_cirugia.editar.
 */
class TipoCirugiaController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', TipoCirugia::class);

        return view('tipos_cirugia.index', [
            'tipos' => TipoCirugia::withCount('plantilla')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', TipoCirugia::class);

        $datos = $this->validar($request);

        $tipo = TipoCirugia::create($datos + ['activo' => true]);

        Bitacora::registrar('TIPO_CIRUGIA_CREADO', $tipo, nuevo: $tipo->only(['codigo', 'nombre']));

        return redirect()->route('tipos_cirugia.edit', $tipo)->with('success', 'Tipo de cirugía creado. Agregue los equipos de la plantilla.');
    }

    public function edit(TipoCirugia $tipo)
    {
        Gate::authorize('view', $tipo);

        $tipo->load('plantilla');

        // Modelos de equipo disponibles para la plantilla (con número de copias).
        $referencias = Equipo::query()
            ->whereNotNull('codigo_referencia')
            ->where('codigo_referencia', '!=', '')
            ->select('codigo_referencia', DB::raw('COUNT(*) as copias'), DB::raw('MIN(nombre) as nombre'))
            ->groupBy('codigo_referencia')
            ->orderBy('codigo_referencia')
            ->get();

        return view('tipos_cirugia.edit', [
            'tipo' => $tipo,
            'referencias' => $referencias,
            'puedeEditar' => Gate::allows('update', $tipo),
        ]);
    }

    public function update(Request $request, TipoCirugia $tipo)
    {
        Gate::authorize('update', $tipo);

        $datos = $this->validar($request, $tipo) + ['activo' => $request->boolean('activo')];

        $tipo->fill($datos);
        $cambios = $tipo->getDirty();

        if ($cambios) {
            $anterior = array_intersect_key($tipo->getOriginal(), $cambios);
            $tipo->save();
            Bitacora::registrar('TIPO_CIRUGIA_ACTUALIZADO', $tipo, anterior: $anterior, nuevo: $cambios);
        }

        return back()->with('success', 'Tipo de cirugía actualizado.');
    }

    public function agregarEquipo(Request $request, TipoCirugia $tipo)
    {
        Gate::authorize('update', $tipo);

        $datos = $request->validate([
            'codigo_referencia' => [
                'required', 'string', 'max:100',
                Rule::exists('equipos', 'codigo_referencia'),
                Rule::unique('tipo_cirugia_equipos')->where('tipo_cirugia_id', $tipo->id),
            ],
            'obligatorio' => ['nullable', 'boolean'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ], [
            'codigo_referencia.unique' => 'Ese equipo ya está en la plantilla.',
        ]);

        $linea = $tipo->plantilla()->create([
            'codigo_referencia' => $datos['codigo_referencia'],
            'obligatorio' => (bool) ($datos['obligatorio'] ?? false),
            'observacion' => $datos['observacion'] ?? null,
            'orden' => (int) $tipo->plantilla()->max('orden') + 1,
        ]);

        Bitacora::registrar('PLANTILLA_EQUIPO_AGREGADO', $tipo, nuevo: $linea->only(['codigo_referencia', 'obligatorio', 'orden']));

        return back()->with('success', 'Equipo agregado a la plantilla.');
    }

    public function actualizarEquipo(Request $request, TipoCirugia $tipo, TipoCirugiaEquipo $linea)
    {
        Gate::authorize('update', $tipo);
        abort_unless($linea->tipo_cirugia_id === $tipo->id, 404);

        $datos = $request->validate([
            'orden' => ['required', 'integer', 'min:0', 'max:999'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ]) + ['obligatorio' => $request->boolean('obligatorio')];

        $linea->fill($datos);
        $cambios = $linea->getDirty();

        if ($cambios) {
            $anterior = array_intersect_key($linea->getOriginal(), $cambios);
            $linea->save();
            Bitacora::registrar('PLANTILLA_EQUIPO_ACTUALIZADO', $tipo, anterior: $anterior + ['codigo_referencia' => $linea->codigo_referencia], nuevo: $cambios);
        }

        return back()->with('success', 'Plantilla actualizada.');
    }

    public function quitarEquipo(TipoCirugia $tipo, TipoCirugiaEquipo $linea)
    {
        Gate::authorize('update', $tipo);
        abort_unless($linea->tipo_cirugia_id === $tipo->id, 404);

        $anterior = $linea->only(['codigo_referencia', 'obligatorio', 'orden']);
        $linea->delete();

        Bitacora::registrar('PLANTILLA_EQUIPO_RETIRADO', $tipo, anterior: $anterior);

        return back()->with('success', 'Equipo retirado de la plantilla.');
    }

    private function validar(Request $request, ?TipoCirugia $tipo = null): array
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:30', Rule::unique('tipos_cirugia', 'codigo')->ignore($tipo)],
            'nombre' => ['required', 'string', 'max:150'],
            'region_anatomica' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ]);

        $datos['codigo'] = strtoupper(trim($datos['codigo']));

        return $datos;
    }
}
