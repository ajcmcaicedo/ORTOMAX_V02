<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class HojaPruebaImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        dd($rows->take(5));
    }
}