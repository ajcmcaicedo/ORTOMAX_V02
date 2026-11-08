<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Importaciones\ImportadorExcel;
use App\Services\Importaciones\Perfiles\InstitucionesImportacion;

$ruta = __DIR__ . '/storage/app/importaciones/instituciones_20261007_040423_new.xlsx';

$importador = new ImportadorExcel();

$perfil = new InstitucionesImportacion();

$resultado = $importador->analizar(
    $ruta,
    $perfil
);

print_r([
    'perfil' => $perfil->nombre(),
    'total' => $resultado->total(),
    'nuevos' => $resultado->totalNuevos(),
    'actualizar' => $resultado->totalActualizar(),
    'sin_cambios' => $resultado->totalSinCambios(),
    'errores' => $resultado->totalErrores(),
]);

echo PHP_EOL;
echo "DETALLE DE FILAS:" . PHP_EOL;
echo "=================" . PHP_EOL;

echo PHP_EOL;
echo "DETALLE DE FILAS:";
echo PHP_EOL;
echo "=================";
echo PHP_EOL;

foreach ($resultado->filas() as $indice => $registro) {

    $fila = $registro['fila'] ?? [];

    echo PHP_EOL;
    echo "Fila #" . ($indice + 1) . PHP_EOL;

    echo "Estado: " .
        strtoupper($registro['estado'] ?? '') .
        PHP_EOL;

    echo "Código: " .
        ($fila['codigo'] ?? '') .
        PHP_EOL;

    if (($registro['estado'] ?? '') === 'actualizar') {

        $cambios = $registro['cambios'] ?? [];

        if (empty($cambios)) {

            echo "Cambios: NINGUNO" . PHP_EOL;

        } else {

            echo "Cambios:" . PHP_EOL;

            foreach ($cambios as $cambio) {

                echo "  - " .
                    ($cambio['nombre'] ?? $cambio['campo'] ?? '') .
                    PHP_EOL;

                echo "      Anterior: " .
                    ($cambio['anterior'] ?? '') .
                    PHP_EOL;

                echo "      Nuevo:    " .
                    ($cambio['nuevo'] ?? '') .
                    PHP_EOL;
            }
        }
    }
}