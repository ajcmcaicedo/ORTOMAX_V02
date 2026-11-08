<?php

namespace App\Imports;

use App\Models\Producto;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProductosValidator implements ToCollection
{
    public function __construct(
        protected ValidadorOrtomaImport $validador
    ) {
    }

    public function collection(Collection $rows)
    {
        /*
         * Cargamos una sola vez los códigos existentes
         * en MySQL para evitar una consulta por cada fila.
         */
        $codigosExistentes = Producto::query()
        ->whereNotNull('codigo')
        ->pluck('codigo')
        ->mapWithKeys(function ($codigo) {
            return [strtoupper(trim((string) $codigo)) => true];
        })
        ->all();

        $codigosExcel = [];

        foreach ($rows as $index => $row) {

            // Primera fila = encabezados
            if ($index === 0) {
                continue;
            }

            $fila = $index + 1;

            $codigo = trim((string) ($row[0] ?? ''));
            $codigoBusqueda = strtoupper($codigo);
            $nombre = trim((string) ($row[1] ?? ''));
            $marca = trim((string) ($row[2] ?? ''));
            $familia = trim((string) ($row[3] ?? ''));
            $subfamilia = trim((string) ($row[4] ?? ''));
            $codigoBarra = trim((string) ($row[5] ?? ''));
            $tipoProducto = strtoupper(trim((string) ($row[6] ?? '')));
            $requiereLote = $row[7] ?? null;
            $requiereSerial = $row[8] ?? null;
            $estado = strtoupper(trim((string) ($row[9] ?? '')));

            /*
             * Si toda la fila está vacía, la ignoramos.
             */
            if (
                $codigo === '' &&
                $nombre === '' &&
                $marca === '' &&
                $familia === '' &&
                $subfamilia === '' &&
                $codigoBarra === '' &&
                $tipoProducto === '' &&
                $estado === ''
            ) {
                continue;
            }

            /*
             * Código obligatorio.
             */
            if ($codigo === '') {

                $this->validador->error(
                    'PRODUCTOS',
                    $fila,
                    'codigo',
                    'El código del producto es obligatorio.'
                );

                continue;
            }

            /*
             * Código duplicado dentro del Excel.
             */
            if (isset($codigosExcel[$codigo])) {

                $this->validador->error(
                    'PRODUCTOS',
                    $fila,
                    'codigo',
                    "El código '$codigo' está duplicado en el Excel."
                );

            } else {

                $codigosExcel[$codigo] = true;
            }

            /*
             * El producto debe existir previamente
             * en la tabla productos de MySQL.
             */
            if (
                $tipoProducto !== 'INSTRUMENTAL' &&
                !isset($codigosExistentes[$codigoBusqueda])
            ) {
                $this->validador->error(
                    'PRODUCTOS',
                    $fila,
                    'codigo',
                    "El producto '$codigo' no existe en la tabla productos de MySQL."
                );
            }
            /*
             * Nombre obligatorio.
             */
            if ($nombre === '') {

                $this->validador->error(
                    'PRODUCTOS',
                    $fila,
                    'nombre',
                    'El nombre del producto es obligatorio.'
                );
            }

    /*
             * Tipo de producto.
           
            if (
                $tipoProducto !== '' &&
                !in_array(
                    $tipoProducto,
                    ['IMPLANTE', 'INSTRUMENTAL', 'ACTIVO'],
                    true
                )
            ) {

                $this->validador->error(
                    'PRODUCTOS',
                    $fila,
                    'tipo_producto',
                    "Tipo de producto inválido '$tipoProducto'."
                );
            }
    */
            /*
             * requiere_lote
             */
            if (
                $requiereLote !== null &&
                $requiereLote !== '' &&
                !in_array(
                    (string) $requiereLote,
                    ['0', '1'],
                    true
                )
            ) {

                $this->validador->error(
                    'PRODUCTOS',
                    $fila,
                    'requiere_lote',
                    "Valor inválido '$requiereLote'. Use 0 o 1."
                );
            }

            /*
             * requiere_serial
             */
            if (
                $requiereSerial !== null &&
                $requiereSerial !== '' &&
                !in_array(
                    (string) $requiereSerial,
                    ['0', '1'],
                    true
                )
            ) {

                $this->validador->error(
                    'PRODUCTOS',
                    $fila,
                    'requiere_serial',
                    "Valor inválido '$requiereSerial'. Use 0 o 1."
                );
            }

            /*
             * Estado.
             */
            if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {

                $this->validador->error(
                    'PRODUCTOS',
                    $fila,
                    'estado',
                    "Estado inválido '$estado'. Use ACTIVO o INACTIVO."
                );
            }

            /*
             * Guardamos la información validada en memoria.
             * Todavía NO se modifica MySQL.
             */
            $this->validador->productos[$codigo] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'marca' => $marca,
                'familia' => $familia,
                'subfamilia' => $subfamilia,
                'codigo_barra' => $codigoBarra,
                'tipo_producto' => $tipoProducto,
                'requiere_lote' => $requiereLote,
                'requiere_serial' => $requiereSerial,
                'estado' => $estado,
                'fila' => $fila,
            ];
        }
    }
}