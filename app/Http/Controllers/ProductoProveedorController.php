<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoMaestro;
use App\Models\ProductoProveedor;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Exports\ProductosProveedoresExport;
use App\Imports\ProductosProveedoresImport;
use Maatwebsite\Excel\Facades\Excel;

class ProductoProveedorController extends Controller
{
    /**
     * Listado de relaciones producto maestro / proveedor / producto.
     *
     */

    public function index(Request $request)
    {
        $busqueda = trim((string) $request->input('q', ''));

        /*
         * Dividir la búsqueda en palabras.
         * Ejemplo: "CORTICAL 3.5" -> ["CORTICAL", "3.5"]
         *
         * Se limita a 6 términos para evitar consultas excesivamente
         * grandes si se pega accidentalmente un texto muy largo.
         */
        $terminos = preg_split(
            '/\s+/',
            $busqueda,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        $terminos = array_slice($terminos, 0, 6);

        $productosProveedores = ProductoProveedor::query()
            ->with([
                'productoMaestro',
                'proveedor',
                'producto',
            ])

            ->when(
                !empty($terminos),
                function ($query) use ($terminos) {

                    foreach ($terminos as $termino) {

                        $like = '%' . $termino . '%';

                        $query->where(function ($q) use ($like) {

                            $q->where('codigo_producto', 'like', $like)
                                ->orWhere('registro_sanitario', 'like', $like)

                                ->orWhereHas(
                                    'productoMaestro',
                                    function ($pm) use ($like) {
                                        $pm->where(
                                            'codigo_maestro',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'descripcion',
                                            'like',
                                            $like
                                        );
                                    }
                                )

                                ->orWhereHas(
                                    'proveedor',
                                    function ($proveedor) use ($like) {
                                        $proveedor->where(
                                            'codigo',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'nombre',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'sigla',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'ruc',
                                            'like',
                                            $like
                                        );
                                    }
                                )

                                ->orWhereHas(
                                    'producto',
                                    function ($producto) use ($like) {
                                        $producto->where(
                                            'codigo',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'codigojbg',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'nombre',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'descripcion',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'codigo_barra',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'marca',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'categoria',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'familia',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'subfamilia',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'proced_principal',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'region_anatomica_principal',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'tipo_cirugia',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'tipo',
                                            'like',
                                            $like
                                        )
                                        ->orWhere(
                                            'tipo_producto',
                                            'like',
                                            $like
                                        );
                                    }
                                );
                        });
                    }
                }
            )

            /*
             * Priorizar coincidencias exactas y por inicio
             * en los campos propios de la relación.
             */
            ->when(
                $busqueda !== '',
                function ($query) use ($busqueda) {

                    $query->orderByRaw(
                        'CASE
                            WHEN codigo_producto = ? THEN 0
                            WHEN codigo_producto LIKE ? THEN 1
                            WHEN registro_sanitario = ? THEN 2
                            ELSE 3
                         END',
                        [
                            $busqueda,
                            $busqueda . '%',
                            $busqueda,
                        ]
                    );
                }
            )

            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view(
            'productos_proveedores.index',
            compact(
                'productosProveedores',
                'busqueda'
            )
        );
    }

    /**
     * Crear una relación producto maestro / proveedor / producto.
     */
    public function store(Request $request)
    {
        $datos = $request->validate(
            [
                'producto_maestro_id' => [
                    'required',
                    'integer',
                    'exists:productos_maestros,id',
                ],

                'proveedor_id' => [
                    'required',
                    'integer',
                    'exists:proveedores,id',
                ],

                'producto_id' => [
                    'required',
                    'integer',
                    'exists:productos,id',
                ],

                'codigo_producto' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'registro_sanitario' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'activo' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'producto_maestro_id.required' =>
                    'El producto maestro es obligatorio.',

                'producto_maestro_id.exists' =>
                    'El producto maestro seleccionado no existe.',

                'proveedor_id.required' =>
                    'El proveedor es obligatorio.',

                'proveedor_id.exists' =>
                    'El proveedor seleccionado no existe.',

                'producto_id.required' =>
                    'El producto de Contífico es obligatorio.',

                'producto_id.exists' =>
                    'El producto seleccionado no existe.',

                'codigo_producto.max' =>
                    'El código del producto no puede superar los 100 caracteres.',

                'registro_sanitario.max' =>
                    'El registro sanitario no puede superar los 150 caracteres.',

                'activo.required' =>
                    'El estado es obligatorio.',

                'activo.boolean' =>
                    'El estado seleccionado no es válido.',
            ]
        );

        $existe = ProductoProveedor::query()
            ->where('producto_maestro_id', $datos['producto_maestro_id'])
            ->where('proveedor_id', $datos['proveedor_id'])
            ->where('producto_id', $datos['producto_id'])
            ->exists();

        if ($existe) {
            return response()->json([
                'ok' => false,
                'message' =>
                    'Esta combinación de producto maestro, proveedor y producto ya existe.',
            ], 422);
        }

        /*
         * Si no se proporciona codigo_producto,
         * utilizamos el código real de Contífico.
         */
        if (
            empty($datos['codigo_producto']) &&
            !empty($datos['producto_id'])
        ) {
            $producto = Producto::find($datos['producto_id']);

            if ($producto) {
                $datos['codigo_producto'] = $producto->codigo;
            }
        }

        $productoProveedor =
            ProductoProveedor::create($datos);

        $productoProveedor->load([
            'productoMaestro',
            'proveedor',
            'producto',
        ]);

        return response()->json([
            'ok' => true,
            'message' =>
                'Producto por proveedor creado correctamente.',
            'producto_proveedor' =>
                $productoProveedor,
        ]);
    }


    /**
     * Mostrar una relación.
     */
    public function show(ProductoProveedor $productoProveedor)
    {
        $productoProveedor->load([
            'productoMaestro',
            'proveedor',
            'producto',
        ]);

        return response()->json([
            'ok' => true,
            'producto_proveedor' =>
                $productoProveedor,
        ]);
    }


    /**
     * Actualizar una relación.
     */
    public function update(
        Request $request,
        ProductoProveedor $productoProveedor
    ) {
        $datos = $request->validate(
            [
                'producto_maestro_id' => [
                    'required',
                    'integer',
                    'exists:productos_maestros,id',
                ],

                'proveedor_id' => [
                    'required',
                    'integer',
                    'exists:proveedores,id',
                ],

                'producto_id' => [
                    'required',
                    'integer',
                    'exists:productos,id',
                ],

                'codigo_producto' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'registro_sanitario' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'activo' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'producto_maestro_id.required' =>
                    'El producto maestro es obligatorio.',

                'producto_maestro_id.exists' =>
                    'El producto maestro seleccionado no existe.',

                'proveedor_id.required' =>
                    'El proveedor es obligatorio.',

                'proveedor_id.exists' =>
                    'El proveedor seleccionado no existe.',

                'producto_id.required' =>
                    'El producto de Contífico es obligatorio.',

                'producto_id.exists' =>
                    'El producto seleccionado no existe.',

                'codigo_producto.max' =>
                    'El código del producto no puede superar los 100 caracteres.',

                'registro_sanitario.max' =>
                    'El registro sanitario no puede superar los 150 caracteres.',

                'activo.required' =>
                    'El estado es obligatorio.',

                'activo.boolean' =>
                    'El estado seleccionado no es válido.',
            ]
        );

        $existe = ProductoProveedor::query()
            ->where('producto_maestro_id', $datos['producto_maestro_id'])
            ->where('proveedor_id', $datos['proveedor_id'])
            ->where('producto_id', $datos['producto_id'])
            ->where('id', '!=', $productoProveedor->id)
            ->exists();

        if ($existe) {
            return response()->json([
                'ok' => false,
                'message' =>
                    'Esta combinación de producto maestro, proveedor y producto ya existe.',
            ], 422);
        }

        if (
            empty($datos['codigo_producto']) &&
            !empty($datos['producto_id'])
        ) {
            $producto = Producto::find($datos['producto_id']);

            if ($producto) {
                $datos['codigo_producto'] = $producto->codigo;
            }
        }

        $productoProveedor->update($datos);

        $productoProveedor->load([
            'productoMaestro',
            'proveedor',
            'producto',
        ]);

        return response()->json([
            'ok' => true,
            'message' =>
                'Producto por proveedor actualizado correctamente.',
            'producto_proveedor' =>
                $productoProveedor,
        ]);
    }


    /**
     * Eliminar una relación.
     */
    public function destroy(
        ProductoProveedor $productoProveedor
    ) {
        $productoProveedor->delete();

        return response()->json([
            'ok' => true,
            'message' =>
                'Producto por proveedor eliminado correctamente.',
        ]);
    }


/**
 * Exportar productos por proveedor.
 */
public function exportar()
{
    return Excel::download(
        new ProductosProveedoresExport,
        'PRODUCTOS_PROVEEDORES.xlsx'
    );
}


/**
 * Importar productos por proveedor.
 */
public function importar(Request $request)
{
    $request->validate(
        [
            'archivo' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ],
        [
            'archivo.required' =>
                'Debe seleccionar un archivo Excel.',

            'archivo.file' =>
                'El archivo seleccionado no es válido.',

            'archivo.mimes' =>
                'El archivo debe ser Excel (.xlsx, .xls) o CSV.',

            'archivo.max' =>
                'El archivo no puede superar los 10 MB.',
        ]
    );

    try {

        Excel::import(
            new ProductosProveedoresImport,
            $request->file('archivo')
        );

        return response()->json([
            'ok' => true,
            'message' =>
                'Productos por proveedor importados correctamente.',
        ]);

    } catch (\Throwable $e) {

        return response()->json([
            'ok' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}



    /**
     * Buscar productos de Contífico para el selector AJAX.
     */
    public function buscarProductos(Request $request)
    {
        $busqueda = trim(
            $request->input('q', '')
        );

        if ($busqueda === '') {
            return response()->json([
                'ok' => true,
                'productos' => [],
            ]);
        }

        $productos = Producto::query()
        ->select([
            'id',
            'codigo',
            'codigojbg',
            'nombre',
            'descripcion',
            'marca',
            'categoria',
            'familia',
            'subfamilia',
            'tipo',
            'tipo_producto',
            'codigo_barra',
            'estado'
        ])

            
            ->where(function ($query) use ($busqueda) {

                $palabras = preg_split(
                    '/\s+/',
                    mb_strtolower($busqueda, 'UTF-8'),
                    -1,
                    PREG_SPLIT_NO_EMPTY
                );
            
                foreach ($palabras as $palabra) {
            
                    $query->where(function ($q) use ($palabra) {
            
                        $q->whereRaw(
                            'LOWER(codigo) LIKE ?',
                            ['%' . $palabra . '%']
                        )
                        ->orWhereRaw(
                            'LOWER(codigojbg) LIKE ?',
                            ['%' . $palabra . '%']
                        )
                        ->orWhereRaw(
                            'LOWER(nombre) LIKE ?',
                            ['%' . $palabra . '%']
                        )
                        ->orWhereRaw(
                            'LOWER(descripcion) LIKE ?',
                            ['%' . $palabra . '%']
                        )
                        ->orWhereRaw(
                            'LOWER(codigo_barra) LIKE ?',
                            ['%' . $palabra . '%']
                        );
            
                    });
            
                }
            
            })

            ->orderBy('codigo')
            ->limit(30)
            ->get();

        return response()->json([
            'ok' => true,
            'productos' => $productos,
        ]);
    }
}