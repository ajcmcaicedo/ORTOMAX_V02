<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$ruta = __DIR__ . '/storage/app/importaciones/instituciones_20261007_040423_new.xlsx';

$documento = IOFactory::load($ruta);

$hoja = $documento->getActiveSheet();

echo "ENCABEZADOS ORIGINALES:" . PHP_EOL;
echo "=======================" . PHP_EOL;

for ($columna = 1; $columna <= 8; $columna++) {

    $valor = $hoja->getCellByColumnAndRow(
        $columna,
        1
    )->getValue();

    echo $columna . ": [" . $valor . "]" . PHP_EOL;
}

echo PHP_EOL;
echo "NORMALIZADOS:" . PHP_EOL;
echo "=============" . PHP_EOL;

/*
 * Reproducimos exactamente la función
 * normalizarEncabezado().
 */
function normalizar($valor): string
{
    $valor = trim((string) $valor);

    $valor = mb_strtolower(
        $valor,
        'UTF-8'
    );

    $valor = str_replace(
        ['_', '-'],
        ' ',
        $valor
    );

    $valor = strtr(
        $valor,
        [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ]
    );

    $valor = preg_replace(
        '/\s+/',
        ' ',
        $valor
    );

    return trim($valor);
}

for ($columna = 1; $columna <= 8; $columna++) {

    $valor = $hoja->getCellByColumnAndRow(
        $columna,
        1
    )->getValue();

    echo $columna . ": [" . normalizar($valor) . "]" . PHP_EOL;
}