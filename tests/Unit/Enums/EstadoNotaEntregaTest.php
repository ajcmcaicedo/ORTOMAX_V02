<?php

namespace Tests\Unit\Enums;

use App\Enums\EstadoNotaEntrega as E;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EstadoNotaEntregaTest extends TestCase
{
    public static function flujoNormal(): array
    {
        return [
            [E::BORRADOR, E::EN_ALISTAMIENTO],
            [E::EN_ALISTAMIENTO, E::ALISTADA],
            [E::ALISTADA, E::DESPACHADA],
            [E::DESPACHADA, E::ENTREGADA],
            [E::ENTREGADA, E::EN_CONCILIACION],
            [E::EN_CONCILIACION, E::EN_DEVOLUCION],
            [E::EN_DEVOLUCION, E::CERRADA],
        ];
    }

    #[DataProvider('flujoNormal')]
    public function test_flujo_normal_permitido(E $desde, E $hasta): void
    {
        $this->assertTrue($desde->puedePasarA($hasta));
    }

    public function test_no_se_salta_estados(): void
    {
        $this->assertFalse(E::BORRADOR->puedePasarA(E::DESPACHADA));
        $this->assertFalse(E::EN_ALISTAMIENTO->puedePasarA(E::DESPACHADA));
        $this->assertFalse(E::DESPACHADA->puedePasarA(E::EN_CONCILIACION));
        $this->assertFalse(E::ENTREGADA->puedePasarA(E::CERRADA));
    }

    public function test_estados_finales_no_tienen_salida(): void
    {
        $this->assertSame([], E::CERRADA->transicionesPermitidas());
        $this->assertSame([], E::ANULADA->transicionesPermitidas());
    }

    public function test_no_se_anula_en_devolucion(): void
    {
        $this->assertFalse(E::EN_DEVOLUCION->puedePasarA(E::ANULADA));
    }

    public function test_contenido_editable_solo_antes_de_alistada(): void
    {
        $editables = array_filter(E::cases(), fn (E $e) => $e->permiteEditarContenido());

        $this->assertEqualsCanonicalizing([E::BORRADOR, E::EN_ALISTAMIENTO], array_values($editables));
    }

    public function test_borrador_no_ocupa_equipos_pero_alistamiento_si(): void
    {
        $this->assertFalse(E::BORRADOR->ocupaEquipos());
        $this->assertTrue(E::EN_ALISTAMIENTO->ocupaEquipos());
        $this->assertTrue(E::ENTREGADA->ocupaEquipos());
        $this->assertFalse(E::CERRADA->ocupaEquipos());
    }
}
