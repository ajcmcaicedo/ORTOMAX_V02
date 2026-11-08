<?php

namespace App\Services\Importaciones\Validadores;

class ValidadorDuplicados
{
    /**
     * Detecta duplicados dentro de las filas del archivo Excel.
     *
     * Reglas:
     *
     * 1. Si existe NUMERO_LOTE:
     *    REGIONAL + CODIGO + PRODUCTO + NUMERO_LOTE
     *
     * 2. Si no existe lote pero existe SERIE:
     *    REGIONAL + CODIGO + PRODUCTO + SERIE
     *
     * 3. Si no existe lote ni serie:
     *    REGIONAL + CODIGO + PRODUCTO
     */
    public function validar(array $filas): array
    {
        $claves = [];
        $duplicados = [];

        foreach ($filas as $fila) {

            $regional = $this->normalizar($fila['REGIONAL'] ?? '');
            $codigo = $this->normalizar($fila['CODIGO'] ?? '');
            $producto = $this->normalizar($fila['PRODUCTO'] ?? '');
            $lote = $this->normalizar($fila['NUMERO_LOTE'] ?? '');
            $serie = $this->normalizar($fila['SERIE'] ?? '');

            /*
             * Determinar tipo de clave.
             */
            if ($lote !== '') {

                $tipoClave = 'LOTE';

                $clave = implode('|', [
                    $regional,
                    $codigo,
                    $producto,
                    $lote,
                ]);

            } elseif ($serie !== '') {

                $tipoClave = 'SERIE';

                $clave = implode('|', [
                    $regional,
                    $codigo,
                    $producto,
                    $serie,
                ]);

            } else {

                $tipoClave = 'SIN_LOTE_SERIE';

                $clave = implode('|', [
                    $regional,
                    $codigo,
                    $producto,
                ]);
            }

            $registro = [
                'fila_excel' => $fila['_numero_fila'] ?? null,
                'regional' => $regional,
                'codigo' => $codigo,
                'producto' => $producto,
                'numero_lote' => $lote,
                'serie' => $serie,
                'tipo_clave' => $tipoClave,
                'clave' => $clave,
                'cantidad' => $fila['CANTIDAD'] ?? null,
                'tipo' => $fila['TIPO'] ?? null,
                'descripcion' => $fila['DESCRIPCION'] ?? null,
            ];

            $claves[$clave][] = $registro;
        }

        /*
         * Seleccionar únicamente las claves
         * que aparecen más de una vez.
         */
        foreach ($claves as $clave => $registros) {

            if (count($registros) > 1) {

                $duplicados[] = [
                    'clave' => $clave,
                    'tipo_clave' => $registros[0]['tipo_clave'],
                    'repeticiones' => count($registros),
                    'registros' => $registros,
                ];
            }
        }

        return [
            'total_claves' => count($claves),
            'total_duplicados' => count($duplicados),
            'duplicados' => $duplicados,
        ];
    }

    /**
     * Normalización únicamente para comparar.
     *
     * IMPORTANTE:
     * No modifica el valor original del Excel.
     */
    private function normalizar(mixed $valor): string
    {
        if ($valor === null) {
            return '';
        }

        $valor = (string) $valor;

        $valor = str_replace(
            ["\xC2\xA0", "\xE2\x80\xAF"],
            ' ',
            $valor
        );

        $valor = preg_replace('/\s+/u', ' ', $valor);

        return strtoupper(trim($valor));
    }
}