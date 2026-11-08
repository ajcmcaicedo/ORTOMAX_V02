<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Importaciones\ImportadorExcel;
use App\Services\Importaciones\Perfiles\LotesImportacion;
use App\Services\Importaciones\ResultadoImportacion;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use App\Jobs\ExportarLotesExcel;
use Illuminate\Support\Str;


class LoteController extends Controller
{
    private function rutaPendiente(string $token): string
    {
        return storage_path(
            'app/importaciones/pendientes/lotes/' . $token . '.json'
        );
    }

    public function importar()
    {
        return view('lotes.importar');
    }

    public function descargarPlantilla()
    {
        $ruta = storage_path('app/importaciones/Plantilla_Lotes.xlsx');

        if (!file_exists($ruta)) {
            abort(404, 'Plantilla de lotes no encontrada.');
        }

        return response()->download($ruta, 'PLANTILLA_LOTES.xlsx');
    }


    public function exportar()
    {
        set_time_limit(300);
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '1024M');
    
        $plantilla = storage_path(
            'app/importaciones/Plantilla_Lotes.xlsx'
        );
    
        if (!file_exists($plantilla)) {
            abort(404, 'No se encontro la plantilla de lotes.');
        }
    
        $rutaTemporal = null;
    
        try {
    
            // Cargar la misma plantilla utilizada para importar.
            $libro = IOFactory::load($plantilla);
            $hoja = $libro->getActiveSheet();
    
            // Conservar los encabezados y eliminar los seis ejemplos.
            $ultimaFila = $hoja->getHighestRow();
    
            if ($ultimaFila > 1) {
                $hoja->removeRow(2, $ultimaFila - 1);
            }
    
            // Consultar todos los lotes, sin filtrar por estado.
            $lotes = DB::table('lotes')
                ->join('productos', 'productos.id', '=', 'lotes.producto_id')
                ->select([
                    'productos.codigo as codigo_producto',
                    'lotes.numero_lote',
                    'lotes.fecha_fabricacion',
                    'lotes.fecha_vencimiento',
                    'lotes.registro_sanitario',
                    'lotes.estado',
                    'lotes.observaciones',
                ])
                ->orderBy('productos.codigo')
                ->orderBy('lotes.numero_lote')
                ->cursor();
    
            $fila = 2;
    
            foreach ($lotes as $lote) {
    
                // Codigo del producto como texto.
                $hoja->setCellValueExplicit(
                    'A' . $fila,
                    (string) $lote->codigo_producto,
                    DataType::TYPE_STRING
                );
    
                // Numero de lote como texto para conservar sus digitos.
                $hoja->setCellValueExplicit(
                    'B' . $fila,
                    (string) $lote->numero_lote,
                    DataType::TYPE_STRING
                );
    
                // Fechas en el mismo formato de la plantilla.
                $fabricacion = $lote->fecha_fabricacion
                    ? date('d/m/y', strtotime($lote->fecha_fabricacion))
                    : null;
    
                $vencimiento = $lote->fecha_vencimiento
                    ? date('d/m/y', strtotime($lote->fecha_vencimiento))
                    : null;
    
                $hoja->setCellValue('C' . $fila, $fabricacion);
                $hoja->setCellValue('D' . $fila, $vencimiento);
    
                // Registro sanitario.
                $hoja->setCellValueExplicit(
                    'E' . $fila,
                    (string) ($lote->registro_sanitario ?? ''),
                    DataType::TYPE_STRING
                );
    
                // Estado.
                $estado = $lote->estado
                    ? ucfirst(mb_strtolower($lote->estado))
                    : 'Activo';
    
                $hoja->setCellValueExplicit(
                    'F' . $fila,
                    $estado,
                    DataType::TYPE_STRING
                );
    
                // Observaciones.
                $hoja->setCellValue(
                    'G' . $fila,
                    $lote->observaciones
                );
    
                $fila++;
            }
    
            // Crear archivo temporal.
            $rutaTemporal = tempnam(
                storage_path('app'),
                'exportacion_lotes_'
            );
    
            if ($rutaTemporal === false) {
                throw new \RuntimeException(
                    'No se pudo crear el archivo temporal.'
                );
            }
    
            $escritor = new Xlsx($libro);
            $escritor->save($rutaTemporal);
    
            $libro->disconnectWorksheets();
            unset($libro);
    
            return response()
                ->download(
                    $rutaTemporal,
                    'LOTES_ORTOMAX.xlsx'
                )
                ->deleteFileAfterSend(true);
    
        } catch (\Throwable $e) {
    
            if ($rutaTemporal && file_exists($rutaTemporal)) {
                @unlink($rutaTemporal);
            }
    
            report($e);
    
            return back()->with(
                'error',
                'No se pudo exportar el archivo de lotes.'
            );
        }
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

        $importador = new ImportadorExcel();
        $perfil = new LotesImportacion();

        $resultado = $importador->analizar(
            $request->file('archivo')->getRealPath(),
            $perfil
        );

        if ($resultado->total() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo no contiene registros.',
            ], 422);
        }

        $token = bin2hex(random_bytes(16));

        $directorio = storage_path('app/importaciones/pendientes/lotes');

        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        try {
            $json = json_encode(
                $resultado->toArray(),
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_THROW_ON_ERROR
            );

            if (file_put_contents(
                $this->rutaPendiente($token),
                $json,
                LOCK_EX
            ) === false) {
                throw new \RuntimeException('No se pudo guardar la vista previa.');
            }

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo guardar la vista previa.',
            ], 500);
        }

        $preview = collect($resultado->filas())
            ->take(100)
            ->map(function ($registro) {
                $fila = $registro['fila'] ?? [];

                return [
                    'fila' => $fila['_numero_fila'] ?? '',
                    'codigo_producto' => $fila['codigo_producto'] ?? '',
                    'numero_lote' => $fila['numero_lote'] ?? '',
                    'fecha_fabricacion' => $fila['fecha_fabricacion'] ?? '',
                    'fecha_vencimiento' => $fila['fecha_vencimiento'] ?? '',
                    'registro_sanitario' => $fila['registro_sanitario'] ?? '',
                    'estado' => $registro['estado'] ?? '',
                    'cambios' => $registro['cambios'] ?? [],
                    'errores' => $registro['errores'] ?? [],
                ];
            })
            ->values();

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

    public function importConfirm(Request $request)
    {
        $request->validate([
            'token' => [
                'required',
                'string',
                'size:32',
                'regex:/^[a-f0-9]{32}$/',
            ],
        ]);

        $token = $request->input('token');
        $ruta = $this->rutaPendiente($token);

        if (!is_file($ruta)) {
            return response()->json([
                'success' => false,
                'message' => 'La vista previa no existe. Analice nuevamente el archivo.',
            ], 422);
        }

        if (filemtime($ruta) < time() - 7200) {
            @unlink($ruta);

            return response()->json([
                'success' => false,
                'message' => 'La vista previa ha vencido. Analice nuevamente el archivo.',
            ], 422);
        }

        try {
            $datos = json_decode(
                file_get_contents($ruta),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo leer la vista previa.',
            ], 422);
        }

        if (
            !is_array($datos) ||
            !isset($datos['filas']) ||
            !is_array($datos['filas'])
        ) {
            return response()->json([
                'success' => false,
                'message' => 'La vista previa no tiene un formato válido.',
            ], 422);
        }

        if (($datos['errores'] ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo contiene errores. Corríjalos antes de confirmar.',
            ], 422);
        }

        $perfil = new LotesImportacion();

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

                    $existente = $perfil->buscarExistente($fila);

                    if ($estado === ResultadoImportacion::NUEVO) {
                        if ($existente) {
                            throw new \RuntimeException(
                                'Un lote nuevo ya existe en la base de datos. Analice nuevamente el Excel.'
                            );
                        }

                        $perfil->crear($fila);
                        $creados++;

                        continue;
                    }

                    if ($estado === ResultadoImportacion::ACTUALIZAR) {
                        if (!$existente) {
                            throw new \RuntimeException(
                                'Un lote que debía actualizarse ya no existe. Analice nuevamente el Excel.'
                            );
                        }

                        // Recalcular los cambios con los valores actuales.
                        $cambios = $perfil->compararCambios(
                            $fila,
                            $existente
                        );

                        if (!empty($cambios)) {
                            $perfil->actualizar($existente, $fila);
                            $actualizados++;
                        } else {
                            $sinCambios++;
                        }

                        continue;
                    }

                    if ($estado === ResultadoImportacion::SIN_CAMBIOS) {
                        if (!$existente) {
                            throw new \RuntimeException(
                                'Un lote marcado sin cambios ya no existe. Analice nuevamente el Excel.'
                            );
                        }

                        // Si cambió después del análisis, no se modifica
                        // silenciosamente: se solicita una nueva vista previa.
                        $cambios = $perfil->compararCambios(
                            $fila,
                            $existente
                        );

                        if (!empty($cambios)) {
                            throw new \RuntimeException(
                                'Un lote cambió después del análisis. Analice nuevamente el Excel.'
                            );
                        }

                        $sinCambios++;
                        continue;
                    }

                    throw new \RuntimeException(
                        'Se encontró un estado de registro no reconocido.'
                    );
                }
            });

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'La importación fue cancelada. No se confirmaron cambios.',
                'error' => $e->getMessage(),
            ], 422);
        }

        @unlink($ruta);

        return response()->json([
            'success' => true,
            'message' => 'Importación de lotes finalizada correctamente.',
            'creados' => $creados,
            'actualizados' => $actualizados,
            'sin_cambios' => $sinCambios,
            'total_lotes' => Lote::count(),
        ]);
    }

    
public function iniciarExportacion()
{
    $token = (string) Str::uuid();

    DB::table('exportaciones_lotes')->insert([
        'token' => $token,
        'estado' => 'pendiente',
        'total_registros' => 0,
        'procesados' => 0,
        'porcentaje' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    try {
        ExportarLotesExcel::dispatch($token);

        return response()->json([
            'success' => true,
            'token' => $token,
            'message' => 'Exportacion iniciada correctamente.',
        ]);

    } catch (\Throwable $e) {

        DB::table('exportaciones_lotes')
            ->where('token', $token)
            ->update([
                'estado' => 'error',
                'error' => $e->getMessage(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => false,
            'message' => 'No se pudo iniciar la exportacion.',
        ], 500);
    }
}

public function progresoExportacion(string $token)
{
    $exportacion = DB::table('exportaciones_lotes')
        ->where('token', $token)
        ->first();

    if (!$exportacion) {
        return response()->json([
            'success' => false,
            'message' => 'Exportacion no encontrada.',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'estado' => $exportacion->estado,
        'total' => $exportacion->total_registros,
        'procesados' => $exportacion->procesados,
        'porcentaje' => $exportacion->porcentaje,
        'error' => $exportacion->error,
    ]);
}

public function descargarExportacion(string $token)
{
    $exportacion = DB::table('exportaciones_lotes')
        ->where('token', $token)
        ->first();

    if (
        !$exportacion ||
        $exportacion->estado !== 'completado' ||
        empty($exportacion->archivo)
    ) {
        abort(404, 'El archivo de exportacion no esta disponible.');
    }

    $ruta = storage_path(
        'app/' . $exportacion->archivo
    );

    if (!is_file($ruta)) {
        abort(404, 'No se encontro el archivo generado.');
    }

    return response()->download(
        $ruta,
        'LOTES_ORTOMAX.xlsx'
    )->deleteFileAfterSend(true);
}


}
