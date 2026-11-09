<?php

namespace Tests\Feature\NotasEntrega;

use App\Enums\EstadoNotaEntrega as E;
use App\Models\NotaEntrega;
use App\Services\NotasEntrega\ServicioBorradorNota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\DatosOperativos;
use Tests\TestCase;

/**
 * Permisos validados en el servidor (no solo ocultando botones).
 */
class NotasEntregaHttpTest extends TestCase
{
    use DatosOperativos;
    use RefreshDatabase;

    public function test_preparador_crea_borrador_desde_el_formulario(): void
    {
        $preparador = $this->usuarioConRol('PREPARADOR');
        $institucion = $this->institucion();
        $bodega = $this->bodega();

        $this->actingAs($preparador)->get(route('notas_entrega.create'))->assertOk();

        $respuesta = $this->actingAs($preparador)->post(route('notas_entrega.store'), [
            'institucion_id' => $institucion->id,
            'bodega_origen_id' => $bodega->id,
            'fecha_cirugia_programada' => now()->addDay()->format('Y-m-d\T07:00'),
            'paciente' => ['nombres' => 'POR CONFIRMAR'],
        ]);

        $nota = NotaEntrega::firstOrFail();
        $respuesta->assertRedirect(route('notas_entrega.show', $nota));
        $this->assertSame('POR CONFIRMAR', $nota->paciente->nombres);
    }

    public function test_instrumentador_y_facturacion_no_crean_notas(): void
    {
        foreach (['INSTRUMENTADOR', 'FACTURACION'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->post(route('notas_entrega.store'), [
                    'institucion_id' => $this->institucion()->id,
                    'bodega_origen_id' => $this->bodega()->id,
                ])
                ->assertForbidden();
        }

        $this->assertSame(0, NotaEntrega::count());
    }

    public function test_instrumentador_solo_ve_sus_notas(): void
    {
        $instrumentador = $this->usuarioConRol('INSTRUMENTADOR');
        $suya = $this->nota(E::ENTREGADA, ['instrumentador_id' => $instrumentador->id]);
        $ajena = $this->nota(E::ENTREGADA);

        $this->actingAs($instrumentador)->get(route('notas_entrega.show', $suya))->assertOk()->assertSee($suya->numero);
        $this->actingAs($instrumentador)->get(route('notas_entrega.show', $ajena))->assertForbidden();

        $this->actingAs($instrumentador)->get(route('notas_entrega.index'))
            ->assertOk()
            ->assertSee($suya->numero)
            ->assertDontSee($ajena->numero);
    }

    public function test_facturacion_no_accede_al_listado(): void
    {
        $this->actingAs($this->usuarioConRol('FACTURACION'))->get(route('notas_entrega.index'))->assertForbidden();
    }

    public function test_abrir_la_nota_registra_acceso_al_paciente(): void
    {
        $gerente = $this->usuarioConRol('GERENTE');
        $nota = app(ServicioBorradorNota::class)->crear([
            'institucion_id' => $this->institucion()->id,
            'bodega_origen_id' => $this->bodega()->id,
        ], $gerente, ['nombres' => 'MARÍA LÓPEZ']);

        $this->actingAs($gerente)->get(route('notas_entrega.show', $nota))->assertOk()->assertSee('MARÍA LÓPEZ');

        $this->assertDatabaseHas('bitacora', ['evento' => 'PACIENTE_ACCESO', 'usuario_id' => $gerente->id]);
    }

    public function test_sugerencias_solo_para_quien_alista(): void
    {
        $tipo = $this->tipoCirugia(['ARIX-MAN' => true]);
        $this->equipo('ARIX-MAN', null, 'P13NAC7');
        $nota = $this->nota(E::BORRADOR, ['tipo_cirugia_id' => $tipo->id]);

        $this->actingAs($this->usuarioConRol('PREPARADOR'))
            ->getJson(route('notas_entrega.sugerencias', $nota))
            ->assertOk()
            ->assertJsonPath('sugerencias.0.propuesto.codigo', 'P13NAC7');

        $this->actingAs($this->usuarioConRol('GERENTE'))
            ->getJson(route('notas_entrega.sugerencias', $nota))
            ->assertForbidden();
    }

    public function test_aceptar_sugerencias_por_http(): void
    {
        $preparador = $this->usuarioConRol('PREPARADOR');
        $equipo = $this->equipo('ARIX-MAN', null, 'P13NAC7');
        $nota = $this->nota();

        $this->actingAs($preparador)
            ->postJson(route('notas_entrega.sugerencias.aceptar', $nota), ['equipos' => [$equipo->id]])
            ->assertOk()
            ->assertJsonPath('agregados.0', 'P13NAC7');

        $this->assertSame(1, $nota->lineasEquipo()->count());
    }

    public function test_agregar_equipo_ocupado_responde_422_con_detalle(): void
    {
        $equipo = $this->equipo();
        $otra = $this->nota(E::DESPACHADA);
        $this->ponerEquipoEnNota($otra, $equipo);

        $this->actingAs($this->usuarioConRol('PREPARADOR'))
            ->postJson(route('notas_entrega.equipos.store', $this->nota()), ['equipo_id' => $equipo->id])
            ->assertStatus(422)
            ->assertJsonPath('detalles.0.nota_id', $otra->id);
    }

    public function test_no_se_agregan_equipos_a_una_nota_despachada(): void
    {
        $this->actingAs($this->usuarioConRol('PREPARADOR'))
            ->postJson(route('notas_entrega.equipos.store', $this->nota(E::DESPACHADA)), ['equipo_id' => $this->equipo()->id])
            ->assertForbidden();
    }

    public function test_busqueda_de_equipos_indica_disponibilidad(): void
    {
        $this->equipo('ARIX-MAN', null, 'P13NAC7');
        $this->equipo('ARIX-MAN', null, 'P13NAC8', estado: 'EN_MANTENIMIENTO');

        $this->actingAs($this->usuarioConRol('PREPARADOR'))
            ->getJson(route('notas_entrega.equipos.buscar', $this->nota()) . '?q=P13NAC')
            ->assertOk()
            ->assertJsonPath('equipos.0.disponible', true)
            ->assertJsonPath('equipos.1.disponible', false);
    }

    public function test_iniciar_alistamiento_y_anular_por_http(): void
    {
        $preparador = $this->usuarioConRol('PREPARADOR');
        $nota = $this->nota();
        $this->ponerEquipoEnNota($nota, $this->equipo());

        $this->actingAs($preparador)
            ->post(route('notas_entrega.iniciar_alistamiento', $nota), ['version' => $nota->version])
            ->assertRedirect();
        $this->assertSame(E::EN_ALISTAMIENTO, $nota->fresh()->estado);

        $this->actingAs($preparador)->post(route('notas_entrega.anular', $nota), ['motivo' => ''])->assertSessionHasErrors('motivo');

        $this->actingAs($preparador)->post(route('notas_entrega.anular', $nota), ['motivo' => 'Cirugía cancelada por el médico'])->assertRedirect();
        $this->assertSame(E::ANULADA, $nota->fresh()->estado);
    }

    public function test_plantillas_solo_las_edita_un_usuario_autorizado(): void
    {
        $preparador = $this->usuarioConRol('PREPARADOR');
        $admin = $this->usuarioConRol('ADMINISTRADOR');
        $this->equipo('ARIX-MAN');

        $this->actingAs($preparador)->get(route('tipos_cirugia.index'))->assertOk();
        $this->actingAs($preparador)->post(route('tipos_cirugia.store'), ['codigo' => 'RD', 'nombre' => 'Radio distal'])->assertForbidden();

        $this->actingAs($admin)->post(route('tipos_cirugia.store'), ['codigo' => 'rd', 'nombre' => 'Radio distal'])->assertRedirect();
        $tipo = \App\Models\TipoCirugia::where('codigo', 'RD')->firstOrFail();

        $this->actingAs($preparador)->post(route('tipos_cirugia.equipos.store', $tipo), ['codigo_referencia' => 'ARIX-MAN'])->assertForbidden();
        $this->actingAs($admin)->post(route('tipos_cirugia.equipos.store', $tipo), ['codigo_referencia' => 'ARIX-MAN', 'obligatorio' => 1])->assertRedirect();
        $this->actingAs($admin)->post(route('tipos_cirugia.equipos.store', $tipo), ['codigo_referencia' => 'NO-EXISTE'])->assertSessionHasErrors('codigo_referencia');

        $this->assertSame(['ARIX-MAN'], $tipo->plantilla()->pluck('codigo_referencia')->all());
        $this->actingAs($admin)->get(route('tipos_cirugia.edit', $tipo))->assertOk()->assertSee('ARIX-MAN');
    }

    public function test_pantalla_de_la_nota_se_muestra_para_el_preparador(): void
    {
        $preparador = $this->usuarioConRol('PREPARADOR');
        $tipo = $this->tipoCirugia(['ARIX-MAN' => true]);
        $nota = $this->nota(E::BORRADOR, ['tipo_cirugia_id' => $tipo->id]);
        $this->ponerEquipoEnNota($nota, $this->equipo('ARIX-MAN', null, 'P13NAC7'));

        $this->actingAs($preparador)->get(route('notas_entrega.show', $nota))
            ->assertOk()
            ->assertSee('P13NAC7')
            ->assertSee('Sugerir equipos para Radio distal')
            ->assertSee('Iniciar alistamiento');
    }
}
