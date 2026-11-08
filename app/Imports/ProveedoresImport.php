<?php

namespace App\Imports;

use App\Models\Proveedor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProveedoresImport implements ToCollection, WithHeadingRow
{
    /**
     * Importar proveedores desde Excel.
     */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {

            /*
             * Ignorar filas completamente vacías.
             */
            if (
                empty($row['codigo']) &&
                empty($row['nombre']) &&
                empty($row['ruc']) &&
                empty($row['sigla']) &&
                empty($row['estado'])
            ) {
                continue;
            }

            /*
             * Normalizar datos.
             */
            $codigo = strtoupper(
                trim((string) ($row['codigo'] ?? ''))
            );

            $nombre = trim(
                (string) ($row['nombre'] ?? '')
            );

            $ruc = trim(
                (string) ($row['ruc'] ?? '')
            );

            $sigla = strtoupper(
                trim((string) ($row['sigla'] ?? ''))
            );

            $estado = strtoupper(
                trim((string) ($row['estado'] ?? 'ACTIVO'))
            );

            /*
             * Validaciones básicas.
             */
            if ($codigo === '') {
                continue;
            }

            if ($nombre === '') {
                continue;
            }

            if ($sigla === '') {
                continue;
            }

            /*
             * Normalizar estado.
             */
            $activo = !in_array(
                $estado,
                ['INACTIVO', 'INACTIVA', '0', 'NO'],
                true
            );

            /*
             * Crear o actualizar por código.
             */
            Proveedor::updateOrCreate(
                [
                    'codigo' => $codigo,
                ],
                [
                    'nombre' => $nombre,
                    'ruc' => $ruc !== ''
                        ? $ruc
                        : null,
                    'sigla' => $sigla,
                    'estado' => $activo,
                ]
            );
        }
    }
}