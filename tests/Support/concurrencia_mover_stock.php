<?php

/*
 * Proceso independiente usado por ConcurrenciaInventarioTest.
 *
 * Uso: php concurrencia_mover_stock.php <stock_lote_id> <cantidad> <inicio_unix_float> <espera_ms>
 *
 * Espera hasta <inicio> para arrancar a la par con otro proceso, intenta
 * consumir <cantidad> del stock y, ya con las filas bloqueadas, espera
 * <espera_ms> para forzar la superposición. Imprime OK o FALLO.
 */

use App\Enums\TipoMovimiento;
use App\Services\Inventario\LineaMovimiento;
use App\Services\Inventario\MotorInventario;
use App\Services\Inventario\PuntoStock;
use App\Services\Inventario\SolicitudMovimiento;

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

[, $stockId, $cantidad, $inicio, $esperaMs] = $argv;

$motor = new class((int) $esperaMs) extends MotorInventario {
    public function __construct(private int $esperaMs)
    {
    }

    protected function despuesDeBloquear(): void
    {
        usleep($this->esperaMs * 1000);
    }
};

while (microtime(true) < (float) $inicio) {
    usleep(1000);
}

try {
    $motor->registrar(new SolicitudMovimiento(
        tipo: TipoMovimiento::CONSUMO,
        lineas: [new LineaMovimiento($cantidad, PuntoStock::stockLoteId((int) $stockId), null)],
        usuarioId: null,
    ));
    echo 'OK';
} catch (Throwable $e) {
    echo 'FALLO: ' . get_class($e) . ' ' . $e->getMessage();
}
