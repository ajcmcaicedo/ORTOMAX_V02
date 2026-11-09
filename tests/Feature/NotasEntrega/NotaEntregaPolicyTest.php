<?php

namespace Tests\Feature\NotasEntrega;

use App\Enums\EstadoNotaEntrega as E;
use App\Models\NotaEntrega;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\DatosOperativos;
use Tests\TestCase;

/**
 * Matriz de permisos por rol (sección 8 del plan), evaluada en el
 * servidor mediante NotaEntregaPolicy.
 */
class NotaEntregaPolicyTest extends TestCase
{
    use RefreshDatabase;
    use DatosOperativos;

    public static function matriz(): array
    {
        // [rol, habilidad, estado de la nota, ¿asignada?, resultado esperado]
        return [
            'preparador alista borrador' => ['PREPARADOR', 'alistar', E::BORRADOR, false, true],
            'preparador alista en alistamiento' => ['PREPARADOR', 'alistar', E::EN_ALISTAMIENTO, false, true],
            'preparador no edita despachada' => ['PREPARADOR', 'alistar', E::DESPACHADA, false, false],
            'preparador despacha alistada' => ['PREPARADOR', 'despachar', E::ALISTADA, false, true],
            'preparador no despacha borrador' => ['PREPARADOR', 'despachar', E::BORRADOR, false, false],
            'preparador no anula despachada' => ['PREPARADOR', 'anular', E::DESPACHADA, false, false],
            'preparador anula borrador' => ['PREPARADOR', 'anular', E::BORRADOR, false, true],
            'preparador no registra consumo' => ['PREPARADOR', 'registrarConsumo', E::ENTREGADA, true, false],
            'preparador ve notas sin asignar' => ['PREPARADOR', 'view', E::BORRADOR, false, true],
            'instrumentador ve nota asignada' => ['INSTRUMENTADOR', 'view', E::ENTREGADA, true, true],
            'instrumentador no ve nota ajena' => ['INSTRUMENTADOR', 'view', E::ENTREGADA, false, false],
            'instrumentador registra consumo asignado' => ['INSTRUMENTADOR', 'registrarConsumo', E::ENTREGADA, true, true],
            'instrumentador no registra consumo ajeno' => ['INSTRUMENTADOR', 'registrarConsumo', E::ENTREGADA, false, false],
            'instrumentador no registra si no entregada' => ['INSTRUMENTADOR', 'registrarConsumo', E::DESPACHADA, true, false],
            'instrumentador confirma entrega asignada' => ['INSTRUMENTADOR', 'confirmarEntrega', E::DESPACHADA, true, true],
            'instrumentador no alista' => ['INSTRUMENTADOR', 'alistar', E::BORRADOR, true, false],
            'gerente ve todo' => ['GERENTE', 'view', E::ENTREGADA, false, true],
            'gerente anula despachada' => ['GERENTE', 'anular', E::DESPACHADA, false, true],
            'gerente no anula cerrada' => ['GERENTE', 'anular', E::CERRADA, false, false],
            'gerente autoriza ajuste' => ['GERENTE', 'ajustar', E::ENTREGADA, false, true],
            'gerente no despacha' => ['GERENTE', 'despachar', E::ALISTADA, false, false],
            'jefe bodega verifica devolucion' => ['JEFE_BODEGA', 'verificarDevolucion', E::EN_CONCILIACION, false, true],
            'jefe bodega no verifica entregada' => ['JEFE_BODEGA', 'verificarDevolucion', E::ENTREGADA, false, false],
            'preparador no verifica devolucion' => ['PREPARADOR', 'verificarDevolucion', E::EN_CONCILIACION, false, false],
            'facturacion no ve notas' => ['FACTURACION', 'view', E::ENTREGADA, false, false],
            'facturacion no ve paciente' => ['FACTURACION', 'verPaciente', E::ENTREGADA, false, false],
            'instrumentador ve paciente asignado' => ['INSTRUMENTADOR', 'verPaciente', E::ENTREGADA, true, true],
            'gerente ve paciente' => ['GERENTE', 'verPaciente', E::ENTREGADA, false, true],
        ];
    }

    #[DataProvider('matriz')]
    public function test_matriz_de_permisos(string $rol, string $habilidad, E $estado, bool $asignada, bool $esperado): void
    {
        $usuario = $this->usuarioConRol($rol);
        $nota = $this->nota($estado, $asignada ? ['instrumentador_id' => $usuario->id] : []);

        $this->assertSame(
            $esperado,
            Gate::forUser($usuario)->allows($habilidad, $nota),
            "{$rol} → {$habilidad} con nota {$estado->value}"
        );
    }

    public function test_crear_nota_solo_gerente_y_preparador(): void
    {
        foreach (['GERENTE' => true, 'PREPARADOR' => true, 'INSTRUMENTADOR' => false, 'FACTURACION' => false] as $rol => $esperado) {
            $this->assertSame($esperado, $this->usuarioConRol($rol)->can('create', NotaEntrega::class), $rol);
        }
    }

    public function test_usuario_sin_roles_no_puede_nada(): void
    {
        $usuario = \App\Models\User::factory()->create();
        $nota = $this->nota(E::BORRADOR);

        foreach (['view', 'alistar', 'despachar', 'anular', 'registrarConsumo'] as $habilidad) {
            $this->assertFalse(Gate::forUser($usuario)->allows($habilidad, $nota), $habilidad);
        }
    }
}
