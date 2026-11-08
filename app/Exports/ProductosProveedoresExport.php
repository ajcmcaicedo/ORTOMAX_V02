<?php

namespace App\Exports;

use App\Models\ProductoProveedor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductosProveedoresExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return ProductoProveedor::query()
            ->with([
                'productoMaestro',
                'proveedor',
                'producto',
            ])
            ->orderBy('id')
            ->get()
            ->map(function ($item) {

                return [
                    'CODIGO_MAESTRO' => $item->productoMaestro?->codigo_maestro,
                    'PROVEEDOR_CODIGO' => $item->proveedor?->codigo,
                    'PRODUCTO_CONTIFICO' => $item->producto?->codigo,
                    'CODIGO_PRODUCTO' => $item->codigo_producto,
                    'REGISTRO_SANITARIO' => $item->registro_sanitario,
                    'ACTIVO' => $item->activo ? 'ACTIVO' : 'INACTIVO',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'CODIGO_MAESTRO',
            'PROVEEDOR_CODIGO',
            'PRODUCTO_CONTIFICO',
            'CODIGO_PRODUCTO',
            'REGISTRO_SANITARIO',
            'ACTIVO',
        ];
    }
}