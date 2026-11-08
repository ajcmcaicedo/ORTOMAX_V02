<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Regional;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BodegaController extends Controller
{
    public function index()
    {
        $bodegas = Bodega::with('regional')
            ->orderBy('codigo')
            ->get();

        return view('bodegas.index', compact('bodegas'));
    }

    public function create()
    {
        $regionales = Regional::where('activo', true)
            ->orderBy('codigo')
            ->get();

        return response()->json([
            'ok' => true,
            'regionales' => $regionales,
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'regional_id' => [
                'required',
                'integer',
                'exists:regionales,id',
            ],

            'codigo' => [
                'required',
                'string',
                'max:20',
                'unique:bodegas,codigo',
            ],

            'nombre' => [
                'required',
                'string',
                'max:150',
            ],

            'tipo_bodega' => [
                'nullable',
                'string',
                'max:50',
            ],

            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:50',
            ],

            'activo' => [
                'nullable',
                'boolean',
            ],
        ]);

        $datos['activo'] = $request->boolean('activo');

        $bodega = Bodega::create($datos);

        $bodega->load('regional');

        return response()->json([
            'ok' => true,
            'mensaje' => 'Bodega creada correctamente.',
            'bodega' => $bodega,
        ], 201);
    }

    public function edit(Bodega $bodega)
    {
        $regionales = Regional::where('activo', true)
            ->orderBy('codigo')
            ->get();

        $bodega->load('regional');

        return response()->json([
            'ok' => true,
            'bodega' => $bodega,
            'regionales' => $regionales,
        ]);
    }

    public function update(Request $request, Bodega $bodega)
    {
        $datos = $request->validate([
            'regional_id' => [
                'required',
                'integer',
                'exists:regionales,id',
            ],

            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('bodegas', 'codigo')
                    ->ignore($bodega->id),
            ],

            'nombre' => [
                'required',
                'string',
                'max:150',
            ],

            'tipo_bodega' => [
                'nullable',
                'string',
                'max:50',
            ],

            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:50',
            ],

            'activo' => [
                'nullable',
                'boolean',
            ],
        ]);

        $datos['activo'] = $request->boolean('activo');

        $bodega->update($datos);

        $bodega->load('regional');

        return response()->json([
            'ok' => true,
            'mensaje' => 'Bodega actualizada correctamente.',
            'bodega' => $bodega,
        ]);
    }

    public function destroy(Bodega $bodega)
    {
        $id = $bodega->id;
    
        if ($bodega->ubicaciones()->exists()) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se puede eliminar la bodega porque tiene ubicaciones asociadas.',
            ], 422);
        }
    
        if ($bodega->equipos()->exists()) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se puede eliminar la bodega porque tiene equipos asociados.',
            ], 422);
        }
    
        if ($bodega->activos()->exists()) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se puede eliminar la bodega porque tiene activos asociados.',
            ], 422);
        }
    
        $bodega->delete();
    
        return response()->json([
            'ok' => true,
            'mensaje' => 'Bodega eliminada correctamente.',
            'id' => $id,
        ]);
    }


}