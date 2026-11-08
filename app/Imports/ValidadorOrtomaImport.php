<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ValidadorOrtomaImport implements WithMultipleSheets
{
    public array $errores = [];

    public array $advertencias = [];

    public array $regionales = [];

    public array $bodegas = [];

    public array $equipos = [];

    public array $productos = [];

    public array $lotes = [];

    public array $activos = [];

    public array $contenidos = [];

    public function sheets(): array
    {
        return [
            'REGIONALES' => new RegionalesValidator($this),
            'BODEGAS' => new BodegasValidator($this),
            'EQUIPOS' => new EquiposValidator($this),
            'PRODUCTOS' => new ProductosValidator($this),
            'LOTES' => new LotesValidator($this),
            'ACTIVOS' => new ActivosValidator($this),
            'CONTENIDO_EQUIPOS' => new ContenidoEquiposValidator($this),
        ];
    }

    public function error(
        string $hoja,
        int $fila,
        string $campo,
        string $mensaje
    ): void {
        $this->errores[] = [
            'hoja' => $hoja,
            'fila' => $fila,
            'campo' => $campo,
            'mensaje' => $mensaje,
        ];
    }

    public function advertencia(
        string $hoja,
        int $fila,
        string $campo,
        string $mensaje
    ): void {
        $this->advertencias[] = [
            'hoja' => $hoja,
            'fila' => $fila,
            'campo' => $campo,
            'mensaje' => $mensaje,
        ];
    }
}