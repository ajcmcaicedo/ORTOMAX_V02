<?php

namespace App\Console\Commands;

use App\Services\SiigoService;
use Illuminate\Console\Command;

class DiagnosticarProductoContifico extends Command
{
    protected $signature = 'contifico:diagnosticar-producto
                            {codigo : Código del producto a buscar}';

    protected $description = 'Busca un producto en todas las páginas de Contífico y detecta IDs duplicados';

    public function handle(SiigoService $siigo)
    {
        $codigoBuscado = trim($this->argument('codigo'));

        $this->info('==========================================');
        $this->info(' DIAGNÓSTICO DE PRODUCTO CONTÍFICO');
        $this->info('==========================================');
        $this->info("Código buscado: {$codigoBuscado}");
        $this->newLine();

        $aparicionesCodigo = [];
        $aparicionesId = [];

        $pagina = 1;

        do {

            $this->info("Consultando página {$pagina}...");

            $response = $siigo->obtenerProductosPagina($pagina);

            if ($response->failed()) {
                $this->error(
                    "Error HTTP en página {$pagina}: " .
                    $response->status()
                );

                return Command::FAILURE;
            }

            $data = $response->json();

            $productos = $data['results'] ?? [];

            foreach ($productos as $producto) {

                $id = $producto['id'] ?? null;
                $codigo = $producto['codigo'] ?? null;

                if ($id === null) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Guardar todas las apariciones del ID buscado
                |--------------------------------------------------------------------------
                */

                if ($id === 'Zxgep7zxrflLBd1p') {

                    $aparicionesId[] = [
                        'pagina' => $pagina,
                        'id' => $id,
                        'codigo' => $codigo,
                        'nombre' => $producto['nombre'] ?? null,
                        'codigo_auxiliar' =>
                            $producto['codigo_auxiliar'] ?? null,
                        'marca' =>
                            $producto['marca_nombre'] ?? null,
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | Guardar todas las apariciones del código buscado
                |--------------------------------------------------------------------------
                */

                if ($codigo === $codigoBuscado) {

                    $aparicionesCodigo[] = [
                        'pagina' => $pagina,
                        'id' => $id,
                        'codigo' => $codigo,
                        'nombre' => $producto['nombre'] ?? null,
                        'codigo_auxiliar' =>
                            $producto['codigo_auxiliar'] ?? null,
                        'marca' =>
                            $producto['marca_nombre'] ?? null,
                    ];
                }
            }

            $next = $data['next'] ?? null;

            if ($next !== null) {
                $pagina++;
            }

        } while ($next !== null);

        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | RESULTADO DEL CÓDIGO
        |--------------------------------------------------------------------------
        */

        $this->info('------------------------------------------');
        $this->info('RESULTADO DEL CÓDIGO');
        $this->info('------------------------------------------');

        if (empty($aparicionesCodigo)) {

            $this->warn(
                "No se encontró el código {$codigoBuscado}."
            );

        } else {

            foreach ($aparicionesCodigo as $item) {

                $this->line(
                    "Página: {$item['pagina']}"
                );

                $this->line(
                    "ID: {$item['id']}"
                );

                $this->line(
                    "Código: {$item['codigo']}"
                );

                $this->line(
                    "Nombre: {$item['nombre']}"
                );

                $this->line(
                    "Código auxiliar: {$item['codigo_auxiliar']}"
                );

                $this->line(
                    "Marca: {$item['marca']}"
                );

                $this->newLine();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | RESULTADO DEL ID
        |--------------------------------------------------------------------------
        */

        $this->info('------------------------------------------');
        $this->info('APARICIONES DEL ID Zxgep7zxrflLBd1p');
        $this->info('------------------------------------------');

        if (empty($aparicionesId)) {

            $this->warn(
                'El ID Zxgep7zxrflLBd1p no apareció.'
            );

        } else {

            foreach ($aparicionesId as $item) {

                $this->line(
                    "Página: {$item['pagina']}"
                );

                $this->line(
                    "ID: {$item['id']}"
                );

                $this->line(
                    "Código: {$item['codigo']}"
                );

                $this->line(
                    "Nombre: {$item['nombre']}"
                );

                $this->line(
                    "Código auxiliar: {$item['codigo_auxiliar']}"
                );

                $this->line(
                    "Marca: {$item['marca']}"
                );

                $this->newLine();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | RESUMEN
        |--------------------------------------------------------------------------
        */

        $this->info('==========================================');
        $this->info(' RESUMEN');
        $this->info('==========================================');

        $this->info(
            'Apariciones del código: ' .
            count($aparicionesCodigo)
        );

        $this->info(
            'Apariciones del ID: ' .
            count($aparicionesId)
        );

        $this->info(
            'Última página consultada: ' .
            $pagina
        );

        $this->info('==========================================');

        return Command::SUCCESS;
    }
}