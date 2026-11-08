<?php

namespace App\Services\Importaciones\Productos;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SincronizadorProductoContifico
{
    /**
     * Campos maestros administrados por Cont�fico.
     *
     * IMPORTANTE:
     * No incluye codigojbg ni los campos propios de ORTOMAX.
     */
    private function mapearDatosContifico(
        array $producto,
        ?string $categoriaNombre = null
    ): array {
        $categoriaId = $producto['categoria_id'] ?? null;

        return [
            'contifico_id' => $producto['id'] ?? null,

            'codigo' => $producto['codigo'] ?? null,

            'nombre' => $producto['nombre'] ?? null,

            'categoria_id' => $categoriaId,

            'categoria' => $categoriaNombre,

            'marca_id' => $producto['marca_id'] ?? null,

            'marca' => $producto['marca_nombre'] ?? null,

            'codigo_barra' => $producto['codigo_barra'] ?? null,

            'descripcion' => $producto['descripcion'] ?? null,

            'estado' => $producto['estado'] ?? null,

            'tipo' => $producto['tipo'] ?? null,

            'tipo_producto' => $producto['tipo_producto'] ?? null,

            'porcentaje_iva' => $producto['porcentaje_iva'] ?? null,

            'costo_maximo' => $producto['costo_maximo'] ?? null,

            'fecha_contifico' => !empty($producto['fecha_creacion'])
                ? Carbon::parse($producto['fecha_creacion'])
                : null,

            'origen_producto' => 'CONTIFICO',

            'estado_contifico' => 'SINCRONIZADO',

            'updated_at' => now(),
        ];
    }

    /**
     * Sincroniza un �nico producto de Cont�fico con ORTOMAX.
     *
     * Devuelve:
     *
     * CREADO
     * VINCULADO
     * ACTUALIZADO
     * SIN_CAMBIOS
     * CONFLICTO
     */
    public function sincronizar(
        array $producto,
        ?string $categoriaNombre = null
    ): array {
        $contificoId = trim(
            (string) ($producto['id'] ?? '')
        );

        $codigo = trim(
            (string) ($producto['codigo'] ?? '')
        );

        if ($contificoId === '') {
            return [
                'resultado' => 'ERROR',
                'codigo' => $codigo,
                'mensaje' => 'Cont�fico no entreg� ID.',
            ];
        }

        if ($codigo === '') {
            return [
                'resultado' => 'ERROR',
                'codigo' => null,
                'mensaje' => 'Cont�fico no entreg� c�digo.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Buscar por c�digo
        |--------------------------------------------------------------------------
        */

        $existentePorCodigo = DB::table('productos')
            ->where('codigo', $codigo)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | 2. Buscar por contifico_id
        |--------------------------------------------------------------------------
        */

        $existentePorContifico = DB::table('productos')
            ->where('contifico_id', $contificoId)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | 3. Detectar conflicto de identidad
        |--------------------------------------------------------------------------
        */

        if (
            $existentePorCodigo &&
            $existentePorContifico &&
            $existentePorCodigo->id !== $existentePorContifico->id
        ) {
            return [
                'resultado' => 'CONFLICTO',
                'codigo' => $codigo,
                'contifico_id' => $contificoId,
                'mensaje' =>
                    'El c�digo y el contifico_id pertenecen a registros diferentes en ORTOMAX.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Si el contifico_id ya pertenece a otro c�digo
        |--------------------------------------------------------------------------
        */

        if ($existentePorContifico) {

            $codigoExistente = trim(
                (string) ($existentePorContifico->codigo ?? '')
            );

            if (
                $codigoExistente !== '' &&
                strtoupper($codigoExistente) !== strtoupper($codigo)
            ) {
                return [
                    'resultado' => 'CONFLICTO',
                    'codigo' => $codigo,
                    'contifico_id' => $contificoId,
                    'mensaje' =>
                        "El contifico_id {$contificoId} ya pertenece al c�digo {$codigoExistente}.",
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Datos maestros provenientes de Cont�fico
        |--------------------------------------------------------------------------
        */

        $datos = $this->mapearDatosContifico(
            $producto,
            $categoriaNombre
        );
        
        /*
        |--------------------------------------------------------------------------
        | PROTEGER CATEGORÍA SI CONTÍFICO ENTREGÓ categoria_id
        | PERO NO TENEMOS EL NOMBRE EN EL MAPA
        |--------------------------------------------------------------------------
        |
        | Nunca debemos borrar una categoría existente solamente porque
        | el catálogo de categorías no fue cargado o no encontró el ID.
        |
        */
        
        $categoriaId = $producto['categoria_id'] ?? null;
        
        if (
            $categoriaId !== null &&
            $categoriaNombre === null
        ) {
            unset($datos['categoria']);
        }

        /*
        |--------------------------------------------------------------------------
        | 6. PRODUCTO NO EXISTE
        |--------------------------------------------------------------------------
        */

        if (!$existentePorCodigo && !$existentePorContifico) {

            $id = DB::table('productos')->insertGetId([
                ...$datos,
                'created_at' => now(),
            ]);

            return [
                'resultado' => 'CREADO',
                'id' => $id,
                'codigo' => $codigo,
                'contifico_id' => $contificoId,
                'mensaje' => 'Producto creado desde Cont�fico.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Determinar registro existente
        |--------------------------------------------------------------------------
        */

        $existente = $existentePorCodigo ?? $existentePorContifico;

        /*
        |--------------------------------------------------------------------------
        | 8. Verificar si ya est� vinculado correctamente
        |--------------------------------------------------------------------------
        */

        $existenteContificoId = trim(
            (string) ($existente->contifico_id ?? '')
        );

        /*
        |--------------------------------------------------------------------------
        | 9. PRODUCTO TEMPORAL DE EXCEL
        |--------------------------------------------------------------------------
        |
        | codigo existe
        | contifico_id est� vac�o
        |
        | Lo convertimos en producto oficial de Cont�fico.
        |--------------------------------------------------------------------------
        */

        if ($existenteContificoId === '') {

            DB::table('productos')
                ->where('id', $existente->id)
                ->update($datos);

            return [
                'resultado' => 'VINCULADO',
                'id' => $existente->id,
                'codigo' => $codigo,
                'contifico_id' => $contificoId,
                'mensaje' =>
                    'Producto temporal vinculado posteriormente con Cont�fico.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Producto ya sincronizado
        |--------------------------------------------------------------------------
        */

        if ($existenteContificoId !== $contificoId) {

            return [
                'resultado' => 'CONFLICTO',
                'id' => $existente->id,
                'codigo' => $codigo,
                'contifico_id' => $contificoId,
                'mensaje' =>
                    "El c�digo {$codigo} ya est� vinculado al contifico_id {$existenteContificoId}.",
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Comparar datos antes de actualizar
        |--------------------------------------------------------------------------
        */

        $camposActualizables = [
            'nombre',
            'categoria_id',
            'categoria',
            'marca_id',
            'marca',
            'codigo_barra',
            'descripcion',
            'estado',
            'tipo',
            'tipo_producto',
            'porcentaje_iva',
            'costo_maximo',
            'fecha_contifico',
            'origen_producto',
            'estado_contifico',
        ];

        $cambios = [];

        foreach ($camposActualizables as $campo) {

            $nuevo = $datos[$campo] ?? null;
            $actual = $existente->{$campo} ?? null;

            $sonIguales = false;

            if (
                in_array($campo, [
                    'porcentaje_iva',
                    'costo_maximo',
                ], true)
            ) {
                $sonIguales =
                    ($actual === null && $nuevo === null)
                    || (
                        $actual !== null
                        && $nuevo !== null
                        && (float) $actual === (float) $nuevo
                    );
            } else {
                $sonIguales =
                    (string) $actual === (string) $nuevo;
            }
            
            if (!$sonIguales) {
                $cambios[$campo] = [
                    'anterior' => $actual,
                    'nuevo' => $nuevo,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 12. Sin cambios
        |--------------------------------------------------------------------------
        */

        if (empty($cambios)) {

            return [
                'resultado' => 'SIN_CAMBIOS',
                'id' => $existente->id,
                'codigo' => $codigo,
                'contifico_id' => $contificoId,
                'mensaje' => 'El producto ya est� actualizado.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 13. Actualizar producto existente
        |--------------------------------------------------------------------------
        */

        DB::table('productos')
            ->where('id', $existente->id)
            ->update($datos);

        return [
            'resultado' => 'ACTUALIZADO',
            'id' => $existente->id,
            'codigo' => $codigo,
            'contifico_id' => $contificoId,
            'cambios' => $cambios,
            'mensaje' => 'Producto actualizado desde Cont�fico.',
        ];
    }
}