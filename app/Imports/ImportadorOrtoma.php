<?php

namespace App\Imports;

use App\Models\Activo;
use App\Models\Bodega;
use App\Models\Equipo;
use App\Models\Instrumental;
use App\Models\Producto;
use App\Models\Regional;
use App\Models\Lote;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;


class ImportadorOrtoma
{
    protected string $equipoPiloto = 'P13NBC229';

    /**
     * Simula la importación sin modificar la base de datos.
     */

     private function convertirFechaExcel($valor): ?string
     {
         if ($valor === null || $valor === '') {
             return null;
         }
     
         if (is_numeric($valor)) {
     
             try {
                 return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject(
                     $valor
                 )->format('Y-m-d');
     
             } catch (\Throwable $e) {
                 return null;
             }
         }
     
         try {
             return \Carbon\Carbon::parse($valor)->format('Y-m-d');
     
         } catch (\Throwable $e) {
             return null;
         }
     }


     public function simular(ValidadorOrtomaImport $validador): array
     {
         if (!empty($validador->errores)) {
             throw new RuntimeException(
                 'No se puede simular porque existen errores de validación.'
             );
         }
     
         $codigoEquipo = strtoupper(
             trim((string) $this->equipoPiloto)
         );
     
         if (!isset($validador->equipos[$codigoEquipo])) {
             throw new RuntimeException(
                 "El equipo piloto {$codigoEquipo} no existe en el Excel."
             );
         }
     
         $equipoPilotoExcel = $validador->equipos[$codigoEquipo];
     
         $regionalPiloto = strtoupper(
             trim((string) $equipoPilotoExcel['regional'])
         );
     
         $bodegaPiloto = strtoupper(
             trim((string) $equipoPilotoExcel['bodega'])
         );


         
         /*
          * =========================================================
          * REGIONALES
          * =========================================================
          */
     
         $regionalesCrear = [];
         $regionalesExistentes = [];
     
         foreach ($validador->regionales as $regional) {

            if (
                strtoupper(
                    trim((string) $regional['codigo'])
                ) !== $regionalPiloto
            ) {
                continue;
            }
        
            $existente = Regional::where(
                'codigo',
                $regional['codigo']
            )->first();
        
            // resto del código...
        }
        
            if ($existente) {
                $regionalesExistentes[] = [
                    'codigo' => $regional['codigo'],
                    'nombre' => $regional['descripcion'],
                    'id' => $existente->id,
                ];
            } else {
                $regionalesCrear[] = [
                    'codigo' => $regional['codigo'],
                    'nombre' => $regional['descripcion'],
                ];
            }
        
     
         /*
          * =========================================================
          * BODEGAS
          * =========================================================
          */
     
         $bodegasCrear = [];
         $bodegasExistentes = [];
     
         foreach ($validador->bodegas as $bodega) {

            if (
                strtoupper(
                    trim((string) $bodega['codigo'])
                ) !== $bodegaPiloto
            ) {
                continue;
            }
        
            $existente = Bodega::where(
                'codigo',
                $bodega['codigo']
            )->first();
        
            if ($existente) {
                $bodegasExistentes[] = [
                    'codigo' => $bodega['codigo'],
                    'nombre' => $bodega['descripcion'],
                    'id' => $existente->id,
                ];
            } else {
                $bodegasCrear[] = [
                    'codigo' => $bodega['codigo'],
                    'nombre' => $bodega['descripcion'],
                    'regional' => $bodega['regional'],
                ];
            }
        }
     
         /*
          * =========================================================
          * EQUIPOS
          * =========================================================
          */
     
         $equiposCrear = [];
         $equiposExistentes = [];
     
         foreach ($validador->equipos as $equipo) {
     
             $existente = Equipo::where(
                 'codigo',
                 $equipo['codigo_equipo']
             )->first();
     
             if ($existente) {
                 $equiposExistentes[] = [
                     'codigo' => $equipo['codigo_equipo'],
                     'nombre' => $equipo['nombre'],
                     'id' => $existente->id,
                 ];
             } else {
                 $equiposCrear[] = [
                     'codigo' => $equipo['codigo_equipo'],
                     'nombre' => $equipo['nombre'],
                     'tipo_equipo' => $equipo['tipo_equipo'],
                     'regional' => $equipo['regional'],
                     'bodega' => $equipo['bodega'],
                     'estado' => $equipo['estado'],
                     'activo' => $equipo['activo'],
                 ];
             }
         }
     
         /*
          * =========================================================
          * PRODUCTOS E INSTRUMENTALES
          * =========================================================
          */
     
         $productosExistentes = [];
         $productosNoEncontrados = [];
         $instrumentalesCrear = [];
     
         foreach ($validador->productos as $producto) {
     
             $tipo = strtoupper(
                 trim($producto['tipo_producto'])
             );
     
             if ($tipo === 'INSTRUMENTAL') {
     
                 $instrumentalesCrear[] = [
                     'codigo' => $producto['codigo'],
                     'nombre' => $producto['nombre'],
                     'marca' => $producto['marca'] ?? null,
                     'familia' => $producto['familia'] ?? null,
                     'subfamilia' => $producto['subfamilia'] ?? null,
                     'estado' => $producto['estado'] ?? 'ACTIVO',
                 ];
     
                 continue;
             }
     
             $existente = Producto::where(
                 'codigo',
                 $producto['codigo']
             )->first();
     
             if ($existente) {
     
                 $productosExistentes[] = [
                     'codigo' => $producto['codigo'],
                     'nombre' => $producto['nombre'],
                     'id' => $existente->id,
                 ];
     
             } else {
     
                 $productosNoEncontrados[] = [
                     'codigo' => $producto['codigo'],
                     'nombre' => $producto['nombre'],
                     'tipo_producto' => $tipo,
                 ];
             }
         }
     
         /*
          * =========================================================
          * LOTES
          * =========================================================
          */
     
         $lotesCrear = [];
     
         foreach ($validador->lotes as $lote) {
     
             $lotesCrear[] = [
                 'codigo_producto' => $lote['codigo_producto'],
                 'numero_lote' => $lote['numero_lote'],
                 'fecha_fabricacion' => $lote['fecha_fabricacion'] ?? null,
                 'fecha_vencimiento' => $lote['fecha_vencimiento'] ?? null,
                 'registro_sanitario' => $lote['registro_sanitario'] ?? null,
                 'estado' => $lote['estado'] ?? 'ACTIVO',
             ];
         }
     
         /*
          * =========================================================
          * ACTIVOS
          * =========================================================
          */
     
         $activosCrear = [];
         $activosExistentes = [];
     
         foreach ($validador->activos as $activo) {
     
             $existente = Activo::where(
                 'codigo',
                 $activo['codigo_activo']
             )->first();
     
             if ($existente) {
     
                 $activosExistentes[] = [
                     'codigo_activo' => $activo['codigo_activo'],
                     'id' => $existente->id,
                 ];
     
             } else {
     
                 $activosCrear[] = [
                     'codigo_activo' => $activo['codigo_activo'],
                     'codigo_producto' => $activo['codigo_producto'] ?? null,
                     'nombre' => $activo['nombre'],
                     'tipo_activo' => $activo['tipo_activo'],
                     'numero_serial' => $activo['numero_serial'] ?? null,
                     'bodega' => $activo['bodega'] ?? null,
                     'equipo' => $activo['equipo'] ?? null,
                     'estado' => $activo['estado'],
                     'activo' => $activo['activo'],
                 ];
             }
         }
     
         /*
          * =========================================================
          * CONTENIDO DEL EQUIPO
          * =========================================================
          */
     
         $contenidoProcesado = [];
         $contenidoErrores = [];
     
         /*
          * Cargamos los IDs existentes de productos.
          */
         $productosIds = [];
     
         foreach ($validador->productos as $producto) {
     
             $tipo = strtoupper(
                 trim($producto['tipo_producto'])
             );
     
             if ($tipo !== 'INSTRUMENTAL') {
     
                 $registro = Producto::where(
                     'codigo',
                     $producto['codigo']
                 )->first();
     
                 if ($registro) {
                     $productosIds[
                         strtoupper(trim($producto['codigo']))
                     ] = $registro->id;
                 }
             }
         }
     
         /*
          * Cargamos los instrumentales que ya existen.
          *
          * Como todavía no hemos importado los 43,
          * los resolvemos contra el Excel y simulamos
          * el futuro ID como NULL.
          */
         $instrumentalesIds = [];
     
         foreach ($instrumentalesCrear as $instrumental) {
     
             $existente = Instrumental::where(
                 'codigo',
                 $instrumental['codigo']
             )->first();
     
             $instrumentalesIds[
                 strtoupper(trim($instrumental['codigo']))
             ] = $existente?->id;
         }
     
         /*
          * Cargamos lotes existentes.
          */
         $lotesIds = [];
     
         foreach ($validador->lotes as $lote) {
     
             $codigoProducto = strtoupper(
                 trim($lote['codigo_producto'])
             );
     
             $numeroLote = strtoupper(
                 trim($lote['numero_lote'])
             );
     
             $productoId = $productosIds[$codigoProducto] ?? null;
     
             if ($productoId) {
     
                 $registro = Lote::where(
                     'producto_id',
                     $productoId
                 )->where(
                     'numero_lote',
                     $lote['numero_lote']
                 )->first();
     
                 $clave = $codigoProducto . '|' . $numeroLote;
     
                 $lotesIds[$clave] = $registro?->id;
             }
         }
     
         /*
          * Cargamos activos existentes.
          */
         $activosIds = [];
     
         foreach ($validador->activos as $activo) {
     
             $codigoActivo = strtoupper(
                 trim($activo['codigo_activo'])
             );
     
             $registro = Activo::where(
                 'codigo',
                 $activo['codigo_activo']
             )->first();
     
             $activosIds[$codigoActivo] = $registro?->id;
         }
     
         /*
          * =========================================================
          * IMPORTANTE
          * =========================================================
          *
          * El validator actual guarda los datos de
          * CONTENIDO_EQUIPOS dentro de la validación,
          * pero todavía no expone una colección pública
          * para recorrerla aquí.
          *
          * Por eso dejamos preparado el bloque y
          * no inventamos las 103 líneas.
          */
     
/*

/*
|--------------------------------------------------------------------------
| CONTENIDO_EQUIPOS - AUDITORÍA
|--------------------------------------------------------------------------
| Resuelve las 103 filas sin escribir en MySQL.
*/

$contenidoDetalle = [];

$auditoria = [
    'total' => count($validador->contenidos),

    'implantes' => 0,
    'instrumentales' => 0,

    'productos_existentes' => 0,
    'productos_no_encontrados' => 0,

    'instrumentales_existentes' => 0,
    'instrumentales_para_crear' => 0,

    'lotes_encontrados' => 0,
    'lotes_no_encontrados' => 0,

    'activos_encontrados' => 0,
    'activos_no_encontrados' => 0,

    'equipos_existentes' => 0,
    'equipos_para_crear' => 0,

    'errores_resolucion' => 0,
];




/*
|--------------------------------------------------------------------------
| Resolver cada contenido
|--------------------------------------------------------------------------
*/
$equiposProcesados = [];

foreach ($validador->contenidos as $contenido) {

    $tipo = strtoupper(
        trim((string) $contenido['tipo'])
    );

    $codigo = strtoupper(
        trim((string) $contenido['codigo'])
    );

    $codigoEquipo = strtoupper(
        trim((string) $contenido['codigo_equipo'])
    );

    /*
     * EQUIPO
     */

/*
 * EQUIPO
 *
 * Se cuenta una sola vez cada código de equipo.
 */

 $equipoBD = Equipo::query()
 ->whereRaw('UPPER(codigo) = ?', [$codigoEquipo])
 ->first([
     'id',
     'codigo',
     'nombre',
     'tipo_equipo',
 ]);

if ($equipoBD) {

 if (!isset($equiposProcesados[$codigoEquipo])) {

     $auditoria['equipos_existentes']++;

     $equiposProcesados[$codigoEquipo] = true;
 }

 $equipoResultado = [
     'id' => $equipoBD->id,
     'codigo' => $equipoBD->codigo,
     'nombre' => $equipoBD->nombre,
     'tipo_equipo' => $equipoBD->tipo_equipo,
     'accion' => 'USAR_EXISTENTE',
 ];

} else {

 if (!isset($equiposProcesados[$codigoEquipo])) {

     $auditoria['equipos_para_crear']++;

     $equiposProcesados[$codigoEquipo] = true;
 }

 $equipoExcel =
     $validador->equipos[$codigoEquipo] ?? null;

 $equipoResultado = [
     'id' => null,
     'codigo' => $codigoEquipo,
     'nombre' => $equipoExcel['nombre'] ?? null,
     'tipo_equipo' => $equipoExcel['tipo_equipo'] ?? null,
     'accion' => 'CREAR',
 ];
}

    /*
     * RESULTADO BASE
     */

    $resultado = [
        'fila' => $contenido['fila'],

        'equipo' => $equipoResultado,

        'tipo' => $tipo,

        'codigo' => $contenido['codigo'],

        'grupo' => $contenido['grupo'],
        'nombre_grupo' => $contenido['nombre_grupo'],

        'subgrupo' => $contenido['subgrupo'],
        'nombre_subgrupo' => $contenido['nombre_subgrupo'],

        'bandeja' => $contenido['bandeja'],

        'orden' => $contenido['orden'],

        'cantidad' => $contenido['cantidad'],

        'estado' => $contenido['estado'],

        'producto' => null,

        'instrumental' => null,

        'lote' => null,

        'activo' => null,

        'destino' => [
            'equipo_detalles' => true,
            'equipo_inventario' => true,
        ],

        'accion' => null,
    ];

    /*
     |--------------------------------------------------------------------------
     | IMPLANTE
     |--------------------------------------------------------------------------
     */

    if ($tipo === 'IMPLANTE') {

        $auditoria['implantes']++;

        $producto = Producto::query()
            ->whereRaw('UPPER(codigo) = ?', [$codigo])
            ->first([
                'id',
                'codigo',
                'nombre',
            ]);

        if ($producto) {

            $auditoria['productos_existentes']++;

            $resultado['producto'] = [
                'id' => $producto->id,
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'accion' => 'USAR_EXISTENTE',
            ];

            /*
             * LOTE
             */

            if (!empty($contenido['numero_lote'])) {

                $claveLote =
                    $codigo . '|' .
                    strtoupper(
                        trim(
                            (string) $contenido['numero_lote']
                        )
                    );

                $loteExcel =
                    $validador->lotes[$claveLote] ?? null;

                if ($loteExcel) {

                    $auditoria['lotes_encontrados']++;

                    $resultado['lote'] = [
                        'producto_id' => $producto->id,
                        'numero_lote' =>
                            $loteExcel['numero_lote'],
                        'fecha_fabricacion' =>
                            $this->convertirFechaExcel(
                                $loteExcel['fecha_fabricacion']
                            ),
                        'fecha_vencimiento' =>
                            $loteExcel['fecha_vencimiento'],
                        'registro_sanitario' =>
                            $loteExcel['registro_sanitario'],
                        'accion' => 'CREAR_LOTE',
                    ];

                } else {

                    $auditoria['lotes_no_encontrados']++;
                    $auditoria['errores_resolucion']++;

                    $resultado['lote'] = [
                        'numero_lote' =>
                            $contenido['numero_lote'],
                        'accion' => 'ERROR_LOTE',
                    ];
                }
            }

            $resultado['accion'] =
                'USAR_PRODUCTO_EXISTENTE';

        } else {

            $auditoria['productos_no_encontrados']++;
            $auditoria['errores_resolucion']++;

            $resultado['accion'] =
                'ERROR_PRODUCTO_NO_EXISTE';
        }
    }

    /*
     |--------------------------------------------------------------------------
     | INSTRUMENTAL
     |--------------------------------------------------------------------------
     */

    elseif ($tipo === 'INSTRUMENTAL') {

        $auditoria['instrumentales']++;

        $instrumental = Instrumental::query()
            ->whereRaw('UPPER(codigo) = ?', [$codigo])
            ->first([
                'id',
                'codigo',
                'nombre',
                'marca',
                'familia',
                'subfamilia',
            ]);

        if ($instrumental) {

            $auditoria['instrumentales_existentes']++;

            $resultado['instrumental'] = [
                'id' => $instrumental->id,
                'codigo' => $instrumental->codigo,
                'nombre' => $instrumental->nombre,
                'accion' => 'USAR_EXISTENTE',
            ];

            $resultado['accion'] =
                'USAR_INSTRUMENTAL_EXISTENTE';

        } else {

            $auditoria['instrumentales_para_crear']++;

            $resultado['instrumental'] = [
                'id' => null,
                'codigo' => $contenido['codigo'],
                'nombre' =>
                    $contenido['nombre_subgrupo']
                    ?: $contenido['nombre_grupo'],
                'accion' => 'CREAR',
            ];

            $resultado['accion'] =
                'CREAR_INSTRUMENTAL';
        }
    }

    /*
     |--------------------------------------------------------------------------
     | ACTIVO
     |--------------------------------------------------------------------------
     */

    if (!empty($contenido['codigo_activo'])) {

        $codigoActivo = strtoupper(
            trim(
                (string) $contenido['codigo_activo']
            )
        );

        $activo = Activo::query()
            ->whereRaw('UPPER(codigo) = ?', [$codigoActivo])
            ->first([
                'id',
                'codigo',
                'nombre',
                'numero_serial',
                'producto_id',
                'bodega_id',
                'equipo_id',
            ]);

        if ($activo) {

            $auditoria['activos_encontrados']++;

            $resultado['activo'] = [
                'id' => $activo->id,
                'codigo' => $activo->codigo,
                'nombre' => $activo->nombre,
                'numero_serial' =>
                    $activo->numero_serial,
                'bodega_id' => $activo->bodega_id,
                'equipo_id' => $activo->equipo_id,
                'accion' => 'USAR_EXISTENTE',
            ];

        } else {

            $auditoria['activos_no_encontrados']++;
            $auditoria['errores_resolucion']++;

            $resultado['activo'] = [
                'codigo' =>
                    $contenido['codigo_activo'],
                'accion' => 'ERROR_ACTIVO',
            ];
        }
    }

    $contenidoDetalle[] = $resultado;
}

/*
|--------------------------------------------------------------------------
| Resultado de auditoría
|--------------------------------------------------------------------------
*/

$contenidoResumen = [
    'estado' =>
        $auditoria['errores_resolucion'] === 0
            ? 'AUDITORIA_OK'
            : 'AUDITORIA_CON_ERRORES',

    'auditoria' => $auditoria,

    'detalle' => $contenidoDetalle,
];



     
         /*
          * =========================================================
          * RESUMEN
          * =========================================================
          */
     
         return [
     
             'ok' => true,
     
             'modo' => 'SIMULACION_SIN_ESCRITURA',
     
             'equipo_piloto' => $codigoEquipo,
     
             'regionales' => [
                 'crear' => count($regionalesCrear),
                 'existentes' => count($regionalesExistentes),
             ],
     
             'bodegas' => [
                 'crear' => count($bodegasCrear),
                 'existentes' => count($bodegasExistentes),
             ],
     
             'equipos' => [
                 'crear' => count($equiposCrear),
                 'existentes' => count($equiposExistentes),
             ],
     
             'productos' => [
                 'existentes_contifico' => count($productosExistentes),
                 'no_encontrados' => count($productosNoEncontrados),
                 'detalle_no_encontrados' => $productosNoEncontrados,
             ],
     
             'instrumentales' => [
                 'crear' => count($instrumentalesCrear),
             ],
     
             'lotes' => [
                 'crear' => count($lotesCrear),
             ],
     
             'activos' => [
                 'crear' => count($activosCrear),
                 'existentes' => count($activosExistentes),
             ],
     
             'contenido_equipo' => $contenidoResumen,
         ];
     }


    /**
     * Importación real.
     *
     * Todavía no implementada.
     */
    public function importar(ValidadorOrtomaImport $validador): array
    {
        if (!empty($validador->errores)) {
            throw new RuntimeException(
                'No se puede importar porque existen errores de validación.'
            );
        }
    
    

// ============================================================
// EQUIPO PILOTO
// ============================================================

$codigoPiloto = strtoupper(
    trim((string) $this->equipoPiloto)
);

if (!isset($validador->equipos[$codigoPiloto])) {
    throw new \Exception(
        "El equipo piloto {$codigoPiloto} no existe en el Excel."
    );
}

$equipoPilotoExcel = $validador->equipos[$codigoPiloto];

$regionalPiloto = strtoupper(
    trim((string) $equipoPilotoExcel['regional'])
);

$bodegaPiloto = strtoupper(
    trim((string) $equipoPilotoExcel['bodega'])
);

// ============================================================
// DEPENDENCIAS DEL EQUIPO PILOTO
// ============================================================

$instrumentalesPiloto = [];
$lotesPiloto = [];
$activosPiloto = [];

foreach ($validador->contenidos as $contenido) {

    if (
        strtoupper(
            trim((string) $contenido['codigo_equipo'])
        ) !== $codigoPiloto
    ) {
        continue;
    }

    $tipoContenido = strtoupper(
        trim((string) $contenido['tipo'])
    );

    $codigoContenido = strtoupper(
        trim((string) $contenido['codigo'])
    );

    // --------------------------------------------------------
    // INSTRUMENTALES
    // --------------------------------------------------------

    if ($tipoContenido === 'INSTRUMENTAL') {

        $instrumentalesPiloto[$codigoContenido] = true;
    }

    // --------------------------------------------------------
    // LOTES DE IMPLANTES
    // --------------------------------------------------------

    if (
        $tipoContenido === 'IMPLANTE'
        && !empty($contenido['numero_lote'])
    ) {

        $numeroLote = strtoupper(
            trim((string) $contenido['numero_lote'])
        );

        $lotesPiloto[
            $codigoContenido . '|' . $numeroLote
        ] = true;
    }

    // --------------------------------------------------------
    // ACTIVOS
    // --------------------------------------------------------

    if (!empty($contenido['codigo_activo'])) {

        $codigoActivo = strtoupper(
            trim((string) $contenido['codigo_activo'])
        );

        $activosPiloto[$codigoActivo] = true;
    }
}



        // También incluir activos que tengan el equipo piloto
        foreach ($validador->activos as $activo) {

            if (
                !empty($activo['equipo'])
                &&
                strtoupper(
                    trim((string) $activo['equipo'])
                ) === $codigoPiloto
            ) {

                $codigoActivo = strtoupper(
                    trim((string) $activo['codigo_activo'])
                );

                $activosPiloto[$codigoActivo] = true;
            }
        }
    
        /*
        |--------------------------------------------------------------------------
        | CONTADORES
        |--------------------------------------------------------------------------
        */
    
        $resultado = [
            'ok' => false,
    
            'modo' => 'IMPORTACION_REAL',
    
            'equipo_piloto' => $codigoPiloto,
    
            'creados' => [
                'regionales' => 0,
                'bodegas' => 0,
                'equipos' => 0,
                'instrumentales' => 0,
                'lotes' => 0,
                'activos' => 0,
                'equipo_detalles' => 0,
                'equipo_inventario' => 0,
            ],
    
            'existentes' => [
                'regionales' => 0,
                'bodegas' => 0,
                'equipos' => 0,
                'instrumentales' => 0,
                'lotes' => 0,
                'activos' => 0,
            ],
        ];
    
        /*
        |--------------------------------------------------------------------------
        | TRANSACCIÓN
        |--------------------------------------------------------------------------
        */
    
        DB::transaction(function () use (
            $validador,
            $codigoPiloto,
            $regionalPiloto,
            $bodegaPiloto,
            &$resultado
        ) {
    
            /*
            |--------------------------------------------------------------------------
            | 1. REGIONALES
            |--------------------------------------------------------------------------
            */
            
            $regionalesIds = [];

            foreach ($validador->regionales as $regional) {
            
                if (
                    strtoupper(
                        trim((string) $regional['codigo'])
                    ) !== $regionalPiloto
                ) {
                    continue;
                }
            
                $codigo = trim((string) $regional['codigo']);
    
                $registro = Regional::where(
                    'codigo',
                    $codigo
                )->first();
    
                if ($registro) {
    
                    $resultado['existentes']['regionales']++;
    
                } else {
    
                    $registro = Regional::create([
                        'codigo' => $codigo,
                        'nombre' => $regional['descripcion'],
                        'descripcion' => $regional['descripcion'],
                        'activo' => strtoupper(trim((string) $regional['estado'])) === 'ACTIVO',
                    ]);
    
                    $resultado['creados']['regionales']++;
                }
    
                $regionalesIds[strtoupper($codigo)] = $registro->id;
            }
    
            /*
            |--------------------------------------------------------------------------
            | 2. BODEGAS
            |--------------------------------------------------------------------------
            */
    
            $bodegasIds = [];
    
            foreach ($validador->bodegas as $bodega) {


                if (
                    strtoupper(
                        trim((string) $bodega['codigo'])
                    ) !== $bodegaPiloto
                ) {
                    continue;
                }

    
                $codigo = trim((string) $bodega['codigo']);
    
                $regionalCodigo = strtoupper(
                    trim((string) $bodega['regional'])
                );
    
                $regionalId =
                    $regionalesIds[$regionalCodigo] ?? null;
    
                if (!$regionalId) {
    
                    $regional = Regional::where(
                        'codigo',
                        $bodega['regional']
                    )->first();
    
                    $regionalId = $regional?->id;
                }
    
                if (!$regionalId) {
                    throw new RuntimeException(
                        "No se pudo resolver la regional de la bodega {$codigo}."
                    );
                }
    
                $registro = Bodega::where(
                    'codigo',
                    $codigo
                )->first();
    
                if ($registro) {
    
                    $resultado['existentes']['bodegas']++;
    
                } else {
    
                    $registro = Bodega::create([
                        'regional_id' => $regionalId,
                        'codigo' => $codigo,
                        'nombre' => $bodega['descripcion'],
                        'tipo_bodega' => 'SECUNDARIA',
                        'activo' => strtoupper(trim((string) $bodega['estado'])) === 'ACTIVO',
                    ]);
    
                    $resultado['creados']['bodegas']++;
                }
    
                $bodegasIds[strtoupper($codigo)] = $registro->id;
            }
    
            /*
            |--------------------------------------------------------------------------
            | 3. INSTRUMENTALES
            |--------------------------------------------------------------------------
            */
    
            $instrumentalesIds = [];
    
            foreach ($validador->productos as $producto) {

                $tipo = strtoupper(
                    trim((string) $producto['tipo_producto'])
                );
            
                if ($tipo !== 'INSTRUMENTAL') {
                    continue;
                }
            
                $codigoInstrumental = strtoupper(
                    trim((string) $producto['codigo'])
                );
            
                if (!isset($instrumentalesPiloto[$codigoInstrumental])) {
                    continue;
                }
            
                $codigo = trim((string) $producto['codigo']);
    
                $registro = Instrumental::where(
                    'codigo',
                    $codigo
                )->first();
    
                if ($registro) {
    
                    $resultado['existentes']['instrumentales']++;
    
                } else {
    
                    $registro = Instrumental::create([
                        'codigo' => $codigo,
                        'nombre' => $producto['nombre'],
                        'marca' => $producto['marca'] ?? null,
                        'familia' => $producto['familia'] ?? null,
                        'subfamilia' => $producto['subfamilia'] ?? null,
                        'estado' => $producto['estado'] ?? 'ACTIVO',
                        'activo' => true,
                    ]);
    
                    $resultado['creados']['instrumentales']++;
                }
    
                $instrumentalesIds[strtoupper($codigo)] =
                    $registro->id;
            }
    
            /*
            |--------------------------------------------------------------------------
            | 4. EQUIPOS
            |--------------------------------------------------------------------------
            */
    
            $equiposIds = [];
    
            foreach ($validador->equipos as $equipo) {
    
                /*
                 * Para el piloto solamente procesamos P13NBC229.
                 */
                if (
                    strtoupper(
                        trim((string) $equipo['codigo_equipo'])
                    ) !== $codigoPiloto
                ) {
                    continue;
                }
    
                $codigo = trim(
                    (string) $equipo['codigo_equipo']
                );
    
                $regionalCodigo = strtoupper(
                    trim((string) $equipo['regional'])
                );
    
                $bodegaCodigo = strtoupper(
                    trim((string) $equipo['bodega'])
                );
    
                $regionalId =
                    $regionalesIds[$regionalCodigo] ?? null;
    
                $bodegaId =
                    $bodegasIds[$bodegaCodigo] ?? null;
    
                if (!$regionalId) {
                    throw new RuntimeException(
                        "No se pudo resolver la regional del equipo {$codigo}."
                    );
                }
    
                if (!$bodegaId) {
                    throw new RuntimeException(
                        "No se pudo resolver la bodega del equipo {$codigo}."
                    );
                }
    
                $registro = Equipo::where(
                    'codigo',
                    $codigo
                )->first();
    
                if ($registro) {
    
                    $resultado['existentes']['equipos']++;
    
                } else {
    
                    $registro = Equipo::create([
                        'codigo' => $codigo,
                        'nombre' => $equipo['nombre'],
                        'descripcion' => null,
                        'tipo_equipo' => $equipo['tipo_equipo'],
                        'regional_id' => $regionalId,
                        'bodega_id' => $bodegaId,
                        'estado' => $equipo['estado'],
                        'activo' => (bool) $equipo['activo'],
                    ]);
    
                    $resultado['creados']['equipos']++;
                }
    
                $equiposIds[strtoupper($codigo)] =
                    $registro->id;
            }
    
            /*
            |--------------------------------------------------------------------------
            | 5. LOTES
            |--------------------------------------------------------------------------
            */
    
            $lotesIds = [];

            dd([
                'tipo_lotes' => gettype($validador->lotes),
                'total_lotes' => is_countable($validador->lotes)
                    ? count($validador->lotes)
                    : 'NO_COUNTABLE',
            
                'total_lotes_piloto' => is_countable($lotesPiloto)
                    ? count($lotesPiloto)
                    : 'NO_COUNTABLE',
            
                'primera_clave_lote_piloto' => is_array($lotesPiloto)
                    ? array_key_first($lotesPiloto)
                    : 'NO_ARRAY',
            
                'primer_lote' => is_array($validador->lotes)
                    ? reset($validador->lotes)
                    : null,
            ]);
            
            foreach ($validador->lotes as $lote) {
                
            
                $codigoProducto = strtoupper(
                    trim((string) $lote['codigo_producto'])
                );
            
                // ...
            

                if (!$producto) {
                    throw new RuntimeException(
                        "No existe el producto {$lote['codigo_producto']} para el lote."
                    );
                }
    
                $numeroLote = trim(
                    (string) $lote['numero_lote']
                );
    
                $registro = Lote::where(
                    'producto_id',
                    $producto->id
                )->where(
                    'numero_lote',
                    $numeroLote
                )->first();
    
                if ($registro) {
    
                    $resultado['existentes']['lotes']++;
    
                } else {
    
                    $registro = Lote::create([
                        'producto_id' => $producto->id,
                        'numero_lote' => $numeroLote,
    
                        'fecha_fabricacion' =>
                            $this->convertirFechaExcel(
                                $lote['fecha_fabricacion'] ?? null
                            ),
    
                        'fecha_vencimiento' =>
                            $this->convertirFechaExcel(
                                $lote['fecha_vencimiento'] ?? null
                            ),
    
                        'registro_sanitario' =>
                            $lote['registro_sanitario'] ?? null,
    
                        'estado' =>
                            $lote['estado'] ?? 'ACTIVO',
    
                        'observaciones' => null,
                    ]);
    
                    $resultado['creados']['lotes']++;
                }
    
                $clave =
                    $codigoProducto . '|' .
                    strtoupper($numeroLote);
    
                $lotesIds[$clave] = $registro->id;

                if ($numeroLote === '2400018645') {
                    dd([
                        'codigoProducto' => $codigoProducto,
                        'numeroLote' => $numeroLote,
                        'clave' => $clave,
                        'lote_id' => $registro->id,
                        'producto_id' => $producto->id,
                    ]);
                }

            
        
    
            /*
            |--------------------------------------------------------------------------
            | 6. ACTIVOS
            |--------------------------------------------------------------------------
            */
    
            $activosIds = [];
    
            foreach ($validador->activos as $activo) {

                $codigoActivo = strtoupper(
                    trim((string) $activo['codigo_activo'])
                );
                
                if (!isset($activosPiloto[$codigoActivo])) {
                    continue;
                }
    
                $codigoActivo = trim(
                    (string) $activo['codigo_activo']
                );
    
                $registro = Activo::where(
                    'codigo',
                    $codigoActivo
                )->first();
    
                /*
                 * Producto es opcional para activos.
                 */
                $productoId = null;
    
                if (!empty($activo['codigo_producto'])) {
    
                    $producto = Producto::whereRaw(
                        'UPPER(codigo) = ?',
                        [
                            strtoupper(
                                trim(
                                    (string) $activo['codigo_producto']
                                )
                            )
                        ]
                    )->first();
    
                    if (!$producto) {
                        throw new RuntimeException(
                            "El producto del activo {$codigoActivo} no existe."
                        );
                    }
    
                    $productoId = $producto->id;
                }
    
                /*
                 * Bodega es opcional.
                 */
                $bodegaId = null;
    
                if (!empty($activo['bodega'])) {
    
                    $bodegaCodigo = strtoupper(
                        trim((string) $activo['bodega'])
                    );
    
                    $bodegaId =
                        $bodegasIds[$bodegaCodigo] ?? null;
    
                    if (!$bodegaId) {
    
                        $bodega = Bodega::whereRaw(
                            'UPPER(codigo) = ?',
                            [$bodegaCodigo]
                        )->first();
    
                        $bodegaId = $bodega?->id;
                    }
    
                    if (!$bodegaId) {
                        throw new RuntimeException(
                            "No se pudo resolver la bodega del activo {$codigoActivo}."
                        );
                    }
                }
    
                /*
                 * Equipo es opcional.
                 */
                $equipoId = null;
    
                if (!empty($activo['equipo'])) {
    
                    $equipoCodigo = strtoupper(
                        trim((string) $activo['equipo'])
                    );
    
                    $equipoId =
                        $equiposIds[$equipoCodigo] ?? null;
    
                    if (!$equipoId) {
    
                        $equipo = Equipo::whereRaw(
                            'UPPER(codigo) = ?',
                            [$equipoCodigo]
                        )->first();
    
                        $equipoId = $equipo?->id;
                    }
                }
    
                if ($registro) {
    
                    $resultado['existentes']['activos']++;
    
                } else {
    
                    $registro = Activo::create([
                        'producto_id' => $productoId,
                        'codigo' => $codigoActivo,
                        'numero_serial' =>
                            $activo['numero_serial'] ?? null,
                        'nombre' => $activo['nombre'],
                        'tipo_activo' => $activo['tipo_activo'],
                        'marca' => $activo['marca'] ?? null,
                        'modelo' => $activo['modelo'] ?? null,
                        'serie' => $activo['serie'] ?? null,
                        'estado' => $activo['estado'],
                        'bodega_id' => $bodegaId,
                        'equipo_id' => $equipoId,
                        'activo' => (bool) $activo['activo'],
                    ]);
    
                    $resultado['creados']['activos']++;
                }
    
                $activosIds[strtoupper($codigoActivo)] =
                    $registro->id;
            }
    
            /*
            |--------------------------------------------------------------------------
            | 7. CONTENIDO DEL EQUIPO
            |--------------------------------------------------------------------------
            */
    
            foreach ($validador->contenidos as $contenido) {
    
                $codigoEquipo = strtoupper(
                    trim((string) $contenido['codigo_equipo'])
                );
    
                /*
                 * PILOTO
                 */
                if ($codigoEquipo !== $codigoPiloto) {
                    continue;
                }
    
                $equipoId =
                    $equiposIds[$codigoEquipo] ?? null;
    
                if (!$equipoId) {
                    throw new RuntimeException(
                        "No se pudo resolver el ID del equipo {$codigoEquipo}."
                    );
                }
    
                $tipo = strtoupper(
                    trim((string) $contenido['tipo'])
                );
    
                $codigoContenido = strtoupper(
                    trim((string) $contenido['codigo'])
                );
    
                $productoId = null;
                $instrumentalId = null;
                $loteId = null;
                $activoId = null;
    
                /*
                 * IMPLANTE
                 */
                if ($tipo === 'IMPLANTE') {
    
                    $producto = Producto::whereRaw(
                        'UPPER(codigo) = ?',
                        [$codigoContenido]
                    )->first();
    
                    if (!$producto) {
                        throw new RuntimeException(
                            "No existe el producto {$contenido['codigo']}."
                        );
                    }
    
                    $productoId = $producto->id;
    
                    if (!empty($contenido['numero_lote'])) {
    
                        $claveLote =
                            $codigoContenido . '|' .
                            strtoupper(
                                trim(
                                    (string) $contenido['numero_lote']
                                )
                            );
    
                                $loteId = $lotesIds[$claveLote] ?? null;

                                if (!$loteId) {

                                    $lote = Lote::where(
                                        'producto_id',
                                        $productoId
                                    )->where(
                                        'numero_lote',
                                        trim((string) $contenido['numero_lote'])
                                    )->first();

                                    $loteId = $lote?->id;
                                }

                                if (!$loteId) {

                                    dd([
                                        'codigoContenido' => $codigoContenido,
                                        'numeroLoteContenido' => $contenido['numero_lote'],
                                        'claveLoteBuscada' => $claveLote,
                                        'loteIdEncontradoEnArray' => $lotesIds[$claveLote] ?? null,
                                        'clavesLotesDisponibles' => array_keys($lotesIds),
                                    ]);

                                    throw new RuntimeException(
                                        "No se pudo resolver el lote {$contenido['numero_lote']} del producto {$codigoContenido}."
                                    );
                                }
                    }
                }
    
                /*
                 * INSTRUMENTAL
                 */
                elseif ($tipo === 'INSTRUMENTAL') {
    
                    $instrumentalId =
                        $instrumentalesIds[$codigoContenido] ?? null;
    
                    if (!$instrumentalId) {
                        throw new RuntimeException(
                            "No se pudo resolver el instrumental {$contenido['codigo']}."
                        );
                    }
                }
    
                /*
                 * ACTIVO
                 */
                if (!empty($contenido['codigo_activo'])) {
    
                    $codigoActivo = strtoupper(
                        trim(
                            (string) $contenido['codigo_activo']
                        )
                    );
    
                    $activoId =
                        $activosIds[$codigoActivo] ?? null;
    
                    if (!$activoId) {
                        throw new RuntimeException(
                            "No se pudo resolver el activo {$contenido['codigo_activo']}."
                        );
                    }
                }
    
                /*
                 * DATOS COMUNES
                 */
                $datosDetalle = [
                    'equipo_id' => $equipoId,
                    'producto_id' => $productoId,
                    'instrumental_id' => $instrumentalId,
                    'activo_id' => $activoId,
    
                    'tipo_contenido' => $tipo,
    
                    'grupo' => $contenido['grupo'],
                    'subgrupo' => $contenido['subgrupo'],
                    'bandeja' => $contenido['bandeja'] ?: null,
                    'orden' => $contenido['orden'],
    
                    'cantidad_configurada' =>
                        $contenido['cantidad'],
    
                    'requiere_lote' =>
                        $tipo === 'IMPLANTE',
    
                    'requiere_serial' =>
                        $activoId !== null,
    
                    'activo' =>
                        $contenido['estado'] === 'ACTIVO',
                ];
    
                /*
                 * EQUIPO_DETALLES
                 *
                 * Buscamos primero el mismo contenido.
                 */
                $detalle = \App\Models\EquipoDetalle::where([
                    'equipo_id' => $equipoId,
                    'producto_id' => $productoId,
                    'instrumental_id' => $instrumentalId,
                    'activo_id' => $activoId,
                    'grupo' => $contenido['grupo'],
                    'subgrupo' => $contenido['subgrupo'],
                    'orden' => $contenido['orden'],
                ])->first();
    
                if (!$detalle) {
    
                    \App\Models\EquipoDetalle::create(
                        $datosDetalle
                    );
    
                    $resultado['creados']['equipo_detalles']++;
                }
                
            /*
            * EQUIPO_INVENTARIO
            */
            $datosInventario = [
                'equipo_id' => $equipoId,
                'producto_id' => $productoId,
                'instrumental_id' => $instrumentalId,
                'activo_id' => $activoId,
                'lote_id' => $loteId,

                'grupo' => $contenido['grupo'] ?? null,
                'subgrupo' => $contenido['subgrupo'] ?? null,
                'bandeja' => !empty($contenido['bandeja'])
                    ? $contenido['bandeja']
                    : null,
                'orden' => $contenido['orden'] ?? null,

                'cantidad' => $contenido['cantidad'] ?? 1,
                'cantidad_reservada' => 0,

                'estado' => $contenido['estado'] ?? 'ACTIVO',

                'fecha_ingreso' => now(),
            ];

            /*
            * Buscar si el elemento ya existe en EQUIPO_INVENTARIO.
            *
            * Para un ACTIVO:
            * La combinación equipo_id + activo_id identifica
            * el registro dentro del equipo.
            */
            if ($activoId) {

                $inventario = \App\Models\EquipoInventario::where(
                    'equipo_id',
                    $equipoId
                )->where(
                    'activo_id',
                    $activoId
                )->first();

            } else {

                /*
                * Para productos/lotes/instrumentales:
                * buscamos por la combinación completa del contenido.
                */
                $inventario = \App\Models\EquipoInventario::where(
                    'equipo_id',
                    $equipoId
                )->where(
                    'producto_id',
                    $productoId
                )->where(
                    'instrumental_id',
                    $instrumentalId
                )->where(
                    'lote_id',
                    $loteId
                )->where(
                    'grupo',
                    $contenido['grupo'] ?? null
                )->where(
                    'subgrupo',
                    $contenido['subgrupo'] ?? null
                )->where(
                    'orden',
                    $contenido['orden'] ?? null
                )->first();
            }

            /*
            * Crear solamente si todavía no existe.
            */
            if (!$inventario) {

                \App\Models\EquipoInventario::create($datosInventario);

                $resultado['creados']['equipo_inventario']++;
            