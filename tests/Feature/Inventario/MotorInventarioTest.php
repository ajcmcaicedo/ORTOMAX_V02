<?php

namespace Tests\Feature\Inventario;

use App\Enums\TipoMovimiento;
use App\Models\MovimientoInventario;
use App\Models\StockLote;
use App\Services\Inventario\LineaMovimiento;
use App\Services\Inventario\MotorInventario;
use App\Services\Inventario\PuntoStock;
use App\Services\Inventario\SolicitudMovimiento;
use App\Services\Inventario\StockInsuficienteException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Support\DatosOperativos;
use Tests\TestCase;

class MotorInventarioTest extends TestCase
{
    use RefreshDatabase;
    use DatosOperativos;

    private MotorInventario $motor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->motor = app(MotorInventario::class);
    }

    public function test_traslado_entre_ubicaciones_conserva_lote_y_registra_saldos(): void
    {
        $bodega = $this->bodega();
        $origen = $this->ubicacion($bodega);
        $destino = $this->ubicacion($this->bodega('CONSIGNACION'), 'CONSIGNACION');
        $lote = $this->lote($this->producto());
        $stock = $this->stock($origen, $lote, 10);
        $nota = $this->nota();

        $mov = $this->motor->registrar(new SolicitudMovimiento(
            tipo: TipoMovimiento::DESPACHO,
            lineas: [new LineaMovimiento(4, PuntoStock::stockLoteId($stock->id), PuntoStock::stockLote(
                $destino->bodega_id, $destino->id, $lote->producto_id, $lote->id
            ))],
            usuarioId: null,
            notaEntregaId: $nota->id,
        ));

        $this->assertSame('6.00', $stock->fresh()->cantidad);
        $enDestino = StockLote::where('ubicacion_id', $destino->id)->where('lote_id', $lote->id)->first();
        $this->assertSame('4.00', $enDestino->cantidad);

        $detalle = $mov->detalles->first();
        $this->assertSame($lote->id, $detalle->lote_id);
        $this->assertSame($nota->id, $mov->nota_entrega_id);
        $this->assertSame('6.00', $detalle->saldo_origen_resultante);
        $this->assertSame('4.00', $detalle->saldo_destino_resultante);
        $this->assertSame($bodega->id, $mov->bodega_origen_id);
    }

    public function test_no_permite_tomar_stock_reservado_por_otra_operacion(): void
    {
        $lote = $this->lote($this->producto());
        $stock = $this->stock($this->ubicacion($this->bodega()), $lote, 5, reservada: 3);

        $this->expectException(StockInsuficienteException::class);

        // Disponible = 2; se piden 3 sin reserva propia.
        $this->motor->registrar(new SolicitudMovimiento(
            tipo: TipoMovimiento::CONSUMO,
            lineas: [new LineaMovimiento(3, PuntoStock::stockLoteId($stock->id), null)],
            usuarioId: null,
        ));
    }

    public function test_despacho_desde_reserva_descuenta_cantidad_y_reserva(): void
    {
        $equipo = $this->equipo();
        $lote = $this->lote($this->producto());
        $contenido = $this->contenidoEquipo($equipo, $lote, 6, reservada: 6);
        $consignacion = $this->ubicacion($this->bodega('CONSIGNACION'), 'CONSIGNACION');

        $this->motor->registrar(new SolicitudMovimiento(
            tipo: TipoMovimiento::DESPACHO,
            lineas: [new LineaMovimiento(
                6,
                PuntoStock::equipoInventarioId($contenido->id),
                PuntoStock::stockLote($consignacion->bodega_id, $consignacion->id, $lote->producto_id, $lote->id),
                tomarDeReserva: true,
            )],
            usuarioId: null,
        ));

        $contenido->refresh();
        $this->assertSame('0.00', $contenido->cantidad);
        $this->assertSame('0.00', $contenido->cantidad_reservada);
    }

    public function test_si_una_linea_falla_no_se_aplica_ninguna(): void
    {
        $ubic = $this->ubicacion($this->bodega());
        $loteA = $this->lote($this->producto());
        $loteB = $this->lote($this->producto());
        $a = $this->stock($ubic, $loteA, 10);
        $b = $this->stock($ubic, $loteB, 1);

        try {
            $this->motor->registrar(new SolicitudMovimiento(
                tipo: TipoMovimiento::CONSUMO,
                lineas: [
                    new LineaMovimiento(5, PuntoStock::stockLoteId($a->id), null),
                    new LineaMovimiento(2, PuntoStock::stockLoteId($b->id), null),
                ],
                usuarioId: null,
            ));
            $this->fail('Se esperaba StockInsuficienteException');
        } catch (StockInsuficienteException) {
        }

        $this->assertSame('10.00', $a->fresh()->cantidad);
        $this->assertSame('1.00', $b->fresh()->cantidad);
        $this->assertSame(0, MovimientoInventario::count());
    }

    public function test_doble_envio_con_misma_clave_genera_un_solo_movimiento(): void
    {
        $lote = $this->lote($this->producto());
        $stock = $this->stock($this->ubicacion($this->bodega()), $lote, 10);

        $solicitud = new SolicitudMovimiento(
            tipo: TipoMovimiento::CONSUMO,
            lineas: [new LineaMovimiento(3, PuntoStock::stockLoteId($stock->id), null)],
            usuarioId: null,
            idempotencyKey: 'consumo:nota-1:envio-abc',
        );

        $primero = $this->motor->registrar($solicitud);
        $segundo = $this->motor->registrar($solicitud);

        $this->assertSame($primero->id, $segundo->id);
        $this->assertSame('7.00', $stock->fresh()->cantidad);
        $this->assertSame(1, MovimientoInventario::count());
    }

    public function test_reversa_restaura_saldos_en_el_mismo_lote_y_solo_una_vez(): void
    {
        $origen = $this->ubicacion($this->bodega());
        $destino = $this->ubicacion($this->bodega('CONSIGNACION'), 'CONSIGNACION');
        $lote = $this->lote($this->producto());
        $stock = $this->stock($origen, $lote, 8);
        $usuario = $this->usuarioConRol('GERENTE');

        $mov = $this->motor->registrar(new SolicitudMovimiento(
            tipo: TipoMovimiento::DESPACHO,
            lineas: [new LineaMovimiento(5, PuntoStock::stockLoteId($stock->id), PuntoStock::stockLote(
                $destino->bodega_id, $destino->id, $lote->producto_id, $lote->id
            ))],
            usuarioId: $usuario->id,
        ));

        $reversa = $this->motor->reversar($mov, $usuario->id, 'Cirugía cancelada');
        $otraVez = $this->motor->reversar($mov, $usuario->id, 'Cirugía cancelada');

        $this->assertSame($reversa->id, $otraVez->id);
        $this->assertSame(TipoMovimiento::REVERSA, $reversa->tipo_movimiento);
        $this->assertSame($mov->id, $reversa->movimiento_reversado_id);
        $this->assertSame('8.00', $stock->fresh()->cantidad);
        $this->assertSame('0.00', StockLote::where('ubicacion_id', $destino->id)->value('cantidad'));
        $this->assertDatabaseHas('bitacora', ['evento' => 'MOVIMIENTO_REVERSADO', 'auditable_id' => $mov->id]);
    }

    public function test_reversa_falla_si_el_material_ya_se_consumio(): void
    {
        $origen = $this->ubicacion($this->bodega());
        $destino = $this->ubicacion($this->bodega('CONSIGNACION'), 'CONSIGNACION');
        $lote = $this->lote($this->producto());
        $stock = $this->stock($origen, $lote, 5);

        $despacho = $this->motor->registrar(new SolicitudMovimiento(
            tipo: TipoMovimiento::DESPACHO,
            lineas: [new LineaMovimiento(5, PuntoStock::stockLoteId($stock->id), PuntoStock::stockLote(
                $destino->bodega_id, $destino->id, $lote->producto_id, $lote->id
            ))],
            usuarioId: null,
        ));

        $consignado = StockLote::where('ubicacion_id', $destino->id)->first();
        $this->motor->registrar(new SolicitudMovimiento(
            tipo: TipoMovimiento::CONSUMO,
            lineas: [new LineaMovimiento(2, PuntoStock::stockLoteId($consignado->id), null)],
            usuarioId: null,
        ));

        // Primero hay que reversar el consumo (orden inverso).
        $this->expectException(StockInsuficienteException::class);
        $this->motor->reversar($despacho, $this->usuarioConRol('GERENTE')->id, 'Anulación');
    }

    public function test_movimientos_no_se_eliminan(): void
    {
        $lote = $this->lote($this->producto());
        $stock = $this->stock($this->ubicacion($this->bodega()), $lote, 1);
        $mov = $this->motor->registrar(new SolicitudMovimiento(
            tipo: TipoMovimiento::CONSUMO,
            lineas: [new LineaMovimiento(1, PuntoStock::stockLoteId($stock->id), null)],
            usuarioId: null,
        ));

        $this->expectException(LogicException::class);
        $mov->delete();
    }

    public function test_devolucion_al_equipo_crea_la_linea_si_no_existe(): void
    {
        $equipo = $this->equipo();
        $lote = $this->lote($this->producto());
        $stock = $this->stock($this->ubicacion($this->bodega()), $lote, 3);

        $mov = $this->motor->registrar(new SolicitudMovimiento(
            tipo: TipoMovimiento::REPOSICION,
            lineas: [new LineaMovimiento(2, PuntoStock::stockLoteId($stock->id), PuntoStock::enEquipo($equipo->id, $lote->producto_id, $lote->id))],
            usuarioId: null,
        ));

        $this->assertSame('2.00', $equipo->inventario()->where('lote_id', $lote->id)->value('cantidad'));
        $this->assertSame($equipo->id, $mov->equipo_destino_id);
    }
}
