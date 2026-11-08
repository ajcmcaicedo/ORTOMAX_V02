<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ExcelHojasImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'INSTRUCCIONES'      => new HojaPruebaImport(),
            'REGIONALES'         => new HojaPruebaImport(),
            'BODEGAS'            => new HojaPruebaImport(),
            'EQUIPOS'            => new HojaPruebaImport(),
            'PRODUCTOS'          => new HojaPruebaImport(),
            'LOTES'              => new HojaPruebaImport(),
            'ACTIVOS'            => new HojaPruebaImport(),
            'CONTENIDO_EQUIPOS'  => new HojaPruebaImport(),
        ];
    }
}