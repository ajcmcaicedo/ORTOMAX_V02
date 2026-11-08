<?php

namespace App\Imports;

use App\Models\Producto;
use App\Models\ProductoMaestro;
use App\Models\ProductoProveedor;
use App\Models\Proveedor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class ProductosProveedoresImport implements ToCollection, WithHeadingRow
{
    /**
     * Total de filas procesadas.
     */
    protected int $totalFilas = 0;

    /**
     * Filas importadas correctamente.
     */
    protected int $importadas = 0;

    /**
     * Errores encontrados.
     */
    protected array $errores = [];

    /**
     * Procesar el archivo completo.
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {

            /*
             * La fila 1 contiene encabezados.
             * Por eso la primera fila de datos es la fila 2.
             */
            $fila = $index + 2;

            $this->totalFilas++;

            try {

                /*
                 * ---------------------------------------------------------
                 * LEER DATOS
                 * ---------------------------------------------------------
                 */

                $codigoMaestro = trim(
                    (string) ($row['codigo_maestro'] ?? '')
                );

                $proveedorCodigo = trim(
                    (string) ($row['proveedor_codigo'] ?? '')
                );

                $productoContifico = trim(
                    (string) ($row['producto_contifico'] ?? '')
                );

                $codigoProducto = trim(
                    (string) ($row['codigo_producto'] ?? '')
                );

                $registroSanitario = trim(
                    (string) ($row['registro_sanitario'] ?? '')
                );

                $activoTexto = strtoupper(
                    trim((string) ($row['activo'] ?? 'ACTIVO'))
                );

                /*
                 * ---------------------------------------------------------
                 * VALIDAR PRODUCTO MAESTRO
                 * ---------------------------------------------------------
                 */

                if ($codigoMaestro === '') {
                    $this->registrarError(
                        $fila,
                        'CODIGO_MAESTRO es obligatorio.'
                    );

                    continue;
                }

                $productoMaestro = ProductoMaestro::where(
                    'codigo_maestro',
                    $codigoMaestro
                )->first();

                if (!$productoMaestro) {
                    $this->registrarError(
                        $fila,
                        "El producto maestro '{$codigoMaestro}' no existe."
                    );

                    continue;
                }

                /*
                 * ---------------------------------------------------------
                 * VALIDAR PROVEEDOR
                 * ---------------------------------------------------------
                 */

                if ($proveedorCodigo === '') {
                    $this->registrarError(
                        $fila,
                        'PROVEEDOR_CODIGO es obligatorio.'
                    );

                    continue;
                }

                $proveedor = Proveedor::where(
                    'codigo',
                    $proveedorCodigo
                )->first();

                if (!$proveedor) {
                    $this->registrarError(
                        $fila,
                        "El proveedor '{$proveedorCodigo}' no existe."
                    );

                    continue;
                }

                /*
                 * ---------------------------------------------------------
                 * VALIDAR PRODUCTO CONTÍFICO
                 * ---------------------------------------------------------
                 */

                if ($productoContifico === '') {
                    $this->registrarError(
                        $fila,
                        'PRODUCTO_CONTIFICO es obligatorio.'
                    );

                    continue;
                }

                $producto = Producto::where(
                    'codigo',
                    $productoContifico
                )->first();

                if (!$producto) {
                    $this->registrarError(
                        $fila,
                        "El producto Contífico '{$productoContifico}' no existe."
                    );

                    continue;
                }

                /*
                 * ---------------------------------------------------------
                 * ESTADO
                 * ---------------------------------------------------------
                 */

                $activo = !in_array(
                    $activoTexto,
                    ['INACTIVO', '0', 'NO', 'FALSE'],
                    true
                );

                /*
                 * ---------------------------------------------------------
                 * CÓDIGO DEL PRODUCTO DEL PROVEEDOR
                 * ---------------------------------------------------------
                 */

                if ($codigoProducto === '') {
                    $codigoProducto = $producto->codigo;
                }

                /*
                 * ---------------------------------------------------------
                 * CREAR / ACTUALIZAR
                 * ---------------------------------------------------------
                 */

                ProductoProveedor::updateOrCreate(
                    [
                        'producto_maestro_id' => $productoMaestro->id,
                        'proveedor_id' => $proveedor->id,
                        'producto_id' => $producto->id,
                    ],
                    [
                        'codigo_producto' => $codigoProducto,
                        'registro_sanitario' => $registroSanitario ?: null,
                        'activo' => $activo,
                    ]
                );

                /*
                 * ---------------------------------------------------------
                 * FILA PROCESADA CORRECTAMENTE
                 * ---------------------------------------------------------
                 */

                $this->importadas++;

            } catch (Throwable $e) {

                /*
                 * Cualquier error inesperado en una fila tampoco
                 * debe detener la importación completa.
                 */

                $this->registrarError(
                    $fila,
                    $e->getMessage()
                );

                continue;
            }
        }
    }

    /**
     * Registrar un error de una fila.
     */
    protected function registrarError(int $fila, string $mensaje): void
    {
        $this->errores[] = [
            'fila' => $fila,
            'error' => $mensaje,
        ];
    }

    /**
     * Total de filas.
     */
    public function getTotalFilas(): int
    {
        return $this->totalFilas;
    }

    /**
     * Total importadas correctamente.
     */
    public function getImportadas(): int
    {
        return $this->importadas;
    }

    /**
     * Total de errores.
     */
    public function getTotalErrores(): int
    {
        return count($this->errores);
    }

    /**
     * Obtener detalle de errores.
     */
    public function getErrores(): array
    {
        return $this->errores;
    }

    /**
     * Indica si hubo errores.
     */
    public function tieneErrores(): bool
    {
        return !empty($this->errores);
    }
}