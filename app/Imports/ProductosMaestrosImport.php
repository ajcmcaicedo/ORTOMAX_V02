<?php

namespace App\Imports;

use App\Models\ProductoMaestro;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductosMaestrosImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {

            if (
                empty($row['codigo_maestro']) &&
                empty($row['descripcion']) &&
                empty($row['estado'])
            ) {
                continue;
            }

            $codigoMaestro = trim(
                (string) ($row['codigo_maestro'] ?? '')
            );

            $descripcion = trim(
                (string) ($row['descripcion'] ?? '')
            );

            $estado = strtoupper(
                trim((string) ($row['estado'] ?? 'ACTIVO'))
            );

            if ($codigoMaestro === '' || $descripcion === '') {
                continue;
            }

            $activo = !in_array(
                $estado,
                ['INACTIVO', 'INACTIVA', '0', 'NO'],
                true
            );

            ProductoMaestro::updateOrCreate(
                [
                    'codigo_maestro' => $codigoMaestro,
                ],
                [
                    'descripcion' => $descripcion,
                    'estado' => $activo,
                ]
            );
        }
    }
}