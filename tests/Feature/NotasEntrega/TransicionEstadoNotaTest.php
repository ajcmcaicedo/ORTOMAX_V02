<?php

namespace Tests\Feature\NotasEntrega;

use App\Enums\EstadoNotaEntrega as E;
use App\Services\NotasEntrega\NotaModificadaException;
use App\Services\NotasEntrega\TransicionEstadoNota;
use App\Services\NotasEntrega\TransicionNoPermitidaException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\Support\DatosOperativos;
use Tests\TestCase;

class TransicionEstadoNotaTest extends TestCase
{
    use RefreshDatabase;
    use DatosOperativos;

    private TransicionEstadoNota $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = app(TransicionEstadoNota::class);
    }

    public function test_transicion_valida_cambia_estado_version_y_audita(): void
    {
        $usuario = $this->usuarioConRol('PREPARADOR');
        $nota = $this->nota(E::ALISTADA);

        $this->servicio->ejecutar($nota, E::DESPACHADA, $usuario);

        $nota->refresh();
        $this->assertSame(E::DESPACHADA, $nota->estado);
        $this->assertSame(2, $nota->version);
        $this->assertNotNull($nota->fecha_despacho);
        $this->assertDatabaseHas('bitacora', [
            'evento' => 'NOTA_ESTADO',
            'nota_entrega_id' => $nota->id,
            'usuario_id' => $usuario->id,
        ]);
        $registro = $nota->bitacora()->first();
        $this->assertSame(['estado' => 'ALISTADA'], $registro->valores_anteriores);
        $this->assertSame(['estado' => 'DESPACHADA'], $registro->valores_nuevos);
    }

    public function test_rechaza_saltar_estados(): void
    {
        $nota = $this->nota(E::BORRADOR);

        $this->expectException(TransicionNoPermitidaException::class);

        $this->servicio->ejecutar($nota, E::DESPACHADA, $this->usuarioConRol('PREPARADOR'));
    }

    public function test_doble_clic_no_repite_efectos(): void
    {
        $usuario = $this->usuarioConRol('PREPARADOR');
        $nota = $this->nota(E::ALISTADA);
        $ejecuciones = 0;
        $efectos = function () use (&$ejecuciones) {
            $ejecuciones++;
        };

        $this->servicio->ejecutar($nota, E::DESPACHADA, $usuario, efectos: $efectos);
        $this->servicio->ejecutar($nota, E::DESPACHADA, $usuario, efectos: $efectos);

        $this->assertSame(1, $ejecuciones);
        $this->assertSame(1, $nota->bitacora()->where('evento', 'NOTA_ESTADO')->count());
    }

    public function test_si_fallan_los_efectos_el_estado_no_cambia(): void
    {
        $nota = $this->nota(E::ALISTADA);

        try {
            $this->servicio->ejecutar(
                $nota,
                E::DESPACHADA,
                $this->usuarioConRol('PREPARADOR'),
                efectos: fn () => throw new RuntimeException('Sin stock'),
            );
            $this->fail('Se esperaba excepción');
        } catch (RuntimeException) {
        }

        $this->assertSame(E::ALISTADA, $nota->fresh()->estado);
        $this->assertSame(0, $nota->bitacora()->count());
    }

    public function test_bloqueo_optimista_detecta_cambios_de_otro_usuario(): void
    {
        $nota = $this->nota(E::EN_ALISTAMIENTO);
        $nota->forceFill(['version' => 5])->save();

        $this->expectException(NotaModificadaException::class);

        $this->servicio->ejecutar($nota, E::ALISTADA, $this->usuarioConRol('PREPARADOR'), versionEsperada: 4);
    }

    public function test_anular_exige_motivo(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->servicio->ejecutar($this->nota(), E::ANULADA, $this->usuarioConRol('GERENTE'), motivo: '  ');
    }
}
