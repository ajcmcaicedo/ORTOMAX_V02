<?php

namespace Tests\Feature\Inventario;

use App\Models\MovimientoInventario;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\Support\DatosOperativos;
use Tests\TestCase;

/**
 * Dos procesos reales (dos conexiones MySQL) compiten por el mismo lote.
 * Requiere datos confirmados, por eso usa truncado en lugar de transacción.
 */
class ConcurrenciaInventarioTest extends TestCase
{
    use DatabaseTruncation;
    use DatosOperativos;

    /** Datos sembrados por migraciones que otras pruebas necesitan. */
    protected $exceptTables = ['migrations', 'roles', 'permissions', 'permission_role'];

    protected function tearDown(): void
    {
        // Deja la base limpia para las pruebas que corren después.
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }

    public function test_dos_usuarios_no_pueden_tomar_el_mismo_stock(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('La prueba de bloqueo de filas requiere MySQL.');
        }

        $lote = $this->lote($this->producto());
        $stock = $this->stock($this->ubicacion($this->bodega()), $lote, 5);

        $inicio = microtime(true) + 1.5;
        $procesos = [];

        foreach ([1, 2] as $i) {
            $p = new Process(
                [PHP_BINARY, base_path('tests/Support/concurrencia_mover_stock.php'), $stock->id, '5', (string) $inicio, '400'],
                base_path(),
                $this->entornoBaseDatos(),
                null,
                60
            );
            $p->start();
            $procesos[] = $p;
        }

        $salidas = [];

        foreach ($procesos as $p) {
            $p->wait();
            $salidas[] = trim($p->getOutput() . $p->getErrorOutput());
        }

        $exitos = count(array_filter($salidas, fn ($s) => $s === 'OK'));

        $this->assertSame(1, $exitos, 'Salidas: ' . implode(' | ', $salidas));
        $this->assertStringContainsString('StockInsuficienteException', implode(' ', $salidas));
        $this->assertSame('0.00', $stock->fresh()->cantidad);
        $this->assertSame(1, MovimientoInventario::count());
    }

    private function entornoBaseDatos(): array
    {
        $c = config('database.connections.mysql');

        return [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $c['host'],
            'DB_PORT' => (string) $c['port'],
            'DB_DATABASE' => $c['database'],
            'DB_USERNAME' => $c['username'],
            'DB_PASSWORD' => $c['password'],
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
        ];
    }
}
