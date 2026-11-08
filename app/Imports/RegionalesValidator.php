<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class RegionalesValidator implements ToCollection
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

            $codigo = trim((string) ($row[0] ?? ''));
            $descripcion = trim((string) ($row[1] ?? ''));
            $estado = strtoupper(trim((string) ($row[2] ?? '')));

            if ($codigo === '') {
                $this->validador->error(
                    'REGIONALES',
                    $fila,
                    'codigo',
                    'El código de la regional es obligatorio.'
                );
                continue;
            }

            if (isset($codigos[$codigo])) {
                $this->validador->error(
                    'REGIONALES',
                    $fila,
                    'codigo',
                    "El código '$codigo' está duplicado en el Excel."
                );
                continue;
            }

            $codigos[$codigo] = true;

            if ($descripcion === '') {
                $this->validador->error(
                    'REGIONALES',
                    $fila,
                    'descripcion',
                    'La descripción es obligatoria.'
                );
            }

            if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
                $this->validador->error(
                    'REGIONALES',
                    $fila,
                    'estado',
                    "Estado inválido '$estado'. Use ACTIVO o INACTIVO."
                );
            }

            $this->validador->regionales[$codigo] = [
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'estado' => $estado,
                'fila' => $fila,
            ];
        }
    }
}