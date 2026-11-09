<?php

namespace Tests\Feature\NotasEntrega;

use App\Enums\EstadoNotaEntrega as E;
use App\Models\NotaEntregaDetalle;
use App\Services\NotasEntrega\SugeridorEquipos as S;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\DatosOperativos;
use Tests\TestCase;

/**
 * Sugerencia de equipos por tipo de cirugía (ejemplo real: radio distal).
 */
class SugeridorEquiposTest extends TestCase
{
    use DatosOperativos;
    use RefreshDatabase;

    private function sugerir($nota): array
    {
        return collect(app(S::class)->sugerir($nota->fresh()))->keyBy('codigo_referencia')->all();
    }

    public function test_radio_distal_propone_copias_disponibles_y_no_agrega_nada(): void
    {
        $bodega = $this->bodega();
        $this->equipo('ARIX-RADIO-DISTAL', $bodega, 'P13NAC5');
        $this->equipo('ARIX-MAN', $bodega, 'P13NAC7');
        $this->equipo('BATERIA-AESCULAP', $bodega, 'P17NAB39');
        $this->equipo('BATERIA-AESCULAP', $bodega, 'P17NAB40');

        $tipo = $this->tipoCirugia([
            'ARIX-RADIO-DISTAL' => true,
            'ARIX-MAN' => true,
            'BATERIA-AESCULAP' => false,
        ]);
        $nota = $this->nota(E::BORRADOR, ['tipo_cirugia_id' => $tipo->id, 'bodega_origen_id' => $bodega->id]);

        $s = $this->sugerir($nota);

        $this->assertSame(['ARIX-RADIO-DISTAL', 'ARIX-MAN', 'BATERIA-AESCULAP'], array_keys($s));
        $this->assertSame(S::DISPONIBLE, $s['ARIX-RADIO-DISTAL']['estado']);
        $this->assertSame('P13NAC5', $s['ARIX-RADIO-DISTAL']['propuesto']['codigo']);
        $this->assertTrue($s['ARIX-RADIO-DISTAL']['obligatorio']);
        $this->assertFalse($s['BATERIA-AESCULAP']['obligatorio']);
        $this->assertSame('P17NAB39', $s['BATERIA-AESCULAP']['propuesto']['codigo']);
        $this->assertSame('P17NAB40', $s['BATERIA-AESCULAP']['otras_disponibles'][0]['codigo']);

        // La sugerencia nunca agrega equipos por sí sola.
        $this->assertSame(0, NotaEntregaDetalle::where('nota_entrega_id', $nota->id)->count());
    }

    public function test_copia_ocupada_muestra_nota_e_institucion_y_ofrece_equivalente(): void
    {
        $bodega = $this->bodega();
        $n1 = $this->equipo('ARIX-RADIO-DISTAL', $bodega, 'P13NAC5');
        $this->equipo('ARIX-RADIO-DISTAL', $bodega, 'P13NAC6');

        $otra = $this->nota(E::DESPACHADA, ['institucion_id' => $this->institucion('Hospital Metropolitano')->id]);
        $this->ponerEquipoEnNota($otra, $n1);

        $tipo = $this->tipoCirugia(['ARIX-RADIO-DISTAL' => true]);
        $nota = $this->nota(E::BORRADOR, ['tipo_cirugia_id' => $tipo->id, 'bodega_origen_id' => $bodega->id]);

        $s = $this->sugerir($nota)['ARIX-RADIO-DISTAL'];

        $this->assertSame(S::DISPONIBLE, $s['estado']);
        $this->assertSame('P13NAC6', $s['propuesto']['codigo']);
        $this->assertSame('P13NAC5', $s['no_disponibles'][0]['codigo']);
        $this->assertStringContainsString(trim($otra->numero), $s['no_disponibles'][0]['motivo']);
        $this->assertStringContainsString('Hospital Metropolitano', $s['no_disponibles'][0]['motivo']);
    }

    public function test_sin_copias_disponibles_o_sin_equipos(): void
    {
        $unico = $this->equipo('MOTOR-AESCULAP', null, 'P14NDC106');
        $this->equipo('ACUTEC', null, 'P14NAC63', estado: 'EN_MANTENIMIENTO');

        $ocupante = $this->nota(E::EN_ALISTAMIENTO);
        $this->ponerEquipoEnNota($ocupante, $unico);

        $tipo = $this->tipoCirugia(['MOTOR-AESCULAP' => true, 'ACUTEC' => true, 'NO-EXISTE' => false]);
        $s = $this->sugerir($this->nota(E::BORRADOR, ['tipo_cirugia_id' => $tipo->id]));

        $this->assertSame(S::SIN_DISPONIBLE, $s['MOTOR-AESCULAP']['estado']);
        $this->assertSame(S::SIN_DISPONIBLE, $s['ACUTEC']['estado']);
        $this->assertStringContainsString('EN_MANTENIMIENTO', $s['ACUTEC']['no_disponibles'][0]['motivo']);
        $this->assertSame(S::SIN_EQUIPOS, $s['NO-EXISTE']['estado']);
    }

    public function test_otro_borrador_no_bloquea_pero_se_informa(): void
    {
        $equipo = $this->equipo('ARIX-MAN', null, 'P13NAC7');
        $borrador = $this->nota(E::BORRADOR);
        $this->ponerEquipoEnNota($borrador, $equipo);

        $tipo = $this->tipoCirugia(['ARIX-MAN' => true]);
        $s = $this->sugerir($this->nota(E::BORRADOR, ['tipo_cirugia_id' => $tipo->id]))['ARIX-MAN'];

        $this->assertSame(S::DISPONIBLE, $s['estado']);
        $this->assertSame(trim($borrador->numero), $s['propuesto']['en_borradores'][0]['numero']);
    }

    public function test_notas_cerradas_o_anuladas_no_ocupan(): void
    {
        $equipo = $this->equipo('ARIX-MAN', null, 'P13NAC7');
        $this->ponerEquipoEnNota($this->nota(E::CERRADA), $equipo);
        $this->ponerEquipoEnNota($this->nota(E::ANULADA), $equipo);

        $tipo = $this->tipoCirugia(['ARIX-MAN' => true]);
        $s = $this->sugerir($this->nota(E::BORRADOR, ['tipo_cirugia_id' => $tipo->id]))['ARIX-MAN'];

        $this->assertSame(S::DISPONIBLE, $s['estado']);
        $this->assertSame([], $s['no_disponibles']);
    }

    public function test_prefiere_la_bodega_de_la_nota(): void
    {
        $principal = $this->bodega();
        $otra = $this->bodega();
        $this->equipo('ARIX-MAN', $otra, 'P13NAC1');
        $this->equipo('ARIX-MAN', $principal, 'P13NAC9');

        $tipo = $this->tipoCirugia(['ARIX-MAN' => true]);
        $s = $this->sugerir($this->nota(E::BORRADOR, ['tipo_cirugia_id' => $tipo->id, 'bodega_origen_id' => $principal->id]))['ARIX-MAN'];

        $this->assertSame('P13NAC9', $s['propuesto']['codigo']);
        $this->assertTrue($s['propuesto']['misma_bodega']);
        $this->assertFalse($s['otras_disponibles'][0]['misma_bodega']);
    }

    public function test_equipo_ya_en_la_nota(): void
    {
        $equipo = $this->equipo('ARIX-MAN', null, 'P13NAC7');
        $tipo = $this->tipoCirugia(['ARIX-MAN' => true]);
        $nota = $this->nota(E::BORRADOR, ['tipo_cirugia_id' => $tipo->id]);
        $this->ponerEquipoEnNota($nota, $equipo);

        $this->assertSame(S::YA_EN_NOTA, $this->sugerir($nota)['ARIX-MAN']['estado']);
    }

    public function test_sin_tipo_de_cirugia_no_hay_sugerencias(): void
    {
        $this->assertSame([], app(S::class)->sugerir($this->nota()));
    }
}
