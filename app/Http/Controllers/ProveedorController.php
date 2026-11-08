<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Exports\ProveedoresExport;
use App\Imports\ProveedoresImport;
use Maatwebsite\Excel\Facades\Excel;

class ProveedorController extends Controller
{
    /**
     * Listado de proveedores.
     */
    public function index(Request $request)
    {
        $busqueda = trim($request->input('q', ''));

        $proveedores = Proveedor::query()
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($q) use ($busqueda) {
                    $q->where('codigo', 'like', '%' . $busqueda . '%')
                        ->orWhere('nombre', 'like', '%' . $busqueda . '%')
                        ->orWhere('ruc', 'like', '%' . $busqueda . '%')
                        ->orWhere('sigla', 'like', '%' . $busqueda . '%');
                });
            })
            ->orderBy('nombre')
            ->paginate(25)
            ->withQueryString();

        return view('proveedores.index', compact('proveedores', 'busqueda'));
    }

    /**
     * Crear proveedor.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:50',
                'unique:proveedores,codigo',
            ],
            'nombre' => [
                'required',
                'string',
                'max:150',
            ],
            'ruc' => [
                'nullable',
                'string',
                'max:20',
                'unique:proveedores,ruc',
            ],
            'sigla' => [
                'required',
                'string',
                'max:20',
                'unique:proveedores,sigla',
            ],
            'estado' => [
                'required',
                'boolean',
            ],
        ]);

        $proveedor = Proveedor::create($datos);

        return response()->json([
            'ok' => true,
            'message' => 'Proveedor creado correctamente.',
            'proveedor' => $proveedor,
        ]);
    }

    /**
     * Mostrar un proveedor.
     */
    public function show(Proveedor $proveedor)
    {
        return response()->json([
            'ok' => true,
            'proveedor' => $proveedor,
        ]);
    }

    /**
     * Actualizar proveedor.
     */
    public function update(Request $request, Proveedor $proveedor)
    {
        $datos = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('proveedores', 'codigo')->ignore($proveedor->id),
            ],
            'nombre' => [
                'required',
                'string',
                'max:150',
            ],
            'ruc' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('proveedores', 'ruc')->ignore($proveedor->id),
            ],
            'sigla' => [
                'required',
                'string',
                'max:20',
                Rule::unique('proveedores', 'sigla')->ignore($proveedor->id),
            ],
            'estado' => [
                'required',
                'boolean',
            ],
        ]);

        $proveedor->update($datos);

        return response()->json([
            'ok' => true,
            'message' => 'Proveedor actualizado correctamente.',
            'proveedor' => $proveedor->fresh(),
        ]);
    }

    /**
     * Eliminar proveedor.
     */
    public function destroy(Proveedor $proveedor)
    {
        $proveedor->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Proveedor eliminado correctamente.',
        ]);
    }

    /**
     * Exportar proveedores a Excel.
     */
    public function exportar()
    {
        return Excel::download(
            new ProveedoresExport(),
            'proveedores.xlsx'
        );
    }

    /**
     * Importar proveedores desde Excel.
     */
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
            new ProveedoresImport(),
            $request->file('archivo')
        );

        return response()->json([
            'ok' => true,
            'message' => 'Proveedores importados correctamente.',
        ]);
    }




}