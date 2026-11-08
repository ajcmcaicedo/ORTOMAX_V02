<?php

namespace App\Console\Commands;

use App\Services\SiigoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RecuperarProductosContifico extends Command
{
    protected $signature = 'contifico:recuperar-productos
                            {--codigo=* : Código o códigos de productos a recuperar}
                            {--archivo= : Archivo Excel que contiene la columna CODIGO}';

    protected $description = 'Recupera productos específicos de Contífico mediante V1 → V2 sin recorrer el catálogo completo';

    public function handle(SiigoService $siigo)
    {
        $this->info('==========================================');
        $this->info(' RECUPERACIÓN DE PRODUCTOS CONTÍFICO');
        $this->info(' V1 → V2');
        $this->info('==========================================');
        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | 1. OBTENER CÓDIGOS
        |--------------------------------------------------------------------------
        */

        $codigos = collect();

        /*
        |--------------------------------------------------------------------------
        | 1.1 CÓDIGOS RECIBIDOS DIRECTAMENTE
        |--------------------------------------------------------------------------
        */

        $codigos = $codigos->merge(
            collect($this->option('codigo'))
                ->flatMap(function ($valor) {
                    return explode(',', (string) $valor);
                })
                ->map(fn ($codigo) => trim($codigo))
                ->filter()
        );

        /*
        |--------------------------------------------------------------------------
        | 1.2 CÓDIGOS DESDE ARCHIVO EXCEL
        |--------------------------------------------------------------------------
        */

        $archivo = $this->option('archivo');

        if ($archivo) {

            $rutaArchivo = $archivo;

            if (!file_exists($rutaArchivo)) {

                $rutaAlternativa =
                    base_path($archivo);

                if (file_exists($rutaAlternativa)) {
                    $rutaArchivo = $rutaAlternativa;
                }
            }

            if (!file_exists($rutaArchivo)) {

                $this->error(
                    "No existe el archivo: {$archivo}"
                );

                return Command::FAILURE;
            }

            $this->info(
                'Leyendo archivo Excel: ' .
                $rutaArchivo
            );

            try {

                $spreadsheet =
                    IOFactory::load($rutaArchivo);

                /*
                |--------------------------------------------------------------------------
                | BUSCAR HOJA REVISION_52
                |--------------------------------------------------------------------------
                */

                $sheet = null;

                if (
                    $spreadsheet->sheetNameExists(
                        'REVISION_52'
                    )
                ) {

                    $sheet =
                        $spreadsheet->getSheetByName(
                            'REVISION_52'
                        );

                } else {

                    $sheet =
                        $spreadsheet->getActiveSheet();
                }

                $this->info(
                    'Hoja utilizada: ' .
                    $sheet->getTitle()
                );

                /*
                |--------------------------------------------------------------------------
                | LEER FILA DE ENCABEZADOS
                |--------------------------------------------------------------------------
                */

                $filas =
                    $sheet->toArray(
                        null,
                        true,
                        true,
                        false
                    );

                if (empty($filas)) {

                    $this->error(
                        'El archivo Excel está vacío.'
                    );

                    return Command::FAILURE;
                }

                $encabezados =
                    array_map(
                        function ($valor) {
                            return mb_strtolower(
                                trim((string) $valor),
                                'UTF-8'
                            );
                        },
                        $filas[0]
                    );

                $indiceCodigo =
                    array_search(
                        'codigo',
                        $encabezados,
                        true
                    );

                /*
                |--------------------------------------------------------------------------
                | TAMBIÉN ACEPTAR CODIGO SIN TILDE
                |--------------------------------------------------------------------------
                */

                if ($indiceCodigo === false) {

                    $this->error(
                        'No se encontró la columna CODIGO en el archivo.'
                    );

                    $this->line(
                        'Columnas encontradas: ' .
                        implode(', ', $encabezados)
                    );

                    return Command::FAILURE;
                }

                /*
                |--------------------------------------------------------------------------
                | LEER CÓDIGOS
                |--------------------------------------------------------------------------
                */

                $codigosArchivo = collect();

                foreach (
                    array_slice($filas, 1)
                    as $fila
                ) {

                    $codigo =
                        trim(
                            (string)
                            ($fila[$indiceCodigo] ?? '')
                        );

                    if ($codigo === '') {
                        continue;
                    }

                    $codigosArchivo->push($codigo);
                }

                $this->info(
                    'Códigos encontrados en Excel: ' .
                    $codigosArchivo->count()
                );

                $codigos =
                    $codigos->merge(
                        $codigosArchivo
                    );

            } catch (\Throwable $e) {

                $this->error(
                    'Error leyendo Excel: ' .
                    $e->getMessage()
                );

                return Command::FAILURE;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | NORMALIZAR Y ELIMINAR DUPLICADOS
        |--------------------------------------------------------------------------
        */

        $codigos = $codigos
            ->map(fn ($codigo) => trim((string) $codigo))
            ->filter()
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | VALIDAR
        |--------------------------------------------------------------------------
        */

        if ($codigos->isEmpty()) {

            $this->error(
                'No se recibió ningún código.'
            );

            $this->line('');

            $this->line(
                'Ejemplo por código:'
            );

            $this->line(
                'php artisan contifico:recuperar-productos --codigo=INST6512'
            );

            $this->line('');

            $this->line(
                'Ejemplo con Excel:'
            );

            $this->line(
                'php artisan contifico:recuperar-productos --archivo=storage/app/importaciones/REVISION_52_PRODUCTOS_CONTIFICO_FINAL.xlsx'
            );

            return Command::FAILURE;
        }

        $this->newLine();

        $this->info(
            'Códigos únicos a procesar: ' .
            $codigos->count()
        );

        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | 2. ESTADÍSTICAS
        |--------------------------------------------------------------------------
        */

        $recuperados = 0;
        $yaExistian = 0;
        $noEncontrados = 0;
        $conflictos = 0;
        $errores = 0;

        /*
        |--------------------------------------------------------------------------
        | 3. PROCESAR CADA CÓDIGO
        |--------------------------------------------------------------------------
        */

        foreach ($codigos as $codigo) {

            $this->info(
                "Procesando: {$codigo}"
            );

            try {

                /*
                |--------------------------------------------------------------------------
                | 3.1 ¿YA EXISTE EN ORTOMAX?
                |--------------------------------------------------------------------------
                */

                $existente =
                    DB::table('productos')
                        ->where('codigo', $codigo)
                        ->first();

                /*
                |--------------------------------------------------------------------------
                | 3.2 RECUPERAR V1 → V2
                |--------------------------------------------------------------------------
                */

                $resultado =
                    $siigo->recuperarProductoPorCodigo(
                        $codigo
                    );

                if (!$resultado) {

                    $this->warn(
                        "  NO ENCONTRADO en Contífico: {$codigo}"
                    );

                    $noEncontrados++;

                    continue;
                }

                $productoV1 =
                    $resultado['v1'] ?? null;

                $productoV2 =
                    $resultado['v2'] ?? null;

                if (
                    !is_array($productoV1) ||
                    !is_array($productoV2)
                ) {

                    $this->error(
                        '  Respuesta V1/V2 inválida'
                    );

                    $errores++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | 3.3 OBTENER ID CONTÍFICO
                |--------------------------------------------------------------------------
                */

                $contificoId =
                    trim(
                        (string)
                        ($productoV2['id'] ?? '')
                    );

                if ($contificoId === '') {

                    $this->error(
                        '  V2 no entregó ID'
                    );

                    $errores++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | 3.4 VALIDAR CÓDIGO V2
                |--------------------------------------------------------------------------
                */

                $codigoV2 =
                    trim(
                        (string)
                        ($productoV2['codigo'] ?? '')
                    );

                if (
                    strtoupper($codigoV2) !==
                    strtoupper($codigo)
                ) {

                    $this->error(
                        "  CONFLICTO: solicitado {$codigo}, V2 entregó {$codigoV2}"
                    );

                    $conflictos++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | 3.5 VALIDAR EXISTENCIA POR CÓDIGO
                |--------------------------------------------------------------------------
                */

                if ($existente) {

                    $idExistente =
                        trim(
                            (string)
                            ($existente->contifico_id ?? '')
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | MISMO CÓDIGO + MISMO ID
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $idExistente !== '' &&
                        $idExistente === $contificoId
                    ) {

                        $this->info(
                            "  YA EXISTE: {$codigo} → {$contificoId}"
                        );

                        $yaExistian++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | MISMO CÓDIGO + ID DIFERENTE
                    |--------------------------------------------------------------------------
                    */

                    $this->error(
                        "  CONFLICTO: {$codigo} ya existe con contifico_id {$idExistente}; Contífico entrega {$contificoId}"
                    );

                    $conflictos++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | 3.6 VALIDAR CONTIFICO_ID
                |--------------------------------------------------------------------------
                */

                $productoPorId =
                    DB::table('productos')
                        ->where(
                            'contifico_id',
                            $contificoId
                        )
                        ->first();

                if ($productoPorId) {

                    $codigoExistente =
                        trim(
                            (string)
                            ($productoPorId->codigo ?? '')
                        );

                    if (
                        strtoupper($codigoExistente) !==
                        strtoupper($codigo)
                    ) {

                        $this->error(
                            "  CONFLICTO: {$contificoId} ya pertenece a {$codigoExistente}"
                        );

                        $conflictos++;

                        continue;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | 3.7 PREPARAR REGISTRO
                |--------------------------------------------------------------------------
                */

                $registro = [

                    'contifico_id' =>
                        $contificoId,

                    'codigojbg' =>
                        $productoV2['codigo_auxiliar'] ?? null,

                    'codigo' =>
                        $productoV2['codigo'] ?? null,

                    'nombre' =>
                        $productoV2['nombre'] ?? null,

                    'categoria_id' =>
                        $productoV2['categoria_id'] ?? null,

                    'categoria' =>
                        null,

                    'marca_id' =>
                        $productoV2['marca_id'] ?? null,

                    'marca' =>
                        $productoV2['marca_nombre'] ?? null,

                    'codigo_barra' =>
                        $productoV2['codigo_barra'] ?? null,

                    'descripcion' =>
                        $productoV2['descripcion'] ?? null,

                    'estado' =>
                        $productoV2['estado'] ?? null,

                    'tipo' =>
                        $productoV2['tipo'] ?? null,

                    'tipo_producto' =>
                        $productoV2['tipo_producto'] ?? null,

                    'porcentaje_iva' =>
                        $productoV2['porcentaje_iva'] ?? null,

                    'costo_maximo' =>
                        $productoV2['costo_maximo'] ?? null,

                    'fecha_contifico' =>
                        !empty($productoV2['fecha_creacion'])
                            ? Carbon::parse(
                                $productoV2['fecha_creacion']
                            )
                            : null,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ];

                /*
                |--------------------------------------------------------------------------
                | 3.8 INSERTAR
                |--------------------------------------------------------------------------
                */

                DB::table('productos')->insert(
                    $registro
                );

                /*
                |--------------------------------------------------------------------------
                | 3.9 REVALIDAR
                |--------------------------------------------------------------------------
                */

                $confirmacion =
                    DB::table('productos')
                        ->where(
                            'contifico_id',
                            $contificoId
                        )
                        ->first();

                if (
                    !$confirmacion ||
                    strtoupper(
                        trim(
                            (string)
                            $confirmacion->codigo
                        )
                    ) !== strtoupper($codigo)
                ) {

                    $this->error(
                        "  ERROR DE REVALIDACIÓN: {$codigo}"
                    );

                    $errores++;

                    continue;
                }

                $this->info(
                    "  OK: {$codigo} → {$contificoId}"
                );

                $recuperados++;

            } catch (\Throwable $e) {

                $this->error(
                    "  ERROR: {$e->getMessage()}"
                );

                $errores++;
            }

            $this->newLine();
        }

        /*
        |--------------------------------------------------------------------------
        | 4. RESUMEN
        |--------------------------------------------------------------------------
        */

        $this->info(
            '=========================================='
        );

        $this->info(
            ' RECUPERACIÓN FINALIZADA'
        );

        $this->info(
            '=========================================='
        );

        $this->info(
            'Solicitados: ' .
            $codigos->count()
        );

        $this->info(
            'Recuperados: ' .
            $recuperados
        );

        $this->info(
            'Ya existentes: ' .
            $yaExistian
        );

        $this->info(
            'No encontrados: ' .
            $noEncontrados
        );

        $this->info(
            'Conflictos: ' .
            $conflictos
        );

        $this->info(
            'Errores: ' .
            $errores
        );

        $this->info(
            '=========================================='
        );

        if (
            $noEncontrados > 0 ||
            $conflictos > 0 ||
            $errores > 0
        ) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}