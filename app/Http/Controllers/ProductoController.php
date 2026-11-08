<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Importaciones\ImportadorExcel;
use App\Services\Importaciones\Perfiles\ProductosImportacion;
use App\Services\Importaciones\ResultadoImportacion;

class ProductoController extends Controller
{
    /**
     * Listado de productos.
     */
    public function index(Request $request)
    {
        $busqueda = trim($request->input('q', ''));

        $productos = Producto::query()
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($q) use ($busqueda) {
                    $q->where('codigo', 'like', '%' . $busqueda . '%')
                        ->orWhere('nombre', 'like', '%' . $busqueda . '%')
                        ->orWhere('codigo_barra', 'like', '%' . $busqueda . '%')
                        ->orWhere('codigojbg', 'like', '%' . $busqueda . '%');
                });
            })
            ->orderBy('codigo')
            ->paginate(25)
            ->withQueryString();

        return view('productos.index', compact(
            'productos',
            'busqueda'
        ));
    }

    /**
     * Búsqueda anterior.
     *
     * Se conserva temporalmente para no perder
     * la funcionalidad existente mientras migramos
     * al nuevo módulo.
     */
    public function buscar(Request $request)
    {
        $busqueda = trim($request->input('q', ''));

        $productos = collect();

        if ($busqueda !== '') {
            $productos = Producto::where('codigo', 'like', '%' . $busqueda . '%')
                ->orWhere('nombre', 'like', '%' . $busqueda . '%')
                ->orWhere('codigo_barra', 'like', '%' . $busqueda . '%')
                ->limit(50)
                ->get();
        }

        return view('productos.buscar', compact(
            'busqueda',
            'productos'
        ));
    }

    public function importar()
    {
        return view('productos.importar');
    }


    public function importPreview(Request $request)
    {
        $request->validate([
            'archivo' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240',
            ],
        ]);
    
        $archivo = $request->file('archivo');
    
        $importador = new ImportadorExcel();
        $perfil = new ProductosImportacion();
    
        $resultado = $importador->analizar(
            $archivo->getRealPath(),
            $perfil
        );
    
        if ($resultado->total() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo no contiene registros para importar.',
            ], 422);
        }
    
        $token = bin2hex(random_bytes(16));
    
        session([
            'importacion_productos_' . $token => $resultado->toArray(),
        ]);
    
        $preview = collect($resultado->filas())->map(function ($registro) {

            $fila = $registro['fila'] ?? [];
        
            return [
                'fila' => $fila['_numero_fila'] ?? '',
                'codigo' => $fila['codigo'] ?? '',
                'nombre' => $fila['nombre'] ?? '',
                'descripcion' => $fila['descripcion'] ?? '',
                'estado' => $registro['estado'] ?? '',
                'existente' => !empty($registro['fila']),
                'cambios' => $registro['cambios'] ?? [],
                'errores' => $registro['errores'] ?? [],
            ];
        
        })->values();
    
        return response()->json([
            'success' => true,
            'token' => $token,
        
            'total' => $resultado->total(),
            'nuevos' => $resultado->totalNuevos(),
            'actualizar' => $resultado->totalActualizar(),
            'sin_cambios' => $resultado->totalSinCambios(),
            'errores' => $resultado->totalErrores(),
        
            'preview' => $preview,
        ]);
    }


    public function descargarPlantilla()
    {
        $ruta = storage_path(
            'app/plantillas/PLANTILLA_SOLICITUD_RAPIDA_PRODUCTOS.xlsx'
        );
    
        abort_unless(
            file_exists($ruta),
            404,
            'La plantilla de productos no existe.'
        );
    
        return response()->download(
            $ruta,
            'PLANTILLA_SOLICITUD_RAPIDA_PRODUCTOS.xlsx',
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    public function importConfirm(Request $request)
    {
        $request->validate([
            'token' => [
                'required',
                'string',
                'size:32',
            ],
        ]);
    
        $token = $request->input('token');
    
        $claveSesion = 'importacion_productos_' . $token;
    
        $datos = session($claveSesion);
    
        if (!$datos) {
            return response()->json([
                'success' => false,
                'message' => 'La vista previa de importación ya no está disponible. Genere una nueva vista previa.',
            ], 422);
        }
    
        if (($datos['total'] ?? 0) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No existen registros para importar.',
            ], 422);
        }
    
        if (($datos['errores'] ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'La importación contiene errores. Corrija el archivo antes de confirmar.',
            ], 422);
        }
    
        $perfil = new ProductosImportacion();
    
        $creados = 0;
        $actualizados = 0;
        $sinCambios = 0;
    
        try {
    
            DB::transaction(function () use (
                $datos,
                $perfil,
                &$creados,
                &$actualizados,
                &$sinCambios
            ) {
    
                foreach ($datos['filas'] as $registro) {
    
                    $estado = $registro['estado'] ?? null;
                    $fila = $registro['fila'] ?? [];
    
                    if ($estado === ResultadoImportacion::SIN_CAMBIOS) {
                        $sinCambios++;
                        continue;
                    }
    
                    if ($estado === ResultadoImportacion::ERROR) {
                        throw new \RuntimeException(
                            'Se encontró un registro con errores durante la confirmación.'
                        );
                    }
    
                    /*
                     * NUEVO
                     */
                    if ($estado === ResultadoImportacion::NUEVO) {
    
                        $existente = $perfil->buscarExistente($fila);
    
                        if ($existente) {
                            throw new \RuntimeException(
                                'El producto con código "' .
                                ($fila['codigo'] ?? '') .
                                '" ya existe. La vista previa quedó desactualizada.'
                            );
                        }
    
                        $perfil->crear($fila);
    
                        $creados++;
    
                        continue;
                    }
    
                    /*
                     * ACTUALIZAR
                     */
                    if ($estado === ResultadoImportacion::ACTUALIZAR) {
    
                        $existente = $perfil->buscarExistente($fila);
    
                        if (!$existente) {
                            throw new \RuntimeException(
                                'El producto con código "' .
                                ($fila['codigo'] ?? '') .
                                '" ya no existe para actualizar. Genere una nueva vista previa.'
                            );
                        }
    
                        $cambios = $perfil->compararCambios(
                            $fila,
                            $existente
                        );
    
                        if (empty($cambios)) {
                            $sinCambios++;
                            continue;
                        }
    
                        $perfil->actualizar(
                            $existente,
                            $fila
                        );
    
                        $actualizados++;
    
                        continue;
                    }
    
                    throw new \RuntimeException(
                        'Estado de importación desconocido: ' . $estado
                    );
                }
            });
    
        } catch (\Throwable $e) {
    
            return response()->json([
                'success' => false,
                'message' => 'La importación fue cancelada. No se realizaron cambios.',
                'error' => $e->getMessage(),
            ], 422);
        }
    
        session()->forget($claveSesion);
    
        return response()->json([
            'success' => true,
            'message' => 'Importación de productos finalizada correctamente.',
            'creados' => $creados,
            'actualizados' => $actualizados,
            'sin_cambios' => $sinCambios,
        ]);
    }

}