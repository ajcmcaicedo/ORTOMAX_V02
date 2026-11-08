<?php

require __DIR__ . '/vendor/autoload.php';

echo "=== ImportadorExcel ===" . PHP_EOL;

$r = new ReflectionClass(
    App\Services\Importaciones\ImportadorExcel::class
);

foreach ($r->getMethods() as $m) {
    echo $m->getName() . PHP_EOL;
}

echo PHP_EOL;
echo "=== InstitucionesImportacion ===" . PHP_EOL;

$r = new ReflectionClass(
    App\Services\Importaciones\Perfiles\InstitucionesImportacion::class
);

foreach ($r->getMethods() as $m) {
    echo $m->getName() . PHP_EOL;
}