<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class EquiposValidator implements ToCollection
{
    public function __construct(
        protected ValidadorOrtomaImport $validador
    ) {
    }

    public function collection(Collection $rows)
    {
        $codigos = [];

        foreach ($rows as $index => $row) {

            if ($index === 0) {
                continue;
            }

            $fila = $index + 1;

            $codigoEquipo = trim((string) ($row[0] ?? ''));
            $nombre = trim((string) ($row[1] ?? ''));
            $tipoEquipo = trim((string) ($row[2] ?? ''));
            $regional = trim((string) ($row[3] ?? ''));
            $bodega = trim((string) ($row[4] ?? ''));
            $estado = strtoupper(trim((string) ($row[5] ?? '')));
            $activo = $row[6] ?? null;

            if ($codigoEquipo === '') {
                $this->validador->error(
                    'EQUIPOS',
                    $fila,
                    'codigo_equipo',
                    'El código del equipo es obligatorio.'
                );

                continue;
            }

            /*
             * Normalizamos el código para las comparaciones.
             * Así P13NBC229 y p13nbc229 se consideran iguales.
             */
            $codigoEquipoBusqueda = strtoupper($codigoEquipo);

            /*
             * Código duplicado dentro del Excel.
             */
            if (isset($codigos[$codigoEquipoBusqueda])) {

                $this->validador->error(
                    'EQUIPOS',
                    $fila,
                    'codigo_equipo',
                    "El código '$codigoEquipo' está duplicado en el Excel."
                );

                continue;
            }

            $codigos[$codigoEquipoBusqueda] = true;

            /*
             * Nombre obligatorio.
             */
            if ($nombre === '') {

                $this->validador->error(
                    'EQUIPOS',
                    $fila,
                    'nombre',
                    'El nombre del equipo es obligatorio.'
                );
            }

            /*
             * Tipo de equipo obligatorio.
             */
            if ($tipoEquipo === '') {

                $this->validador->error(
                    'EQUIPOS',
                    $fila,
                    'tipo_equipo',
                    'El tipo de equipo es obligatorio.'
                );
            }

            /*
             * Regional.
             */
            if ($regional === '') {

                $this->validador->error(
                    'EQUIPOS',
                    $fila,
                    'regional',
                    'La regional es obligatoria.'
                );

            } elseif (!isset($this->validador->regionales[$regional])) {

                $this->validador->error(
                    'EQUIPOS',
                    $fila,
                    'regional',
                    "La regional '$regional' no existe."
                );
            }

            /*
             * Bodega.
             */
            if ($bodega === '') {

                $this->validador->error(
                    'EQUIPOS',
                    $fila,
                    'bodega',
                    'La bodega es obligatoria.'
                );

            } elseif (!isset($this->validador->bodegas[$bodega])) {

                $this->validador->error(
                    'EQUIPOS',
                    $fila,
                    'bodega',
                    "La bodega '$bodega' no existe."
                );

            } else {

                $bodegaRegional =
                    $this->validador->bodegas[$bodega]['regional'];

                if (
                    $regional !== '' &&
                    $bodegaRegional !== '' &&
                    $regional !== $bodegaRegional
                ) {

                    $this->validador->error(
                        'EQUIPOS',
                        $fila,
                        'bodega',
                        "La bodega '$bodega' pertenece a la regional '$bodegaRegional', pero el equipo está asignado a la regional '$regional'."
                    );
                }
            }

            /*
             * Estado.
             */
            if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {

                $this->validador->error(
                    'EQUIPOS',
                    $fila,
                    'estado',
                    "Estado inválido '$estado'. Use ACTIVO o INACTIVO."
                );
            }

            /*
             * Guardamos el equipo en memoria.
             *
             * IMPORTANTE:
             * La clave se guarda normalizada para que
             * CONTENIDO_EQUIPOS pueda encontrarlo.
             */
            $this->validador->equipos[$codigoEquipoBusqueda] = [
                'codigo_equipo' => $codigoEquipo,
                'nombre' => $nombre,
                'tipo_equipo' => $tipoEquipo,
                'regional' => $regional,
                'bodega' => $bodega,
                'estado' => $estado,
                'activo' => $activo,
                'fila' => $fila,
            ];
        }
    }
}