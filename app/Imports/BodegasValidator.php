<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class BodegasValidator implements ToCollection
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
            $regional = trim((string) ($row[2] ?? ''));
            $estado = strtoupper(trim((string) ($row[3] ?? '')));

            if ($codigo === '') {
                $this->validador->error(
                    'BODEGAS',
                    $fila,
                    'codigo',
                    'El código de la bodega es obligatorio.'
                );
                continue;
            }

            if (isset($codigos[$codigo])) {
                $this->validador->error(
                    'BODEGAS',
                    $fila,
                    'codigo',
                    "El código '$codigo' está duplicado en el Excel."
                );
                continue;
            }

            $codigos[$codigo] = true;

            if ($descripcion === '') {
                $this->validador->error(
                    'BODEGAS',
                    $fila,
                    'descripcion',
                    'La descripción es obligatoria.'
                );
            }

            if ($regional === '') {
                $this->validador->error(
                    'BODEGAS',
                    $fila,
                    'regional',
                    'La regional es obligatoria.'
                );
            } elseif (!isset($this->validador->regionales[$regional])) {
                $this->validador->error(
                    'BODEGAS',
                    $fila,
                    'regional',
                    "La regional '$regional' no existe en la hoja REGIONALES."
                );
            }

            if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
                $this->validador->error(
                    'BODEGAS',
                    $fila,
                    'estado',
                    "Estado inválido '$estado'. Use ACTIVO o INACTIVO."
                );
            }

            $this->validador->bodegas[$codigo] = [
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'regional' => $regional,
                'estado' => $estado,
                'fila' => $fila,
            ];
        }
    }
}