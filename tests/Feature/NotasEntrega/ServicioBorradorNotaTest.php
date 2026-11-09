<?php

namespace Tests\Feature\NotasEntrega;

use App\Enums\EstadoNotaEntrega as E;
use App\Exceptions\ReglaNegocioException;
use App\Models\Bitacora;
use App\Models\NotaEntregaPaciente;
use App\Services\NotasEntrega\EquipoNoDisponibleException;
use App\Services\NotasEntrega\NotaModificadaException;
use App\Services\NotasEntrega\ServicioBorradorNota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\DatosOperativos;
use Tests\TestCase;

class ServicioBorradorNotaTest extends TestCase
{
    use DatosOperativos;
    use RefreshDatabase;

    private ServicioBorradorNota $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = app(ServicioBorradorNota::class);
    }

    private function datosMinimos(): array
    {
        return [
            'institucion_id' => $this->institucion()->id,
            'bodega_origen_id' => $this->bodega()->id,
        ];
    }

    public function test_crea_borrador_con_numero_consecutivo_y_auditoria(): void
    {
        $preparador = $this->usuarioConRol('PREPARADOR');

        $a = $this->servicio->crear($this->datosMinimos(), $preparador);
        $b = $this->servicio->crear($this->datosMinimos() + ['observaciones' => 'Paciente por confirmar'], $preparador);

        $this->assertMatchesRegularExpression('/^NE\d{10}$/', $a->numero);
        $this->assertSame((int) substr($a->numero, 4) + 1, (int) substr($b->numero, 4));
        $this->assertSame(E::BORRADOR, $a->estado);
        $this->assertSame($preparador->id, $a->creado_por_id);
        $this->assertSame(1, Bitacora::where('evento', 'NOTA_CREADA')->where('nota_entrega_id', $a->id)->count());
    }

    public function test_paciente_se_guarda_cifrado_y_la_bitacora_no_guarda_valores(): void
    {
        $nota = $this->servicio->crear($this->datosMinimos(), $this->usuarioConRol('PREPARADOR'), [
            'identificacion' => '0912345678',
            'nombres' => 'JUAN PÉREZ',
        ]);

        $crudo = DB::table('nota_entrega_pacientes')->where('nota_entrega_id', $nota->id)->first();
        $this->assertStringNotContainsString('0912345678', $crudo->identificacion);
        $this->assertStringNotContainsString('JUAN', $crudo->nombres);
        $this->assertSame(NotaEntregaPaciente::hashIdentificacion('0912-345678'), $crudo->identificacion_hash);
        $this->assertSame('JUAN PÉREZ', $nota->paciente->nombres);

        $registro = Bitacora::where('evento', 'PACIENTE_REGISTRADO')->first();
        $this->assertEqualsCanonicalizing(['identificacion', 'nombres'], $registro->valores_nuevos['campos']);
        $this->assertStringNotContainsString('0912345678', json_encode($registro->getAttributes()));
    }

    public function test_ver_paciente_deja_constancia_del_acceso(): void
    {
        $usuario = $this->usuarioConRol('GERENTE');
        $nota = $this->servicio->crear($this->datosMinimos(), $usuario, ['nombres' => 'ANA']);

        $this->assertSame('ANA', $this->servicio->verPaciente($nota, $usuario)['nombres']);
        $this->assertDatabaseHas('bitacora', ['evento' => 'PACIENTE_ACCESO', 'nota_entrega_id' => $nota->id, 'usuario_id' => $usuario->id]);
    }

    public function test_actualizar_audita_valor_anterior_y_nuevo(): void
    {
        $usuario = $this->usuarioConRol('PREPARADOR');
        $nota = $this->servicio->crear($this->datosMinimos(), $usuario);
        $tipo = $this->tipoCirugia([]);

        $this->servicio->actualizar($nota, ['tipo_cirugia_id' => $tipo->id], $usuario, $nota->version);

        $this->assertSame(2, $nota->fresh()->version);
        $registro = Bitacora::where('evento', 'NOTA_ACTUALIZADA')->first();
        $this->assertSame(['tipo_cirugia_id' => null], $registro->valores_anteriores);
        $this->assertSame(['tipo_cirugia_id' => $tipo->id], $registro->valores_nuevos);
    }

    public function test_actualizar_con_version_vieja_falla(): void
    {
        $usuario = $this->usuarioConRol('PREPARADOR');
        $nota = $this->servicio->crear($this->datosMinimos(), $usuario);
        $this->servicio->actualizar($nota, ['observaciones' => 'A'], $usuario, 1);

        $this->expectException(NotaModificadaException::class);
        $this->servicio->actualizar($nota, ['observaciones' => 'B'], $usuario, 1);
    }

    public function test_no_se_edita_despues_de_alistada(): void
    {
        $this->expectException(ReglaNegocioException::class);

        $this->servicio->actualizar($this->nota(E::ALISTADA), ['observaciones' => 'X'], $this->usuarioConRol('PREPARADOR'));
    }

    public function test_agregar_equipo_valida_mantenimiento_ocupacion_y_duplicado(): void
    {
        $usuario = $this->usuarioConRol('PREPARADOR');
        $nota = $this->nota();

        $libre = $this->equipo();
        $this->servicio->agregarEquipo($nota, $libre, $usuario);
        $this->assertSame(1, $nota->lineasEquipo()->count());
        $this->assertDatabaseHas('bitacora', ['evento' => 'NOTA_EQUIPO_AGREGADO', 'nota_entrega_id' => $nota->id]);

        try {
            $this->servicio->agregarEquipo($nota, $libre, $usuario);
            $this->fail('Duplicado aceptado');
        } catch (EquipoNoDisponibleException $e) {
            $this->assertStringContainsString('ya está en esta nota', $e->getMessage());
        }

        try {
            $this->servicio->agregarEquipo($nota, $this->equipo(estado: 'EN_MANTENIMIENTO'), $usuario);
            $this->fail('Equipo en mantenimiento aceptado');
        } catch (EquipoNoDisponibleException $e) {
            $this->assertStringContainsString('EN_MANTENIMIENTO', $e->getMessage());
        }

        $ocupado = $this->equipo();
        $otra = $this->nota(E::ENTREGADA);
        $this->ponerEquipoEnNota($otra, $ocupado);

        try {
            $this->servicio->agregarEquipo($nota, $ocupado, $usuario);
            $this->fail('Equipo ocupado aceptado');
        } catch (EquipoNoDisponibleException $e) {
            $this->assertStringContainsString(trim($otra->numero), $e->getMessage());
            $this->assertSame($otra->id, $e->detalles[0]['nota_id']);
        }

        $this->assertSame(1, $nota->lineasEquipo()->count());
    }

    public function test_aceptar_sugerencias_agrega_las_disponibles_y_reporta_las_rechazadas(): void
    {
        $usuario = $this->usuarioConRol('PREPARADOR');
        $nota = $this->nota();
        $a = $this->equipo();
        $b = $this->equipo();
        $this->ponerEquipoEnNota($this->nota(E::DESPACHADA), $b);

        $r = $this->servicio->aceptarSugerencias($nota, [$a->id, $b->id], $usuario);

        $this->assertCount(1, $r['agregados']);
        $this->assertSame($b->id, $r['rechazados'][0]['equipo_id']);
        $this->assertSame(
            'SUGERENCIA',
            Bitacora::where('evento', 'NOTA_EQUIPO_AGREGADO')->first()->valores_nuevos['origen']
        );
    }

    public function test_quitar_equipo_en_alistamiento_exige_motivo(): void
    {
        $usuario = $this->usuarioConRol('PREPARADOR');
        $nota = $this->nota(E::EN_ALISTAMIENTO);
        $linea = $this->ponerEquipoEnNota($nota, $this->equipo());

        try {
            $this->servicio->quitarEquipo($nota, $linea, $usuario);
            $this->fail('Se retiró sin motivo');
        } catch (ReglaNegocioException) {
        }

        $this->servicio->quitarEquipo($nota, $linea, $usuario, 'Pedido del médico');

        $this->assertSame(0, $nota->lineasEquipo()->count());
        $this->assertDatabaseHas('bitacora', ['evento' => 'NOTA_EQUIPO_RETIRADO', 'motivo' => 'Pedido del médico']);
    }

    public function test_dos_borradores_con_el_mismo_equipo_gana_el_primero_en_alistar(): void
    {
        $usuario = $this->usuarioConRol('PREPARADOR');
        $equipo = $this->equipo();
        $primera = $this->nota();
        $segunda = $this->nota();
        $this->servicio->agregarEquipo($primera, $equipo, $usuario);
        $this->servicio->agregarEquipo($segunda, $equipo, $usuario); // permitido: ambos borradores

        $this->servicio->iniciarAlistamiento($primera, $usuario);
        $this->assertSame(E::EN_ALISTAMIENTO, $primera->fresh()->estado);
        $this->assertSame($usuario->id, $primera->fresh()->preparador_id);

        try {
            $this->servicio->iniciarAlistamiento($segunda, $usuario);
            $this->fail('Se alistaron dos notas con el mismo equipo');
        } catch (EquipoNoDisponibleException $e) {
            $this->assertStringContainsString(trim($primera->numero), $e->getMessage());
        }

        $this->assertSame(E::BORRADOR, $segunda->fresh()->estado);
    }

    public function test_no_inicia_alistamiento_sin_equipos(): void
    {
        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('al menos un equipo');

        $this->servicio->iniciarAlistamiento($this->nota(), $this->usuarioConRol('PREPARADOR'));
    }
}
