<?php

namespace App\Http\Controllers;

use App\Models\Regional;
use Illuminate\Http\Request;

class RegionalController extends Controller
{
    /**
     * Mostrar listado de regionales.
     */
    public function index()
    {
        $regionales = Regional::orderBy('codigo')->get();

        return view('regionales.index', compact('regionales'));
    }

    /**
     * Mostrar formulario para crear.
     */
    public function create()
    {
        return view('regionales.create');
    }

    /**
     * Guardar nueva regional.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:20', 'unique:regionales,codigo'],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'activo' => ['nullable', 'boolean'],
        ]);
    
        $datos['activo'] = $request->boolean('activo');
    
        $regional = Regional::create($datos);
    
        if ($request->expectsJson()) {
    
            return response()->json([
                'ok' => true,
                'mensaje' => 'Regional creada correctamente.',
                'regional' => [
                    'id' => $regional->id,
                    'codigo' => $regional->codigo,
                    'nombre' => $regional->nombre,
                    'descripcion' => $regional->descripcion,
                    'activo' => $regional->activo,
                ],
            ], 201);
        }
    
        return redirect()
            ->route('regionales.index')
            ->with('success', 'Regional creada correctamente.');
    }
    
    /**
     * Mostrar una regional.
     */
    public function show(Regional $regionale)
    {
        return view('regionales.show', compact('regionale'));
    }

    /**
     * Mostrar formulario para editar.
     */
    public function edit(Regional $regionale)
    {
        return view('regionales.edit', compact('regionale'));
    }

    /**
     * Actualizar regional.
     */
    public function update(Request $request, Regional $regionale)
    {
        $datos = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:20',
                'unique:regionales,codigo,' . $regionale->id,
            ],
            'nombre' => [
                'required',
                'string',
                'max:150',
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:500',
            ],
            'activo' => [
                'nullable',
                'boolean',
            ],
        ]);

        $datos['activo'] = $request->boolean('activo');

        $regionale->update($datos);

        return redirect()
            ->route('regionales.index')
            ->with('success', 'Regional actualizada correctamente.');
    }

    /**
     * Eliminar regional.
     */
    public function destroy(Regional $regionale)
    {
        $id = $regionale->id;
    
        $regionale->delete();
    
        return response()->json([
            'ok' => true,
            'mensaje' => 'Regional eliminada correctamente.',
            'id' => $id,
        ]);
    }
}