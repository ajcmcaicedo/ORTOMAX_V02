<?php

namespace App\Console\Commands;

use App\Services\SiigoService;
use App\Services\Importaciones\Productos\SincronizadorProductoContifico;
use Illuminate\Console\Command;

class SyncProductos extends Command
{
    protected $signature = 'contifico:sync-productos';

    protected $description =
        'Sincroniza los productos de Contífico con ORTOMAX AI';

    public function handle(
        SiigoService $siigo,
        SincronizadorProductoContifico $sincronizador
    ) {
        $this->info('==========================================');
        $this->info(' SINCRONIZACIÓN DE PRODUCTOS CONTÍFICO');
        $this->info('==========================================');
        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | 1. CARGAR CATEGORÍAS DE CONTÍFICO
        |--------------------------------------------------------------------------
        */

        $this->info('Cargando categorías de Contífico...');

        $categorias = [];

        $paginaCategoria = 1;

        do {

            $this->info(
                "Consultando categorías página {$paginaCategoria}..."
            );

            $responseCategoria =
                $siigo->obtenerCategoriasPagina($paginaCategoria);

            if ($responseCategoria->failed()) {

                $this->error(
                    'Error HTTP categorías: ' .
                    $responseCategoria->status()
                );

                $this->error(
                    $responseCategoria->body()
                );

                return Command::FAILURE;
            }

            $dataCategoria =
                $responseCategoria->json();

            $listaCategorias =
                $dataCategoria['results'] ?? [];

            foreach ($listaCategorias as $categoria) {

                if (empty($categoria['id'])) {
                    continue;
                }

                $categorias[$categoria['id']] =
                    $categoria['nombre'] ?? null;
            }

            $nextCategoria =
                $dataCategoria['next'] ?? null;

            if ($nextCategoria !== null) {
                $paginaCategoria++;
            }

        } while ($nextCategoria !== null);

        $this->info(
            'Categorías cargadas: ' .
            count($categorias)
        );

        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | 2. ESTADÍSTICAS
        |--------------------------------------------------------------------------
        */

        $totalProcesados = 0;
        $totalCreados = 0;
        $totalVinculados = 0;
        $totalActualizados = 0;
        $totalSinCambios = 0;
        $totalConflictos = 0;
        $totalErrores = 0;

        /*
        |--------------------------------------------------------------------------
        | 3. SINCRONIZAR PRODUCTOS V2
        |--------------------------------------------------------------------------
        */

        $pagina = 1;

        do {

            $this->info(
                "Consultando página de productos {$pagina}..."
            );

            $response =
                $siigo->obtenerProductosPagina($pagina);

            if ($response->failed()) {

                $this->error(
                    'Error HTTP productos: ' .
                    $response->status()
                );

                $this->error(
                    $response->body()
                );

                return Command::FAILURE;
            }

            $data =
                $response->json();

            $productos =
                $data['results'] ?? [];

            $cantidad =
                count($productos);

            $this->info(
                "Productos recibidos: {$cantidad}"
            );

            /*
            |--------------------------------------------------------------------------
            | 3.1 PROCESAR PRODUCTOS
            |--------------------------------------------------------------------------
            */

            foreach ($productos as $producto) {

                $totalProcesados++;

                /*
                |--------------------------------------------------------------------------
                | VALIDAR ID
                |--------------------------------------------------------------------------
                */

                if (empty($producto['id'])) {

                    $this->warn(
                        "Producto sin ID en página {$pagina}. Se omite."
                    );

                    $totalErrores++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | OBTENER CATEGORÍA
                |--------------------------------------------------------------------------
                */

                $categoriaId =
                    $producto['categoria_id'] ?? null;

                $categoriaNombre =
                    $categoriaId !== null
                        ? ($categorias[$categoriaId] ?? null)
                        : null;

                /*
                |--------------------------------------------------------------------------
                | SINCRONIZAR
                |--------------------------------------------------------------------------
                */

                try {

                    $resultado =
                        $sincronizador->sincronizar(
                            $producto,
                            $categoriaNombre
                        );

                    $tipoResultado =
                        $resultado['resultado'] ?? 'ERROR';

                    switch ($tipoResultado) {

                        case 'CREADO':

                            $totalCreados++;

                            $this->info(
                                "  [CREADO] " .
                                ($resultado['codigo'] ?? 'SIN-CODIGO')
                            );

                            break;

                        case 'VINCULADO':

                            $totalVinculados++;

                            $this->info(
                                "  [VINCULADO] " .
                                ($resultado['codigo'] ?? 'SIN-CODIGO')
                            );

                            break;

                        case 'ACTUALIZADO':

                            $totalActualizados++;

                            $this->line(
                                "  [ACTUALIZADO] " .
                                ($resultado['codigo'] ?? 'SIN-CODIGO')
                            );

                            break;

                        case 'SIN_CAMBIOS':

                            $totalSinCambios++;

                            break;

                        case 'CONFLICTO':

                            $totalConflictos++;

                            $this->error(
                                "  [CONFLICTO] " .
                                ($resultado['codigo'] ?? 'SIN-CODIGO') .
                                ' - ' .
                                ($resultado['mensaje'] ?? '')
                            );

                            break;

                        default:

                            $totalErrores++;

                            $this->error(
                                "  [ERROR] " .
                                ($resultado['codigo'] ?? 'SIN-CODIGO') .
                                ' - ' .
                                ($resultado['mensaje'] ?? 'Resultado desconocido')
                            );

                            break;
                    }

                } catch (\Throwable $e) {

                    $totalErrores++;

                    $codigo =
                        trim(
                            (string) (
                                $producto['codigo'] ?? ''
                            )
                        );

                    $this->error(
                        "  [ERROR] {$codigo}: " .
                        $e->getMessage()
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 3.2 SIGUIENTE PÁGINA
            |--------------------------------------------------------------------------
            */

            $next =
                $data['next'] ?? null;

            if ($next !== null) {
                $pagina++;
            }

        } while ($next !== null);

        /*
        |--------------------------------------------------------------------------
        | 4. RESUMEN FINAL
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info('==========================================');
        $this->info(' SINCRONIZACIÓN FINALIZADA');
        $this->info('==========================================');

        $this->info(
            'Categorías cargadas: ' .
            count($categorias)
        );

        $this->info(
            'Productos procesados: ' .
            $totalProcesados
        );

        $this->newLine();

        $this->info(
            'Nuevos creados: ' .
            $totalCreados
        );

        $this->info(
            'Temporales vinculados: ' .
            $totalVinculados
        );

        $this->info(
            'Actualizados: ' .
            $totalActualizados
        );

        $this->info(
            'Sin cambios: ' .
            $totalSinCambios
        );

        $this->warn(
            'Conflictos: ' .
            $totalConflictos
        );

        $this->error(
            'Errores: ' .
            $totalErrores
        );

        $this->newLine();

        $this->info('==========================================');

        if ($totalConflictos > 0 || $totalErrores > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}