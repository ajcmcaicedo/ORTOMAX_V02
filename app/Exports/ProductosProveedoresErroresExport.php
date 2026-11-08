<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductosProveedoresErroresExport implements FromArray, WithHeadings
{
    protected array $errores;

    public function __construct(array $errores)
    {
        $this->errores = $errores;
    }

    /**
     * Datos del Excel.
     */
    public function array(): array
    {
        return array_map(
            function ($error) {
                return [
                    $error['fila'] ?? '',
                    $error['error'] ?? '',
                ];
            },
            $this->errores
        );
    }

    /**
     * Encabezados.
     */
    public function headings(): array
    {
        return [
            'FILA',
            'ERROR',
        ];
    }
}