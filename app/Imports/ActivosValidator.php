<?php

namespace App\Imports;

use App\Models\Activo;
use App\Models\Bodega;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;

class ActivosValidator implements OnEachRow
{
    protected ValidadorOrtomaImport $validador;

    protected static ?array $activosExistentes = null;

    protected static ?array $serialesExistentes = null;

    protected static ?array $bodegasExistentes = null;

    public function __construct(ValidadorOrtomaImport $validador)
    {
        $this->validador = $validador;
    }

        public function onRow(Row $row): void
    {
        $fila = $row->getIndex();

        // Ignorar encabezados
        if ($fila === 1) {
            return;
        }

        $datos = $row->toArray();

        // Ignorar filas completamente vacías
        if (empty(array_filter($datos, fn ($valor) => $valor !== null && $valor !== ''))) {
            return;
        }
        /*
        |--------------------------------------------------------------------------
        | Normalizar valores
        |--------------------------------------------------------------------------
        */

        $codigoActivo = trim((string) ($datos[0] ?? ''));
        $codigoProducto = trim((string) ($datos[1] ?? ''));
        $nombre = trim((string) ($datos[2] ?? ''));
        $tipoActivo = strtoupper(trim((string) ($datos[3] ?? '')));
        $numeroSerial = trim((string) ($datos[4] ?? ''));
        $marca = trim((string) ($datos[5] ?? ''));
        $modelo = trim((string) ($datos[6] ?? ''));
        $serie = trim((string) ($datos[7] ?? ''));
        $bodega = trim((string) ($datos[8] ?? ''));
        $equipo = trim((string) ($datos[9] ?? ''));
        $estado = strtoupper(trim((string) ($datos[10] ?? '')));
        $activo = $datos[11] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Código del activo
        |--------------------------------------------------------------------------
        */

        if ($codigoActivo === '') {
            $this->validador->error(
                'ACTIVOS',
                $fila,
                'codigo_activo',
                'El código del activo es obligatorio.'
            );

            return;
        }

        $codigoActivoBusqueda = strtoupper($codigoActivo);

        /*
        |--------------------------------------------------------------------------
        | Cargar activos existentes en MySQL una sola vez
        |--------------------------------------------------------------------------
        */

        if (self::$activosExistentes === null) {
            self::$activosExistentes = Activo::query()
                ->pluck('id', 'codigo')
                ->mapWithKeys(function ($id, $codigo) {
                    return [strtoupper(trim($codigo)) => $id];
                })
                ->toArray();
        }

        /*
        |--------------------------------------------------------------------------
        | Detectar duplicados dentro del Excel
        |--------------------------------------------------------------------------
        */

        static $codigosExcel = [];

        if (isset($codigosExcel[$codigoActivoBusqueda])) {
            $this->validador->error(
                'ACTIVOS',
                $fila,
                'codigo_activo',
                "El código de activo '{$codigoActivo}' está repetido en el Excel."
            );
        } else {
            $codigosExcel[$codigoActivoBusqueda] = true;
        }

        /*
        |--------------------------------------------------------------------------
        | Verificar si ya existe en MySQL
        |--------------------------------------------------------------------------
        */

        if (isset(self::$activosExistentes[$codigoActivoBusqueda])) {
            $this->validador->advertencia(
                'ACTIVOS',
                $fila,
                'codigo_activo',
                "El activo '{$codigoActivo}' ya existe en la base de datos."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Nombre
        |--------------------------------------------------------------------------
        */

        if ($nombre === '') {
            $this->validador->error(
                'ACTIVOS',
                $fila,
                'nombre',
                'El nombre del activo es obligatorio.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Tipo de activo
        |--------------------------------------------------------------------------
        */

        if ($tipoActivo === '') {
            $this->validador->error(
                'ACTIVOS',
                $fila,
                'tipo_activo',
                'El tipo de activo es obligatorio.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Número de serial
        |--------------------------------------------------------------------------
        */

        if ($numeroSerial !== '') {

            if (self::$serialesExistentes === null) {
                self::$serialesExistentes = Activo::query()
                    ->whereNotNull('numero_serial')
                    ->pluck('id', 'numero_serial')
                    ->mapWithKeys(function ($id, $serial) {
                        return [strtoupper(trim($serial)) => $id];
                    })
                    ->toArray();
            }

            $serialBusqueda = strtoupper($numeroSerial);

            static $serialesExcel = [];

            if (isset($serialesExcel[$serialBusqueda])) {
                $this->validador->error(
                    'ACTIVOS',
                    $fila,
                    'numero_serial',
                    "El número de serial '{$numeroSerial}' está repetido en el Excel."
                );
            } else {
                $serialesExcel[$serialBusqueda] = true;
            }

            if (isset(self::$serialesExistentes[$serialBusqueda])) {
                $this->validador->advertencia(
                    'ACTIVOS',
                    $fila,
                    'numero_serial',
                    "El número de serial '{$numeroSerial}' ya existe en la base de datos."
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Bodega
        |--------------------------------------------------------------------------
        |
        | La bodega sí debe existir si se especifica.
        |
        */

        if ($bodega !== '') {

            if (self::$bodegasExistentes === null) {
                self::$bodegasExistentes = Bodega::query()
                    ->pluck('id', 'codigo')
                    ->mapWithKeys(function ($id, $codigo) {
                        return [strtoupper(trim($codigo)) => $id];
                    })
                    ->toArray();
            }

            $bodegaBusqueda = strtoupper($bodega);

            if (!isset(self::$bodegasExistentes[$bodegaBusqueda])) {

                /*
                 * También permitimos que la bodega esté definida
                 * en la hoja BODEGAS del mismo Excel.
                 */
                if (
                    !isset($this->validador->bodegas[$bodegaBusqueda])
                ) {
                    $this->validador->error(
                        'ACTIVOS',
                        $fila,
                        'bodega',
                        "La bodega '{$bodega}' no existe en la base de datos ni está definida en la hoja BODEGAS."
                    );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | EQUIPO
        |--------------------------------------------------------------------------
        |
        | IMPORTANTE:
        |
        | NO validamos que exista en la hoja EQUIPOS.
        |
        | Un activo puede ser asociado temporalmente a cualquier equipo
        | o grupo de equipos durante una cirugía.
        |
        */

        if ($equipo !== '') {
            // No se realiza validación de existencia.
        }

        /*
        |--------------------------------------------------------------------------
        | Estado
        |--------------------------------------------------------------------------
        */

        if ($estado === '') {

            $this->validador->error(
                'ACTIVOS',
                $fila,
                'estado',
                'El estado del activo es obligatorio.'
            );

        } elseif (!in_array($estado, [
            'ACTIVO',
            'INACTIVO',
            'DISPONIBLE',
            'MANTENIMIENTO',
            'BAJA',
        ], true)) {

            $this->validador->error(
                'ACTIVOS',
                $fila,
                'estado',
                "Estado '{$estado}' no válido."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Campo activo
        |--------------------------------------------------------------------------
        */

        if ($activo === null || $activo === '') {

            $this->validador->error(
                'ACTIVOS',
                $fila,
                'activo',
                'El campo activo es obligatorio y debe ser 0 o 1.'
            );

        } elseif (!in_array((string) $activo, ['0', '1'], true)) {

            $this->validador->error(
                'ACTIVOS',
                $fila,
                'activo',
                'El campo activo debe ser 0 o 1.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Guardar información validada en memoria
        |--------------------------------------------------------------------------
        */

        $this->validador->activos[$codigoActivoBusqueda] = [
            'codigo_activo' => $codigoActivo,
            'codigo_producto' => $codigoProducto !== ''
                ? $codigoProducto
                : null,
            'nombre' => $nombre,
            'tipo_activo' => $tipoActivo,
            'numero_serial' => $numeroSerial !== ''
                ? $numeroSerial
                : null,
            'marca' => $marca !== ''
                ? $marca
                : null,
            'modelo' => $modelo !== ''
                ? $modelo
                : null,
            'serie' => $serie !== ''
                ? $serie
                : null,
            'bodega' => $bodega !== ''
                ? $bodega
                : null,
            'equipo' => $equipo !== ''
                ? $equipo
                : null,
            'estado' => $estado,
            'activo' => (int) $activo,
        ];
    }
}