<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

class SincronizacionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PÁGINA
    |--------------------------------------------------------------------------
    */

    public function productos()
    {
        return view('sincronizaciones.productos');
    }

    public function bodegas()
    {
        return view('sincronizaciones.bodegas');
    }

    public function ejecutarBodegas()
    {
        $archivoEstado = storage_path('app/sincronizacion_bodegas.json');
        $archivoSalida = storage_path('logs/sincronizacion_bodegas_output.log');
        $archivoError = storage_path('logs/sincronizacion_bodegas_error.log');

        /*
        |--------------------------------------------------------------------------
        | Verificar si ya existe una sincronización en ejecución
        |--------------------------------------------------------------------------
        */

        if (File::exists($archivoEstado)) {

            $estado = json_decode(
                File::get($archivoEstado),
                true
            );

            if (
                is_array($estado) &&
                ($estado['estado'] ?? null) === 'EN_EJECUCION'
            ) {

                return response()->json([
                    'estado' => 'EN_EJECUCION',
                    'mensaje' => 'La sincronización de bodegas ya está en ejecución.'
                ], 409);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Limpiar archivos anteriores
        |--------------------------------------------------------------------------
        */

        File::put($archivoSalida, '');
        File::put($archivoError, '');


        /*
        |--------------------------------------------------------------------------
        | Preparar comando
        |--------------------------------------------------------------------------
        */

        $php = PHP_BINARY;

        $artisan = base_path('artisan');


        $comando = sprintf(
            'start "" /B "%s" "%s" contifico:sync-bodegas 1>"%s" 2>"%s"',
            $php,
            $artisan,
            $archivoSalida,
            $archivoError
        );


        /*
        |--------------------------------------------------------------------------
        | Ejecutar proceso en segundo plano
        |--------------------------------------------------------------------------
        */

        $proceso = popen($comando, 'r');


        if ($proceso === false) {

            File::put(
                $archivoEstado,
                json_encode([
                    'estado' => 'ERROR',
                    'mensaje' => 'No fue posible iniciar el proceso de sincronización.'
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            return response()->json([
                'estado' => 'ERROR',
                'mensaje' => 'No fue posible iniciar el proceso de sincronización.'
            ], 500);
        }


        pclose($proceso);


        /*
        |--------------------------------------------------------------------------
        | Registrar estado inicial
        |--------------------------------------------------------------------------
        */

        File::put(
            $archivoEstado,
            json_encode([
                'estado' => 'EN_EJECUCION',
                'pid' => null,
                'archivo_salida' => $archivoSalida,
                'archivo_error' => $archivoError,
                'inicio' => now()->toDateTimeString()
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );


        return response()->json([
            'estado' => 'EN_EJECUCION',
            'mensaje' => 'Sincronización de bodegas iniciada correctamente.'
        ]);
    }

    public function estadoBodegas()
    {
        $archivoEstado = storage_path('app/sincronizacion_bodegas.json');
        $archivoSalida = storage_path('logs/sincronizacion_bodegas_output.log');
        $archivoError = storage_path('logs/sincronizacion_bodegas_error.log');

        /*
        |--------------------------------------------------------------------------
        | Si no existe estado
        |--------------------------------------------------------------------------
        */

        if (!File::exists($archivoEstado)) {

            return response()->json([
                'estado' => 'LISTO',
                'mensaje' => 'Sistema listo para sincronizar las bodegas.'
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Leer estado
        |--------------------------------------------------------------------------
        */

        $estado = json_decode(
            File::get($archivoEstado),
            true
        );

        if (!is_array($estado)) {

            return response()->json([
                'estado' => 'LISTO',
                'mensaje' => 'Sistema listo para sincronizar las bodegas.'
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Revisar errores
        |--------------------------------------------------------------------------
        */

        if (
            File::exists($archivoError) &&
            trim(File::get($archivoError)) !== ''
        ) {

            $error = trim(File::get($archivoError));

            File::put(
                $archivoEstado,
                json_encode([
                    'estado' => 'ERROR',
                    'mensaje' => $error
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            return response()->json([
                'estado' => 'ERROR',
                'mensaje' => $error
            ], 500);
        }


        /*
        |--------------------------------------------------------------------------
        | Leer salida del comando
        |--------------------------------------------------------------------------
        */

        $salida = '';

        if (File::exists($archivoSalida)) {

            $salida = File::get($archivoSalida);
        }


        /*
        |--------------------------------------------------------------------------
        | Detectar sincronización finalizada
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($salida, 'SINCRONIZACIÓN FINALIZADA') ||
            str_contains($salida, 'SINCRONIZACI')
        ) {

            preg_match(
                '/Bodegas procesadas:\s*(\d+)/',
                $salida,
                $procesadas
            );

            preg_match(
                '/Bodegas nuevas:\s*(\d+)/',
                $salida,
                $nuevas
            );

            preg_match(
                '/Bodegas actualizadas:\s*(\d+)/',
                $salida,
                $actualizadas
            );


            $datos = [
                'estado' => 'FINALIZADA',
                'bodegas_procesadas' => isset($procesadas[1])
                    ? (int) $procesadas[1]
                    : 0,
                'bodegas_nuevas' => isset($nuevas[1])
                    ? (int) $nuevas[1]
                    : 0,
                'bodegas_actualizadas' => isset($actualizadas[1])
                    ? (int) $actualizadas[1]
                    : 0,
            ];


            File::put(
                $archivoEstado,
                json_encode(
                    $datos,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                )
            );


            return response()->json($datos);
        }


        /*
        |--------------------------------------------------------------------------
        | Todavía ejecutándose
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'estado' => 'EN_EJECUCION',
            'mensaje' => 'La sincronización de bodegas continúa ejecutándose.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | EJECUTAR SINCRONIZACIÓN
    |--------------------------------------------------------------------------
    */

    public function ejecutarProductos(): JsonResponse
    {
        $estadoPath = storage_path('app/sincronizacion_productos.json');

        $logSalida = storage_path('logs/sincronizacion_productos_output.log');
        $logError = storage_path('logs/sincronizacion_productos_error.log');

        /*
        |--------------------------------------------------------------------------
        | COMPROBAR SI YA HAY UNA SINCRONIZACIÓN
        |--------------------------------------------------------------------------
        */

        if (File::exists($estadoPath)) {

            $estado = json_decode(File::get($estadoPath), true);

            if (($estado['estado'] ?? null) === 'EN_EJECUCION') {

                $pid = $estado['pid'] ?? null;

                if ($pid && $this->procesoExiste((int) $pid)) {

                    return response()->json([
                        'ok' => false,
                        'estado' => 'EN_EJECUCION',
                        'mensaje' => 'Ya existe una sincronización de productos en ejecución.',
                    ], 409);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | LIMPIAR LOGS
        |--------------------------------------------------------------------------
        */

        File::put($logSalida, '');
        File::put($logError, '');

        /*
        |--------------------------------------------------------------------------
        | RUTAS
        |--------------------------------------------------------------------------
        */

        $php = PHP_BINARY;
        $artisan = base_path('artisan');

        /*
        |--------------------------------------------------------------------------
        | COMANDO WINDOWS
        |--------------------------------------------------------------------------
        */

        $comando = sprintf(
            'start "" /B "%s" "%s" contifico:sync-productos 1>"%s" 2>"%s"',
            $php,
            $artisan,
            $logSalida,
            $logError
        );

        /*
        |--------------------------------------------------------------------------
        | INICIAR SINCRONIZACIÓN
        |--------------------------------------------------------------------------
        */

        try {

            $handle = popen($comando, 'r');

            if ($handle === false) {

                return response()->json([
                    'ok' => false,
                    'estado' => 'ERROR',
                    'mensaje' => 'PHP no pudo ejecutar el comando de Windows.',
                ], 500);
            }

            pclose($handle);

        } catch (\Throwable $e) {

            return response()->json([
                'ok' => false,
                'estado' => 'ERROR',
                'mensaje' => 'Se produjo un error al iniciar la sincronización.',
                'detalle' => $e->getMessage(),
            ], 500);
        }

        /*
        |--------------------------------------------------------------------------
        | ESPERAR PARA CONFIRMAR QUE ARRANCÓ
        |--------------------------------------------------------------------------
        */

        usleep(1000000);

        /*
        |--------------------------------------------------------------------------
        | LEER LOGS
        |--------------------------------------------------------------------------
        */

        $salida = '';

        if (File::exists($logSalida)) {
            $salida = File::get($logSalida);
        }

        $error = '';

        if (File::exists($logError)) {
            $error = trim(File::get($logError));
        }

        /*
        |--------------------------------------------------------------------------
        | ERROR REAL
        |--------------------------------------------------------------------------
        */

        if ($error !== '') {

            return response()->json([
                'ok' => false,
                'estado' => 'ERROR',
                'mensaje' => 'La sincronización terminó con un error.',
                'error' => $error,
            ], 500);
        }

        /*
        |--------------------------------------------------------------------------
        | CONFIRMAR QUE EL PROCESO ARRANCÓ
        |
        | No dependemos del PID.
        |--------------------------------------------------------------------------
        */

        if ($salida !== '') {

            File::put(
                $estadoPath,
                json_encode([
                    'estado' => 'EN_EJECUCION',
                    'pid' => null,
                    'inicio' => now()->format('Y-m-d H:i:s'),
                    'log_salida' => $logSalida,
                    'log_error' => $logError,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            return response()->json([
                'ok' => true,
                'estado' => 'EN_EJECUCION',
                'mensaje' => 'La sincronización de productos ha sido iniciada.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | NO HUBO NINGUNA SALIDA
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'ok' => false,
            'estado' => 'ERROR',
            'mensaje' => 'Windows recibió la orden, pero la sincronización no produjo ninguna salida.',
        ], 500);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSCAR PROCESO DE SINCRONIZACIÓN
    |--------------------------------------------------------------------------
    */

    public function estadoProductos(): JsonResponse
    {
        $estadoPath = storage_path('app/sincronizacion_productos.json');

        if (!File::exists($estadoPath)) {

            return response()->json([
                'ok' => true,
                'estado' => 'LISTO',
                'mensaje' => 'No hay ninguna sincronización en ejecución.',
            ]);
        }

        $estado = json_decode(File::get($estadoPath), true);

        if (!$estado) {

            return response()->json([
                'ok' => false,
                'estado' => 'ERROR',
                'mensaje' => 'No fue posible leer el estado de la sincronización.',
            ], 500);
        }

        $logSalida = $estado['log_salida'] ?? null;
        $logError = $estado['log_error'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | LEER SALIDA
        |--------------------------------------------------------------------------
        */

        $salida = '';

        if ($logSalida && File::exists($logSalida)) {
            $salida = File::get($logSalida);
        }

        /*
        |--------------------------------------------------------------------------
        | LEER ERROR
        |--------------------------------------------------------------------------
        */

        $error = '';

        if ($logError && File::exists($logError)) {
            $error = trim(File::get($logError));
        }

        /*
        |--------------------------------------------------------------------------
        | ERROR
        |--------------------------------------------------------------------------
        */

        if ($error !== '') {

            $estado['estado'] = 'ERROR';
            $estado['fin'] = now()->format('Y-m-d H:i:s');

            File::put(
                $estadoPath,
                json_encode(
                    $estado,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                )
            );

            return response()->json([
                'ok' => false,
                'estado' => 'ERROR',
                'mensaje' => 'La sincronización terminó con un error.',
                'error' => $error,
            ], 500);
        }

        /*
        |--------------------------------------------------------------------------
        | DETECTAR FINALIZACIÓN
        |--------------------------------------------------------------------------
        */

        $finalizada =
            str_contains($salida, 'SINCRONIZACIÓN FINALIZADA')
            ||
            str_contains($salida, 'SINCRONIZACIÃƒâ€œN FINALIZADA');

        if ($finalizada) {

            preg_match(
                '/Productos procesados:\s*(\d+)/',
                $salida,
                $productos
            );

            preg_match(
                '/Categor.{0,10}as cargadas:\s*(\d+)/u',
                $salida,
                $categorias
            );

            $estado['estado'] = 'FINALIZADA';
            $estado['fin'] = now()->format('Y-m-d H:i:s');

            $estado['productos'] =
                isset($productos[1])
                    ? (int) $productos[1]
                    : null;

            $estado['categorias'] =
                isset($categorias[1])
                    ? (int) $categorias[1]
                    : null;

            File::put(
                $estadoPath,
                json_encode(
                    $estado,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                )
            );

            return response()->json([
                'ok' => true,
                'estado' => 'FINALIZADA',
                'mensaje' => 'La sincronización terminó correctamente.',
                'productos' => $estado['productos'],
                'categorias' => $estado['categorias'],
                'inicio' => $estado['inicio'] ?? null,
                'fin' => $estado['fin'],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | TODAVÍA ESTÁ EJECUTÁNDOSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'ok' => true,
            'estado' => 'EN_EJECUCION',
            'mensaje' => 'Sincronización en curso...',
            'inicio' => $estado['inicio'] ?? null,
        ]);
    }
}