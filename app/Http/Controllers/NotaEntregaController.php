<?php

namespace App\Http\Controllers;

use App\Enums\EstadoNotaEntrega;
use App\Models\Bodega;
use App\Models\Institucion;
use App\Models\Medico;
use App\Models\NotaEntrega;
use App\Models\Regional;
use App\Models\TipoCirugia;
use App\Models\User;
use App\Services\NotasEntrega\ServicioBorradorNota;
use App\Services\NotasEntrega\TransicionEstadoNota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Notas de Entrega: listado, borrador (cabecera + paciente) y
 * transiciones previas al despacho.
 */
class NotaEntregaController extends Controller
{
    public function __construct(
        private ServicioBorradorNota $borrador,
        private TransicionEstadoNota $transicion,
    ) {
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', NotaEntrega::class);

        $usuario = $request->user();

        $notas = NotaEntrega::query()
            ->with(['institucion', 'tipoCirugia', 'instrumentador'])
            ->unless($usuario->tienePermiso('notas.ver_todas'), function ($q) use ($usuario) {
                $q->where(function ($q) use ($usuario) {
                    foreach (['instrumentador_id', 'preparador_id', 'creado_por_id', 'solicitado_por_id'] as $campo) {
                        $q->orWhere($campo, $usuario->id);
                    }
                });
            })
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $texto = '%' . $request->string('buscar') . '%';
                $q->where(function ($q) use ($texto) {
                    $q->where('numero', 'like', $texto)
                        ->orWhereHas('institucion', fn ($i) => $i->where('razon_social', 'like', $texto)
                            ->orWhere('nombre_comercial', 'like', $texto));
                });
            })
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha_cirugia_programada', '>=', $request->date('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha_cirugia_programada', '<=', $request->date('hasta')))
            ->orderByRaw('fecha_cirugia_programada IS NULL, fecha_cirugia_programada')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('notas_entrega.index', [
            'notas' => $notas,
            'estados' => EstadoNotaEntrega::cases(),
        ]);
    }

    public function create()
    {
        Gate::authorize('create', NotaEntrega::class);

        return view('notas_entrega.create', $this->catalogos() + [
            'nota' => new NotaEntrega(['fecha_cirugia_programada' => now()->addDay()->setTime(7, 0)]),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', NotaEntrega::class);

        $datos = $this->validarCabecera($request);
        $paciente = $request->user()->tienePermiso('pacientes.ver')
            ? $this->validarPaciente($request)
            : null;

        $nota = $this->borrador->crear($datos, $request->user(), $paciente);

        return redirect()
            ->route('notas_entrega.show', $nota)
            ->with('success', "Nota {$nota->numero} creada en borrador.");
    }

    public function show(Request $request, NotaEntrega $nota)
    {
        Gate::authorize('view', $nota);

        $usuario = $request->user();

        $nota->load(['institucion', 'regional', 'bodegaOrigen', 'tipoCirugia', 'medico', 'instrumentador', 'preparador', 'lineasEquipo.equipo']);

        $paciente = Gate::allows('verPaciente', $nota)
            ? $this->borrador->verPaciente($nota, $usuario)
            : null;

        return view('notas_entrega.show', $this->catalogos() + [
            'nota' => $nota,
            'paciente' => $paciente,
            'historial' => $nota->bitacora()->where('evento', '!=', 'PACIENTE_ACCESO')->latest('id')->limit(30)->get(),
            'usuarios' => User::whereIn('id', $nota->bitacora()->pluck('usuario_id')->filter()->unique())->pluck('name', 'id'),
        ]);
    }

    public function update(Request $request, NotaEntrega $nota)
    {
        Gate::authorize('update', $nota);

        $this->borrador->actualizar(
            $nota,
            $this->validarCabecera($request),
            $request->user(),
            $request->integer('version') ?: null,
        );

        return back()->with('success', 'Datos de la nota actualizados.');
    }

    public function actualizarPaciente(Request $request, NotaEntrega $nota)
    {
        Gate::authorize('editarPaciente', $nota);

        $this->borrador->guardarPaciente($nota, $this->validarPaciente($request), $request->user());

        return back()->with('success', 'Datos del paciente guardados.');
    }

    public function iniciarAlistamiento(Request $request, NotaEntrega $nota)
    {
        Gate::authorize('iniciarAlistamiento', $nota);

        $this->borrador->iniciarAlistamiento($nota, $request->user(), $request->integer('version') ?: null);

        return back()->with('success', 'Alistamiento iniciado.');
    }

    public function anular(Request $request, NotaEntrega $nota)
    {
        Gate::authorize('anular', $nota);

        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:500']]);

        /*
         * Notas con inventario despachado requieren reversa de movimientos
         * (Fase 6). Hasta entonces solo se anulan las no despachadas.
         */
        if ($nota->estado->inventarioDespachado()) {
            return back()->withErrors(['negocio' => 'La anulación de notas despachadas (con reversa de inventario) estará disponible en una fase posterior.']);
        }

        $this->transicion->ejecutar($nota, EstadoNotaEntrega::ANULADA, $request->user(), $datos['motivo']);

        return back()->with('success', 'Nota anulada.');
    }

    private function validarCabecera(Request $request): array
    {
        return $request->validate([
            'institucion_id' => ['required', 'integer', Rule::exists('instituciones', 'id')->where('activo', true)],
            'regional_id' => ['nullable', 'integer', 'exists:regionales,id'],
            'bodega_origen_id' => ['required', 'integer', Rule::exists('bodegas', 'id')->where('activo', true)],
            'tipo_cirugia_id' => ['nullable', 'integer', Rule::exists('tipos_cirugia', 'id')->where('activo', true)],
            'medico_id' => ['nullable', 'integer', Rule::exists('medicos', 'id')->where('activo', true)],
            'instrumentador_id' => ['nullable', 'integer', $this->reglaUsuarioConRol('INSTRUMENTADOR')],
            'preparador_id' => ['nullable', 'integer', $this->reglaUsuarioConRol('PREPARADOR')],
            'fecha_cirugia_programada' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'institucion_id' => 'institución',
            'bodega_origen_id' => 'bodega de despacho',
            'tipo_cirugia_id' => 'tipo de cirugía',
            'medico_id' => 'médico',
            'fecha_cirugia_programada' => 'fecha de cirugía',
        ]);
    }

    private function validarPaciente(Request $request): array
    {
        $datos = $request->validate([
            'paciente.identificacion' => ['nullable', 'string', 'max:30'],
            'paciente.nombres' => ['nullable', 'string', 'max:200'],
            'paciente.historia_clinica' => ['nullable', 'string', 'max:50'],
            'paciente.aseguradora' => ['nullable', 'string', 'max:150'],
            'paciente.confirmado' => ['nullable', 'boolean'],
        ]);

        $paciente = $datos['paciente'] ?? [];
        $paciente['confirmado'] = (bool) ($paciente['confirmado'] ?? false);

        return $paciente;
    }

    private function reglaUsuarioConRol(string $rol): \Closure
    {
        return function (string $atributo, mixed $valor, \Closure $fallar) use ($rol) {
            $ok = User::whereKey($valor)->whereHas('roles', fn ($q) => $q->where('nombre', $rol))->exists();

            if (!$ok) {
                $fallar("El usuario seleccionado no tiene el rol {$rol}.");
            }
        };
    }

    private function catalogos(): array
    {
        $usuariosConRol = fn (string $rol) => User::whereHas('roles', fn ($q) => $q->where('nombre', $rol))
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'instituciones' => Institucion::where('activo', true)->orderBy('razon_social')->get(['id', 'razon_social', 'nombre_comercial']),
            'regionales' => Regional::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'bodegas' => Bodega::where('activo', true)->orderBy('nombre')->get(['id', 'codigo', 'nombre']),
            'tiposCirugia' => TipoCirugia::where('activo', true)->orderBy('nombre')->get(['id', 'codigo', 'nombre']),
            'medicos' => Medico::where('activo', true)->orderBy('nombres')->get(['id', 'nombres', 'especialidad']),
            'instrumentadores' => $usuariosConRol('INSTRUMENTADOR'),
            'preparadores' => $usuariosConRol('PREPARADOR'),
        ];
    }
}
