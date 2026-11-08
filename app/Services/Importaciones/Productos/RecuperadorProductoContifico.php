<?php

namespace App\Services\Importaciones\Productos;

use App\Services\SiigoService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Throwable;

class RecuperadorProductoContifico
{
    public function __construct(
        protected SiigoService $siigo
    ) {
    }

    /**
     * Recupera un producto desde Contífico utilizando:
     *
     * V1 → localizar producto por código
     * V2 → obtener información completa
     *
     * No crea registros automáticamente si existe un conflicto.
     */
    public function recuperar(string $codigo): array
    {
        $codigo = trim($codigo);

        if ($codigo === '') {
            return [
                'ok' => false,
                'estado' => 'ERROR',
                'codigo' => $codigo,
                'mensaje' => 'El código está vacío.',
            ];
        }

        try {

            /*
             * ==========================================================
             * 1. VERIFICAR SI YA EXISTE EN ORTOMAX POR CÓDIGO
             * ==========================================================
             */
            $existentePorCodigo = DB::table('productos')
                ->where('codigo', $codigo)
                ->first();

            if ($existentePorCodigo) {

                return [
                    'ok' => true,
                    'estado' => 'YA_EXISTE',
                    'codigo' => $codigo,
                    'contifico_id' => $existentePorCodigo->contifico_id,
                    'producto_id' => $existentePorCodigo->id,
                    'mensaje' => 'El producto ya existe en ORTOMAX.',
                ];
            }

            /*
             * ==========================================================
             * 2. BUSCAR EN CONTÍFICO MEDIANTE V1
             * ==========================================================
             */
            $productoV1 = $this->siigo->obtenerProductoPorCodigoV1($codigo);

            if (!$productoV1 || empty($productoV1['id'])) {

                return [
                    'ok' => false,
                    'estado' => 'NO_ENCONTRADO',
                    'codigo' => $codigo,
                    'contifico_id' => null,
                    'mensaje' => 'El producto no fue encontrado en Contífico V1.',
                ];
            }

            $contificoId = trim((string) $productoV1['id']);

            /*
             * ==========================================================
             * 3. VERIFICAR CONFLICTO POR CONTIFICO_ID
             * ==========================================================
             */
            $existentePorContifico = DB::table('productos')
                ->where('contifico_id', $contificoId)
                ->first();

            if ($existentePorContifico) {

                return [
                    'ok' => false,
                    'estado' => 'CONFLICTO',
                    'codigo' => $codigo,
                    'contifico_id' => $contificoId,
                    'producto_id' => $existentePorContifico->id,
                    'mensaje' =>
                        'El contifico_id ya pertenece a otro producto en ORTOMAX.',
                ];
            }

            /*
             * ==========================================================
             * 4. OBTENER PRODUCTO COMPLETO MEDIANTE V2
             * ==========================================================
             */
            $productoV2 = $this->siigo->obtenerProducto($contificoId);

            if (!$productoV2) {

                return [
                    'ok' => false,
                    'estado' => 'ERROR_V2',
                    'codigo' => $codigo,
                    'contifico_id' => $contificoId,
                    'mensaje' =>
                        'Contífico V2 no devolvió información para el producto.',
                ];
            }

            /*
             * ==========================================================
             * 5. VALIDAR QUE EL CÓDIGO SEA EL MISMO
             * ==========================================================
             */
            $codigoV2 = trim((string) ($productoV2['codigo'] ?? ''));

            if (strtoupper($codigoV2) !== strtoupper($codigo)) {

                return [
                    'ok' => false,
                    'estado' => 'CONFLICTO',
                    'codigo' => $codigo,
                    'codigo_contifico' => $codigoV2,
                    'contifico_id' => $contificoId,
                    'mensaje' =>
                        'El código recibido desde Contífico V2 no coincide.',
                ];
            }

            /*
             * ==========================================================
             * 6. PREPARAR DATOS
             * ==========================================================
             */
            $categoriaId = $productoV2['categoria_id']
                ?? $productoV1['categoria_id']
                ?? null;

            /*
             * Intentamos recuperar el nombre de categoría desde ORTOMAX
             * utilizando productos existentes de la misma categoría.
             *
             * Esto evita consultar nuevamente las 392 categorías
             * de Contífico.
             */
            $categoriaNombre = null;

            if ($categoriaId) {

                $categoriaNombre = DB::table('productos')
                    ->where('categoria_id', $categoriaId)
                    ->whereNotNull('categoria')
                    ->where('categoria', '<>', '')
                    ->value('categoria');
            }

            /*
             * Si Contífico entrega directamente el nombre de categoría,
             * lo utilizamos como respaldo.
             */
            if (!$categoriaNombre) {

                $categoriaNombre =
                    $productoV2['categoria']
                    ?? $productoV1['categoria']
                    ?? $productoV2['categoria_nombre']
                    ?? $productoV1['categoria_nombre']
                    ?? null;
            }

            /*
             * Fecha de Contífico.
             */
            $fechaContifico = null;

            $fechaOrigen =
                $productoV2['fecha_creacion']
                ?? $productoV2['fecha']
                ?? $productoV1['fecha_creacion']
                ?? $productoV1['fecha']
                ?? null;

            if ($fechaOrigen) {

                try {
                    $fechaContifico = Carbon::parse($fechaOrigen);
                } catch (Throwable) {
                    $fechaContifico = null;
                }
            }

            /*
             * ==========================================================
             * 7. CONSTRUIR REGISTRO
             * ==========================================================
             */
            $registro = [
                'contifico_id' => $contificoId,

                'codigojbg' =>
                    $productoV2['descripcion']
                    ?? $productoV1['descripcion']
                    ?? null,

                'codigo' => $codigo,

                'categoria_id' => $categoriaId,

                'categoria' => $categoriaNombre,

                'marca_id' =>
                    $productoV2['marca_id']
                    ?? $productoV1['marca_id']
                    ?? null,

                'marca' =>
                    $productoV2['marca']
                    ?? $productoV1['marca']
                    ?? null,

                'codigo_barra' =>
                    $productoV2['codigo_barra']
                    ?? $productoV1['codigo_barra']
                    ?? null,

                'descripcion' =>
                    $productoV2['descripcion']
                    ?? $productoV1['descripcion']
                    ?? null,

                'nombre' =>
                    $productoV2['nombre']
                    ?? $productoV1['nombre']
                    ?? null,

                'estado' =>
                    $productoV2['estado']
                    ?? $productoV1['estado']
                    ?? null,

                'tipo' =>
                    $productoV2['tipo']
                    ?? $productoV1['tipo']
                    ?? null,

                'tipo_producto' =>
                    $productoV2['tipo_producto']
                    ?? $productoV1['tipo_producto']
                    ?? null,

                'porcentaje_iva' =>
                    $productoV2['porcentaje_iva']
                    ?? $productoV1['porcentaje_iva']
                    ?? null,

                'costo_maximo' =>
                    $productoV2['costo_maximo']
                    ?? $productoV1['costo_maximo']
                    ?? null,

                'fecha_contifico' => $fechaContifico,

                'created_at' => now(),
                'updated_at' => now(),
            ];

            /*
             * ==========================================================
             * 8. INSERTAR EN ORTOMAX
             * ==========================================================
             */
            $productoId = DB::table('productos')->insertGetId($registro);

            /*
             * ==========================================================
             * 9. REVALIDAR DESPUÉS DE INSERTAR
             * ==========================================================
             */
            $productoCreado = DB::table('productos')
                ->where('id', $productoId)
                ->first();

            if (!$productoCreado) {

                return [
                    'ok' => false,
                    'estado' => 'ERROR',
                    'codigo' => $codigo,
                    'contifico_id' => $contificoId,
                    'mensaje' =>
                        'El producto fue insertado pero no pudo ser revalidado.',
                ];
            }

            /*
             * Validación final de identidad.
             */
            if (
                trim((string) $productoCreado->codigo) !== $codigo
                ||
                trim((string) $productoCreado->contifico_id) !== $contificoId
            ) {

                return [
                    'ok' => false,
                    'estado' => 'ERROR',
                    'codigo' => $codigo,
                    'contifico_id' => $contificoId,
                    'producto_id' => $productoId,
                    'mensaje' =>
                        'La validación posterior a la creación no coincide.',
                ];
            }

            /*
             * ==========================================================
             * 10. RESULTADO EXITOSO
             * ==========================================================
             */
            return [
                'ok' => true,
                'estado' => 'RECUPERADO',
                'codigo' => $codigo,
                'contifico_id' => $contificoId,
                'producto_id' => $productoId,
                'categoria_id' => $categoriaId,
                'categoria' => $categoriaNombre,
                'mensaje' =>
                    'Producto recuperado correctamente desde Contífico.',
            ];

        } catch (Throwable $e) {

            return [
                'ok' => false,
                'estado' => 'ERROR',
                'codigo' => $codigo,
                'mensaje' => $e->getMessage(),
            ];
        }
    }

    /**
     * Recupera varios productos.
     */
    public function recuperarMuchos(array $codigos): array
    {
        $codigos = collect($codigos)
            ->map(fn ($codigo) => trim((string) $codigo))
            ->filter()
            ->unique()
            ->values();

        $resultados = [];

        foreach ($codigos as $codigo) {
            $resultados[] = $this->recuperar($codigo);
        }

        return $resultados;
    }
}