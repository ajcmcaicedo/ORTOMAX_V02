<?php

namespace App\Console\Commands;

use App\Services\SiigoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncBodegas extends Command
{
    protected $signature = 'contifico:sync-bodegas';

    protected $description = 'Sincroniza las bodegas de Contífico con ORTOMAX AI';

    public function handle(SiigoService $siigo)
    {
        $this->info('==========================================');
        $this->info(' SINCRONIZACIÓN DE BODEGAS CONTÍFICO');
        $this->info('==========================================');
        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | 1. CONSULTAR BODEGAS DE CONTÍFICO
        |--------------------------------------------------------------------------
        */

        $this->info('Consultando bodegas de Contífico...');

        $response = $siigo->obtenerBodegas();

        if ($response->failed()) {

            $this->error(
                'Error HTTP: ' . $response->status()
            );

            $this->error(
                $response->body()
            );

            return Command::FAILURE;
        }

        $data = $response->json();

        $bodegas = $data['results'] ?? [];

        $cantidad = count($bodegas);

        $this->info(
            "Bodegas recibidas: {$cantidad}"
        );

        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | 2. SINCRONIZAR BODEGAS
        |--------------------------------------------------------------------------
        */

        $totalProcesados = 0;

        $totalNuevas = 0;

        $totalActualizadas = 0;

        foreach ($bodegas as $bodega) {

            /*
            |--------------------------------------------------------------------------
            | VALIDAR ID DE CONTÍFICO
            |--------------------------------------------------------------------------
            */

            if (empty($bodega['id'])) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | DATOS
            |--------------------------------------------------------------------------
            */

            $registro = [

                'regional_id' => 5,

                'codigo' =>
                    $bodega['codigo'] ?? null,

                'nombre' =>
                    $bodega['nombre'] ?? null,

                'tipo_bodega' =>
                    'SECUNDARIA',

                'direccion' =>
                    null,

                'telefono' =>
                    null,

                'activo' =>
                    1,

                'contifico_id' =>
                    $bodega['id'],

                'contifico_venta' =>
                    $bodega['venta'] ?? null,

                'contifico_produccion' =>
                    $bodega['produccion'] ?? null,

                'contifico_compra' =>
                    $bodega['compra'] ?? null,

                'updated_at' =>
                    now(),
            ];

            /*
            |--------------------------------------------------------------------------
            | COMPROBAR SI YA EXISTE
            |--------------------------------------------------------------------------
            */

            $existente = DB::table('bodegas')
                ->where('contifico_id', $bodega['id'])
                ->first();

            /*
            |--------------------------------------------------------------------------
            | ACTUALIZAR
            |--------------------------------------------------------------------------
            */

            if ($existente) {

                DB::table('bodegas')
                    ->where('contifico_id', $bodega['id'])
                    ->update($registro);

                $totalActualizadas++;

            }

            /*
            |--------------------------------------------------------------------------
            | CREAR
            |--------------------------------------------------------------------------
            */

            else {

                $registro['created_at'] = now();

                DB::table('bodegas')
                    ->insert($registro);

                $totalNuevas++;
            }

            $totalProcesados++;

            $this->line(
                '✓ ' .
                ($bodega['codigo'] ?? '') .
                ' - ' .
                ($bodega['nombre'] ?? '')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 3. RESULTADO
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info('==========================================');

        $this->info(
            ' SINCRONIZACIÓN FINALIZADA'
        );

        $this->info(
            ' Bodegas procesadas: ' .
            $totalProcesados
        );

        $this->info(
            ' Bodegas nuevas: ' .
            $totalNuevas
        );

        $this->info(
            ' Bodegas actualizadas: ' .
            $totalActualizadas
        );

        $this->info('==========================================');

        return Command::SUCCESS;
    }
}