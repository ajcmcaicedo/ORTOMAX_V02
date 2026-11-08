<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Producto;

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ruta = __DIR__ . '/storage/app/importaciones/CONSOLIDADO_IMPORTACION_V01.xlsx';

if (!file_exists($ruta)) {
    die("ERROR: No existe el archivo:\n$ruta\n");
}

$spreadsheet = IOFactory::load($ruta);
$sheet = $spreadsheet->getActiveSheet();

$filas = $sheet->toArray(null, true, true, true);

$encabezados = array_map(
    fn ($valor) => trim((string) $valor),
    array_shift($filas)
);

$indiceProducto = array_search('PRODUCTO', $encabezados);

if ($indiceProducto === false) {
    die("ERROR: No se encontró la columna PRODUCTO.\n");
}

$productosExcel = [];

foreach ($filas as $numeroFila => $fila) {

    $codigo = trim((string) ($fila[$indiceProducto] ?? ''));

    if ($codigo === '') {
        continue;
    }

    $productosExcel[$codigo][] = $numeroFila + 1;
}

$codigos = array_keys($productosExcel);

$existentes = Producto::whereIn('codigo', $codigos)
    ->pluck('codigo')
    ->map(fn ($codigo) => trim((string) $codigo))
    ->all();

$existentes = array_flip($existentes);

$noEncontrados = [];

foreach ($productosExcel as $codigo => $filasCodigo) {
    if (!isset($existentes[$codigo])) {
        $noEncontrados[$codigo] = $filasCodigo;
    }
}

echo PHP_EOL;
echo "==============================================" . PHP_EOL;
echo " AUDITORÍA PRODUCTOS - CONSOLIDADO_IMPORTACION" . PHP_EOL;
echo "==============================================" . PHP_EOL;

echo "Registros Excel:              " . count($filas) . PHP_EOL;
echo "Productos únicos en Excel:   " . count($productosExcel) . PHP_EOL;
echo "Productos encontrados BD:    " . count($existentes) . PHP_EOL;
echo "Productos NO encontrados:    " . count($noEncontrados) . PHP_EOL;

echo PHP_EOL;

if (count($noEncontrados) === 0) {

    echo "RESULTADO: TODOS LOS PRODUCTOS EXISTEN EN LA BD." . PHP_EOL;

} else {

    echo "PRODUCTOS NO ENCONTRADOS:" . PHP_EOL;
    echo "----------------------------------------------" . PHP_EOL;

    foreach ($noEncontrados as $codigo => $filasCodigo) {
        echo $codigo
            . " | filas Excel: "
            . implode(', ', $filasCodigo)
            . PHP_EOL;
    }
}

echo PHP_EOL;
echo "==============================================" . PHP_EOL;