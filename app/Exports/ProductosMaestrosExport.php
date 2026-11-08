<?php

namespace App\Exports;

use App\Models\ProductoMaestro;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductosMaestrosExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return ProductoMaestro::query()
            ->orderBy('codigo_maestro')
            ->get([
                'codigo_maestro',
                'descripcion',
                'estado',
            ])
            ->map(function ($productoMaestro) {
                return [
                    $productoMaestro->codigo_maestro,
                    $productoMaestro->descripcion,
                    $productoMaestro->estado ? 'ACTIVO' : 'INACTIVO',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'CODIGO_MAESTRO',
            'DESCRIPCION',
            'ESTADO',
        ];
    }
}