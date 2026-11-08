<?php

namespace App\Services\Importaciones;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Services\Importaciones\Perfiles\InstitucionesImportacion;
use App\Services\Importaciones\ResultadoImportacion;
use App\Services\Importaciones\Validadores\ValidadorDuplicados;
use RuntimeException;

class ImportadorExcel
{
    /**
     * Analiza un archivo Excel utilizando el perfil indicado.
     *
     * Este método NO modifica la base de datos.
     */
    public function analizar(string $rutaArchivo, PerfilImportacion $perfil): ResultadoImportacion
    {
        if (!file_exists($rutaArchivo)) {
            throw new RuntimeException(
                "No se encontró el archivo: {$rutaArchivo}"
            );
        }

        /*
         * Cargar archivo Excel.
         */
        $documento = IOFactory::load($rutaArchivo);

        $hoja = $documento->getActiveSheet();

        /*
         * Obtener dimensiones de la hoja.
         */
        $ultimaFila = $hoja->getHighestRow();
        $ultimaColumna = $hoja->getHighestColumn();

        $numeroUltimaColumna = Coordinate::columnIndexFromString(
            $ultimaColumna
        );

        /*
         * Leer encabezados.
         */
        $encabezados = [];

        for ($columna = 1; $columna <= $numeroUltimaColumna; $columna++) {

            $valor = $hoja->getCellByColumnAndRow(
                $columna,
                1
            )->getValue();

            $encabezado = $this->normalizarEncabezado($valor);

            if ($encabezado !== '') {
                $encabezados[$columna] = $encabezado;
            }
        }

        /*
         * Verificar columnas requeridas.
         */
        $columnasRequeridas = $perfil->columnasRequeridas();

        $columnasFaltantes = [];

        foreach ($columnasRequeridas as $columnaRequerida) {

            $normalizada = $this->normalizarEncabezado(
                $columnaRequerida
            );

            if (!in_array(
                $normalizada,
                array_values($encabezados),
                true
            )) {
                $columnasFaltantes[] = $columnaRequerida;
            }
        }

        if (!empty($columnasFaltantes)) {

            throw new RuntimeException(
                'Faltan columnas requeridas: '
                . implode(', ', $columnasFaltantes)
            );
        }

        /*
         * Resultado general del análisis.
         */
        $resultado = new ResultadoImportacion();

        /*
         * Procesar filas.
         */
        for ($filaExcel = 2; $filaExcel <= $ultimaFila; $filaExcel++) {

            $fila = [];

            foreach ($encabezados as $columna => $encabezado) {

                $celda = $hoja->getCellByColumnAndRow(
                    $columna,
                    $filaExcel
                );

                /*
                 * getFormattedValue permite conservar
                 * mejor los valores mostrados en Excel.
                 */
                $valor = $celda->getFormattedValue();

                $fila[$encabezado] = $this->textoExcel($valor);
            }

            /*
             * Ignorar filas completamente vacías.
             */
            if ($this->filaVacia($fila)) {
                continue;
            }

            /*
            * Normalizar fila mediante el perfil.
            */
            $fila = $perfil->normalizarFila($fila);

            /*
            * Conservar el número de fila original del Excel
            * para mostrarlo posteriormente en el análisis.
            */
            $fila['_numero_fila'] = $filaExcel;

            /*
            * Validar fila.
            */
            $errores = $perfil->validarFila(
                $fila,
                $filaExcel
            );

            /*
             * Si existen errores, registrar como ERROR.
             */
            if (!empty($errores)) {

                $resultado->agregarError(
                    $fila,
                    $errores
                );

                continue;
            }

            /*
             * Buscar registro existente.
             */
            $existente = $perfil->buscarExistente($fila);

            /*
             * Determinar operación.
             */
            $operacion = $perfil->determinarOperacion(
                $fila,
                $existente
            );

            switch ($operacion) {

                case ResultadoImportacion::NUEVO:

                    $resultado->agregarNuevo($fila);

                    break;

                    case ResultadoImportacion::ACTUALIZAR:

                        $cambios = $perfil->compararCambios(
                            $fila,
                            $existente
                        );
                    
                        if (empty($cambios)) {
                            $resultado->agregarSinCambios($fila);
                        } else {
                            $resultado->agregarActualizar(
                                $fila,
                                $cambios
                            );
                        }
                    
                        break;
                    
                    
                    case ResultadoImportacion::SIN_CAMBIOS:
                    
                        $resultado->agregarSinCambios($fila);
                    
                        break;
                    
                    
                    case ResultadoImportacion::ERROR:

                    $resultado->agregarError(
                        $fila,
                        [
                            "Operación desconocida: {$operacion}"
                        ]
                    );

                    break;
            }
        }

        return $resultado;
    }

    /**
     * Normaliza el nombre de una columna.
     *
     * Convierte:
     *
     * Razón Social_Cliente
     * ↓
     * razon social cliente
     *
     * Clínica_Hospital
     * ↓
     * clinica hospital
     *
     * Dirección
     * ↓
     * direccion
     */

     protected function normalizarEncabezado(mixed $valor): string
     {
         $valor = trim((string) $valor);
     
         /*
          * Detectar la codificación real del texto.
          *
          * Algunos archivos Excel pueden entregar textos
          * con caracteres como ó, í, é en Windows-1252
          * en lugar de UTF-8.
          */
         $codificacion = mb_detect_encoding(
             $valor,
             ['UTF-8', 'Windows-1252', 'ISO-8859-1'],
             true
         );
     
         if ($codificacion === false) {
             $codificacion = 'UTF-8';
         }
     
         /*
          * Convertir todo internamente a UTF-8.
          */
         if ($codificacion !== 'UTF-8') {
             $valor = mb_convert_encoding(
                 $valor,
                 'UTF-8',
                 $codificacion
             );
         }
     
         /*
          * Convertir a minúsculas.
          */
         $valor = mb_strtolower(
             $valor,
             'UTF-8'
         );
     
         /*
          * Reemplazar separadores.
          */
         $valor = str_replace(
             ['_', '-'],
             ' ',
             $valor
         );
     
         /*
          * Eliminar acentos de forma segura.
          */
         $valor = strtr(
             $valor,
             [
                 'á' => 'a',
                 'é' => 'e',
                 'í' => 'i',
                 'ó' => 'o',
                 'ú' => 'u',
                 'ü' => 'u',
                 'ñ' => 'n',
             ]
         );
     
         /*
          * Normalizar espacios.
          */
         $valor = preg_replace(
             '/\s+/',
             ' ',
             $valor
         );
     
         return trim($valor);
     }





    /**
     * Convierte un valor Excel a texto.
     *
     * Importante para:
     *
     * - códigos
     * - RUC
     * - códigos de producto
     * - teléfonos
     * - valores que puedan contener ceros iniciales
     */
    protected function textoExcel(mixed $valor): string
    {
        if ($valor === null) {
            return '';
        }

        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        return trim((string) $valor);
    }

    /**
     * Determina si una fila está completamente vacía.
     */
    protected function filaVacia(array $fila): bool
    {
        foreach ($fila as $valor) {

            if (trim((string) $valor) !== '') {
                return false;
            }
        }

        return true;
    }
}