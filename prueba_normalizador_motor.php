<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\Importaciones\ImportadorExcel;

$motor = new ImportadorExcel();

$reflection = new ReflectionClass($motor);

$metodo = $reflection->getMethod('normalizarEncabezado');
$metodo->setAccessible(true);

$pruebas = [
    'Razón Social_Cliente',
    'Clínica_Hospital',
    'Dirección',
    'razón social cliente',
    'clínica hospital',
    'dirección',
];

echo "PRUEBA DEL NORMALIZADOR REAL DEL MOTOR" . PHP_EOL;
echo "=======================================" . PHP_EOL;
echo PHP_EOL;

foreach ($pruebas as $texto) {

    $resultado = $metodo->invoke($motor, $texto);

    echo "ORIGINAL : [" . $texto . "]" . PHP_EOL;
    echo "RESULTADO: [" . $resultado . "]" . PHP_EOL;
    echo "HEX ORIGINAL : " . bin2hex($texto) . PHP_EOL;
    echo "HEX RESULTADO: " . bin2hex($resultado) . PHP_EOL;
    echo "---------------------------------------" . PHP_EOL;
}