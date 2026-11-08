<?php

namespace App\Http\Controllers;

use App\Exports\ProductosMaestrosExport;
use App\Imports\ProductosMaestrosImport;
use App\Models\ProductoMaestro;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ProductoMaestroController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = trim($request->input('q', ''));

        $productosMaestros = ProductoMaestro::query()
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($q) use ($busqueda) {
                    $q->where('codigo_maestro', 'like', '%' . $busqueda . '%')
                        ->orWhere('descripcion', 'like', '%' . $busqueda . '%');
                });
            })
            ->orderBy('codigo_maestro')
            ->paginate(25)
            ->withQueryString();

        return view(
            'productos_maestros.index',
            compact('productosMaestros', 'busqueda')
        );
    }

    public function store(Request $request)
    {
        $datos = $request->validate(
            [
                'codigo_maestro' => [
                    'required',
                    'string',
                    'max:50',
                    'unique:productos_maestros,codigo_maestro',
                ],
                'descripcion' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'estado' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'codigo_maestro.required' => 'El código maestro es obligatorio.',
                'codigo_maestro.string' => 'El código maestro debe ser texto.',
                'codigo_maestro.max' => 'El código maestro no puede superar los 50 caracteres.',
                'codigo_maestro.unique' => 'El código maestro ya existe.',
        
                'descripcion.required' => 'La descripción es obligatoria.',
                'descripcion.string' => 'La descripción debe ser texto.',
                'descripcion.max' => 'La descripción no puede superar los 255 caracteres.',
        
                'estado.required' => 'El estado es obligatorio.',
                'estado.boolean' => 'El estado seleccionado no es válido.',
            ]
        );

        $productoMaestro = ProductoMaestro::create($datos);

        return response()->json([
            'ok' => true,
            'message' => 'Producto maestro creado correctamente.',
            'producto_maestro' => $productoMaestro,
        ]);
    }

    public function show(ProductoMaestro $productosMaestro)
    {
        return response()->json([
            'ok' => true,
            'producto_maestro' => $productosMaestro,
        ]);
    }

    public function update(Request $request, ProductoMaestro $productosMaestro)
    {
        $datos = $request->validate(
            [
                'codigo_maestro' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('productos_maestros', 'codigo_maestro')
                        ->ignore($productosMaestro->id),
                ],
                'descripcion' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'estado' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'codigo_maestro.required' => 'El código maestro es obligatorio.',
                'codigo_maestro.string' => 'El código maestro debe ser texto.',
                'codigo_maestro.max' => 'El código maestro no puede superar los 50 caracteres.',
                'codigo_maestro.unique' => 'El código maestro ya existe.',
        
                'descripcion.required' => 'La descripción es obligatoria.',
                'descripcion.string' => 'La descripción debe ser texto.',
                'descripcion.max' => 'La descripción no puede superar los 255 caracteres.',
        
                'estado.required' => 'El estado es obligatorio.',
                'estado.boolean' => 'El estado seleccionado no es válido.',
            ]
        );

        $productosMaestro->update($datos);

        return response()->json([
            'ok' => true,
            'message' => 'Producto maestro actualizado correctamente.',
            'producto_maestro' => $productosMaestro->fresh(),
        ]);
    }

    public function destroy(ProductoMaestro $productosMaestro)
    {
        $productosMaestro->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Producto maestro eliminado correctamente.',
        ]);
    }

    public function exportar()
    {
        return Excel::download(
            new ProductosMaestrosExport(),
            'productos_maestros.xlsx'
        );
    }


    public function importar(Request $request)
{
    $request->validate([
        'archivo' => [
            'required',
            'file',
            'mimes:xlsx,xls,csv',
            'max:5120',
        ],
    ]);

    Excel::import(
        new ProductosMaestrosImport(),
        $request->file('archivo')
    );

    return response()->json([
        'ok' => true,
        'message' => 'Productos maestros importados correctamente.',
    ]);
}

}