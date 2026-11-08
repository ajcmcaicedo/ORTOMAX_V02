<?php

namespace App\Imports;

use App\Models\Producto;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class LotesValidator implements ToCollection
{
    public function __construct(
        protected ValidadorOrtomaImport $validador
    ) {
    }

    public function collection(Collection $rows)
    {
        /*
         * Cargamos los códigos de productos existentes
         * en MySQL una sola vez.
         */
        $productos = Producto::query()
            ->whereNotNull('codigo')
            ->pluck('id', 'codigo')
            ->mapWithKeys(function ($id, $codigo) {
                return [
                    strtoupper(trim((string) $codigo)) => $id
                ];
            })
            ->all();

        /*
         * Para detectar lotes duplicados dentro del Excel.
         */
        $lotesExcel = [];

        foreach ($rows as $index => $row) {

            /*
             * Primera fila = encabezados.
             */
            if ($index === 0) {
                continue;
            }

            $fila = $index + 1;

            /*
             * Lectura de columnas.
             */
            $codigoProducto = trim((string) ($row[0] ?? ''));
            $numeroLote = trim((string) ($row[1] ?? ''));
            $fechaVencimiento = trim((string) ($row[2] ?? ''));
            $fechaFabricacion = trim((string) ($row[3] ?? ''));
            $registroSanitario = trim((string) ($row[4] ?? ''));
            $estado = strtoupper(trim((string) ($row[5] ?? '')));

            /*
             * Si toda la fila está vacía, la ignoramos.
             */
            if (
                $codigoProducto === '' &&
                $numeroLote === '' &&
                $fechaVencimiento === '' &&
                $fechaFabricacion === '' &&
                $registroSanitario === '' &&
                $estado === ''
            ) {
                continue;
            }

            /*
             * Código de producto obligatorio.
             */
            if ($codigoProducto === '') {

                $this->validador->error(
                    'LOTES',
                    $fila,
                    'codigo_producto',
                    'El código del producto es obligatorio.'
                );

                continue;
            }

            $codigoProductoBusqueda = strtoupper($codigoProducto);

            /*
             * El producto debe existir en MySQL.
             */
            if (!isset($productos[$codigoProductoBusqueda])) {

                $this->validador->error(
                    'LOTES',
                    $fila,
                    'codigo_producto',
                    "El producto '$codigoProducto' no existe en la tabla productos de MySQL."
                );

                continue;
            }

            $productoId = $productos[$codigoProductoBusqueda];

            /*
             * Número de lote obligatorio.
             */
            if ($numeroLote === '') {

                $this->validador->error(
                    'LOTES',
                    $fila,
                    'numero_lote',
                    'El número de lote es obligatorio.'
                );

                continue;
            }

            /*
             * El número de lote se trata siempre como TEXTO.
             *
             * Esto permite valores como:
             * 2400018645
             * D180400701
             * K200400304
             * H2104250
             */
            $numeroLoteBusqueda = strtoupper($numeroLote);

            /*
             * Clave única dentro del Excel:
             *
             * producto + lote
             */
            $clave = $codigoProductoBusqueda . '|' . $numeroLoteBusqueda;

            if (isset($lotesExcel[$clave])) {

                $this->validador->error(
                    'LOTES',
                    $fila,
                    'numero_lote',
                    "El lote '$numeroLote' está duplicado para el producto '$codigoProducto'."
                );

            } else {

                $lotesExcel[$clave] = $fila;
            }

            /*
             * Estado.
             */
            if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {

                $this->validador->error(
                    'LOTES',
                    $fila,
                    'estado',
                    "Estado inválido '$estado'. Use ACTIVO o INACTIVO."
                );
            }

            /*
             * Guardamos el lote en memoria.
             *
             * Todavía NO se modifica MySQL.
             */
            $this->validador->lotes[$clave] = [
                'codigo_producto' => $codigoProducto,
                'producto_id' => $productoId,
                'numero_lote' => $numeroLote,
                'fecha_vencimiento' => $fechaVencimiento,
                'fecha_fabricacion' => $fechaFabricacion,
                'registro_sanitario' => $registroSanitario,
                'estado' => $estado,
                'fila' => $fila,
            ];
        }
    }
}