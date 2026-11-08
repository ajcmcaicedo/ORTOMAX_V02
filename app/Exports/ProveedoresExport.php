<?php

namespace App\Exports;

use App\Models\Proveedor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProveedoresExport implements FromCollection, WithHeadings
{
    /**
     * Datos que se exportarán.
     */
    public function collection()
    {
        return Proveedor::query()
            ->orderBy('codigo')
            ->get([
                'codigo',
                'nombre',
                'ruc',
                'sigla',
                'estado',
            ])
            ->map(function ($proveedor) {

                return [
                    $proveedor->codigo,
                    $proveedor->nombre,
                    $proveedor->ruc,
                    $proveedor->sigla,
                    $proveedor->estado
                        ? 'ACTIVO'
                        : 'INACTIVO',
                ];
            });
    }

    /**
     * Encabezados del Excel.
     */
    public function headings(): array
    {
        return [
            'CODIGO',
            'NOMBRE',
            'RUC',
            'SIGLA',
            'ESTADO',
        ];
    }
}