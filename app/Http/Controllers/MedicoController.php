<?php

namespace App\Http\Controllers;

use App\Models\Medico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MedicoController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Medico::class);

        $medicos = Medico::query()
            ->when($request->filled('buscar'), fn ($q) => $q->where(fn ($q) => $q
                ->where('nombres', 'like', '%' . $request->string('buscar') . '%')
                ->orWhere('identificacion', 'like', '%' . $request->string('buscar') . '%')))
            ->orderBy('nombres')
            ->paginate(30)
            ->withQueryString();

        return view('medicos.index', ['medicos' => $medicos]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Medico::class);

        $medico = Medico::create($this->validar($request) + ['activo' => true]);

        return $request->expectsJson()
            ? response()->json(['id' => $medico->id, 'nombres' => $medico->nombres], 201)
            : back()->with('success', 'Médico registrado.');
    }

    public function update(Request $request, Medico $medico)
    {
        Gate::authorize('update', $medico);

        $medico->update($this->validar($request, $medico) + ['activo' => $request->boolean('activo')]);

        return back()->with('success', 'Médico actualizado.');
    }

    private function validar(Request $request, ?Medico $medico = null): array
    {
        return $request->validate([
            'identificacion' => ['nullable', 'string', 'max:30', Rule::unique('medicos', 'identificacion')->ignore($medico)],
            'nombres' => ['required', 'string', 'max:200'],
            'especialidad' => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
        ]);
    }
}
