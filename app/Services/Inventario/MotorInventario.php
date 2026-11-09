<?php

namespace App\Services\Inventario;

use App\Enums\TipoMovimiento;
use App\Exceptions\ReglaNegocioException;
use App\Models\Bitacora;
use App\Models\Equipo;
use App\Models\EquipoInventario;
use App\Models\MovimientoInventario;
use App\Models\MovimientoInventarioDetalle;
use App\Models\StockLote;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Único punto del sistema que modifica saldos de inventario
 * (stock_lotes y equipo_inventario).
 *
 * Garantías:
 * - Todo cambio queda en movimientos_inventario + detalle con saldo resultante.
 * - Transacción con bloqueo de filas (lockForUpdate) en orden determinista
 *   para evitar que dos usuarios tomen el mismo stock y evitar deadlocks.
 * - Nunca deja saldos negativos ni toma stock reservado por otra operación.
 * - Idempotente por idempotency_key.
 *
 * Las cantidades se operan en centésimas enteras (decimal 12,2) para
 * evitar errores de punto flotante.
 */
class MotorInventario
{
    public function registrar(SolicitudMovimiento $solicitud): MovimientoInventario
    {
        if ($solicitud->idempotencyKey !== null) {
            $existente = $this->buscarPorClave($solicitud->idempotencyKey);

            if ($existente) {
                return $existente;
            }
        }

        try {
            return DB::transaction(fn () => $this->ejecutar($solicitud));
        } catch (UniqueConstraintViolationException $e) {
            /*
             * Otra petición con la misma clave terminó primero
             * (doble clic simultáneo). Se devuelve la suya.
             */
            if ($solicitud->idempotencyKey !== null) {
                $existente = $this->buscarPorClave($solicitud->idempotencyKey);

                if ($existente) {
                    return $existente;
                }
            }

            throw $e;
        }
    }

    /**
     * Reversa un movimiento invirtiendo origen y destino de cada línea.
     * Un movimiento se puede reversar una sola vez.
     */
    public function reversar(
        MovimientoInventario $original,
        int $usuarioId,
        string $motivo,
        ?string $idempotencyKey = null,
    ): MovimientoInventario {
        if (trim($motivo) === '') {
            throw new ReglaNegocioException('La reversa requiere un motivo.');
        }

        if ($original->tipo_movimiento === TipoMovimiento::REVERSA) {
            throw new ReglaNegocioException('Una reversa no se puede reversar.');
        }

        $existente = MovimientoInventario::where('movimiento_reversado_id', $original->id)->first();

        if ($existente) {
            return $existente;
        }

        $lineas = $original->detalles()->orderByDesc('id')->get()->map(
            fn (MovimientoInventarioDetalle $d) => new LineaMovimiento(
                cantidad: $d->cantidad,
                origen: $this->puntoDesdeDetalle($d, 'destino'),
                destino: $this->puntoDesdeDetalle($d, 'origen'),
                notaEntregaDetalleId: $d->nota_entrega_detalle_id,
                activoId: $d->activo_id,
                instrumentalId: $d->instrumental_id,
            )
        )->all();

        $solicitud = new SolicitudMovimiento(
            tipo: TipoMovimiento::REVERSA,
            lineas: $lineas,
            usuarioId: $usuarioId,
            notaEntregaId: $original->nota_entrega_id,
            idempotencyKey: $idempotencyKey ?? 'reversa:' . $original->id,
            referencia: 'REVERSA #' . $original->id,
            observaciones: $motivo,
        );

        try {
            $reversa = DB::transaction(function () use ($solicitud, $original, $usuarioId, $motivo) {
                $reversa = $this->ejecutar($solicitud, $original->id);

                Bitacora::registrar(
                    evento: 'MOVIMIENTO_REVERSADO',
                    auditable: $original,
                    notaEntregaId: $original->nota_entrega_id,
                    nuevo: ['movimiento_reversa_id' => $reversa->id],
                    motivo: $motivo,
                    usuarioId: $usuarioId,
                );

                return $reversa;
            });
        } catch (UniqueConstraintViolationException $e) {
            $reversa = MovimientoInventario::where('movimiento_reversado_id', $original->id)->first();

            if (!$reversa) {
                throw $e;
            }
        }

        return $reversa;
    }

    /**
     * Punto de extensión para pruebas de concurrencia: se invoca con
     * las filas ya bloqueadas, antes de modificar saldos.
     */
    protected function despuesDeBloquear(): void
    {
    }

    private function ejecutar(SolicitudMovimiento $s, ?int $reversadoId = null): MovimientoInventario
    {
        /*
         * 1. Cabecera primero: si otra transacción con la misma
         *    idempotency_key está en curso, esta espera en el índice
         *    único y luego falla sin haber tocado stock.
         */
        $movimiento = MovimientoInventario::create([
            'fecha_hora' => now(),
            'tipo_movimiento' => $s->tipo,
            'nota_entrega_id' => $s->notaEntregaId,
            'usuario_id' => $s->usuarioId,
            'referencia' => $s->referencia,
            'idempotency_key' => $s->idempotencyKey,
            'movimiento_reversado_id' => $reversadoId,
            'observaciones' => $s->observaciones,
            'estado' => 'COMPLETADO',
        ]);

        // 2. Resolver puntos (crear destinos faltantes) y bloquear filas.
        $resueltas = [];
        $idsStock = [];
        $idsEquipo = [];

        foreach ($s->lineas as $linea) {
            $cantidad = $this->aCentesimas($linea->cantidad);

            if ($cantidad <= 0) {
                throw new InvalidArgumentException('La cantidad debe ser mayor que cero.');
            }

            $origen = $linea->origen ? $this->resolver($linea->origen, false) : null;
            $destino = $linea->destino ? $this->resolver($linea->destino, true) : null;

            foreach ([$origen, $destino] as $punto) {
                if ($punto) {
                    $punto[0] === PuntoStock::STOCK_LOTE
                        ? $idsStock[] = $punto[1]
                        : $idsEquipo[] = $punto[1];
                }
            }

            $resueltas[] = [$linea, $cantidad, $origen, $destino];
        }

        $filas = $this->bloquear($idsStock, $idsEquipo);

        $this->despuesDeBloquear();

        // 3. Aplicar cada línea sobre los saldos bloqueados.
        $saldos = [];

        foreach ($filas as $clave => $fila) {
            $saldos[$clave] = [
                'cantidad' => $this->aCentesimas($fila->cantidad),
                'reservada' => $this->aCentesimas($fila->cantidad_reservada),
            ];
        }

        $detalles = [];

        foreach ($resueltas as [$linea, $cantidad, $origen, $destino]) {
            $datos = [
                'movimiento_inventario_id' => $movimiento->id,
                'nota_entrega_detalle_id' => $linea->notaEntregaDetalleId,
                'activo_id' => $linea->activoId,
                'instrumental_id' => $linea->instrumentalId,
                'cantidad' => $this->aDecimal($cantidad),
            ];

            if ($origen) {
                $clave = $origen[0] . ':' . $origen[1];
                $fila = $filas[$clave];
                $saldo = &$saldos[$clave];

                $disponible = $saldo['cantidad'] - $saldo['reservada'];

                if ($linea->tomarDeReserva) {
                    if ($saldo['reservada'] < $cantidad || $saldo['cantidad'] < $cantidad) {
                        throw new StockInsuficienteException(sprintf(
                            'La cantidad reservada (%s) no alcanza para %s en %s.',
                            $this->aDecimal($saldo['reservada']),
                            $this->aDecimal($cantidad),
                            $this->describir($fila)
                        ));
                    }

                    $saldo['reservada'] -= $cantidad;
                } elseif ($disponible < $cantidad) {
                    throw new StockInsuficienteException(sprintf(
                        'Stock disponible insuficiente (%s) para mover %s en %s.',
                        $this->aDecimal($disponible),
                        $this->aDecimal($cantidad),
                        $this->describir($fila)
                    ));
                }

                $saldo['cantidad'] -= $cantidad;

                $datos += $this->datosPunto($fila, 'origen');
                $datos['saldo_origen_resultante'] = $this->aDecimal($saldo['cantidad']);
                unset($saldo);
            }

            if ($destino) {
                $clave = $destino[0] . ':' . $destino[1];
                $fila = $filas[$clave];
                $saldo = &$saldos[$clave];

                $saldo['cantidad'] += $cantidad;

                $datos += $this->datosPunto($fila, 'destino');
                $datos['saldo_destino_resultante'] = $this->aDecimal($saldo['cantidad']);
                unset($saldo);
            }

            $referencia = $filas[$origen ? $origen[0] . ':' . $origen[1] : $destino[0] . ':' . $destino[1]];
            $datos['producto_id'] = $referencia->producto_id;
            $datos['lote_id'] = $referencia->lote_id;

            if ($origen && $destino) {
                $this->verificarMismoProductoLote($filas[$origen[0] . ':' . $origen[1]], $filas[$destino[0] . ':' . $destino[1]]);
            }

            $detalles[] = $datos;
        }

        // 4. Persistir saldos finales.
        foreach ($filas as $clave => $fila) {
            $nuevo = $saldos[$clave];

            if ($nuevo['cantidad'] < 0 || $nuevo['reservada'] < 0 || $nuevo['reservada'] > $nuevo['cantidad']) {
                throw new StockInsuficienteException('El movimiento dejaría saldos inválidos en ' . $this->describir($fila) . '.');
            }

            $fila->newQuery()->whereKey($fila->getKey())->update([
                'cantidad' => $this->aDecimal($nuevo['cantidad']),
                'cantidad_reservada' => $this->aDecimal($nuevo['reservada']),
                'updated_at' => now(),
            ]);
        }

        foreach ($detalles as $datos) {
            MovimientoInventarioDetalle::create($datos);
        }

        // Cabecera: origen/destino cuando todas las líneas coinciden.
        $movimiento->forceFill([
            'bodega_origen_id' => $this->valorUnico($detalles, 'bodega_origen_id'),
            'equipo_origen_id' => $this->valorUnico($detalles, 'equipo_origen_id'),
            'bodega_destino_id' => $this->valorUnico($detalles, 'bodega_destino_id'),
            'equipo_destino_id' => $this->valorUnico($detalles, 'equipo_destino_id'),
        ])->save();

        return $movimiento->load('detalles');
    }

    /**
     * Devuelve [tipo, id] del punto; crea la fila destino si no existe.
     */
    private function resolver(PuntoStock $punto, bool $esDestino): array
    {
        if ($punto->id !== null) {
            return [$punto->tipo, $punto->id];
        }

        if ($punto->esStockLote()) {
            $claves = [
                'ubicacion_id' => $punto->ubicacionId,
                'producto_id' => $punto->productoId,
                'lote_id' => $punto->loteId,
            ];

            $id = StockLote::where($claves)->value('id');

            if (!$id) {
                if (!$esDestino) {
                    throw new StockInsuficienteException('No existe stock del lote en la ubicación de origen.');
                }

                // insertOrIgnore respeta el índice único ante creaciones simultáneas.
                StockLote::insertOrIgnore($claves + [
                    'bodega_id' => $punto->bodegaId,
                    'cantidad' => 0,
                    'cantidad_reservada' => 0,
                    'estado' => 'ACTIVO',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $id = StockLote::where($claves)->value('id');
            }

            return [PuntoStock::STOCK_LOTE, $id];
        }

        /*
         * equipo_inventario no tiene índice único por producto/lote:
         * se bloquea el equipo para serializar la creación.
         */
        Equipo::whereKey($punto->equipoId)->lockForUpdate()->firstOrFail();

        $id = EquipoInventario::where('equipo_id', $punto->equipoId)
            ->where('producto_id', $punto->productoId)
            ->whereNull('activo_id')
            ->when(
                $punto->loteId,
                fn ($q) => $q->where('lote_id', $punto->loteId),
                fn ($q) => $q->whereNull('lote_id')
            )
            ->orderBy('id')
            ->value('id');

        if (!$id) {
            if (!$esDestino) {
                throw new StockInsuficienteException('El equipo no contiene ese producto/lote.');
            }

            $id = EquipoInventario::create([
                'equipo_id' => $punto->equipoId,
                'producto_id' => $punto->productoId,
                'lote_id' => $punto->loteId,
                'cantidad' => 0,
                'cantidad_reservada' => 0,
                'estado' => 'ACTIVO',
                'fecha_ingreso' => now(),
            ])->id;
        }

        return [PuntoStock::EQUIPO_INVENTARIO, $id];
    }

    /**
     * Bloquea las filas en orden (tabla, id) para evitar deadlocks.
     *
     * @return array<string, StockLote|EquipoInventario>
     */
    private function bloquear(array $idsStock, array $idsEquipo): array
    {
        $filas = [];

        $idsStock = array_values(array_unique($idsStock));
        sort($idsStock);

        if ($idsStock) {
            $encontradas = StockLote::whereIn('id', $idsStock)->orderBy('id')->lockForUpdate()->get();

            foreach ($encontradas as $fila) {
                $filas[PuntoStock::STOCK_LOTE . ':' . $fila->id] = $fila;
            }
        }

        $idsEquipo = array_values(array_unique($idsEquipo));
        sort($idsEquipo);

        if ($idsEquipo) {
            $encontradas = EquipoInventario::whereIn('id', $idsEquipo)->orderBy('id')->lockForUpdate()->get();

            foreach ($encontradas as $fila) {
                $filas[PuntoStock::EQUIPO_INVENTARIO . ':' . $fila->id] = $fila;
            }
        }

        if (count($filas) !== count($idsStock) + count($idsEquipo)) {
            throw new InvalidArgumentException('Alguno de los puntos de stock no existe.');
        }

        return $filas;
    }

    private function datosPunto(StockLote|EquipoInventario $fila, string $lado): array
    {
        if ($fila instanceof StockLote) {
            return [
                "bodega_{$lado}_id" => $fila->bodega_id,
                "ubicacion_{$lado}_id" => $fila->ubicacion_id,
                "stock_lote_{$lado}_id" => $fila->id,
            ];
        }

        return [
            "equipo_{$lado}_id" => $fila->equipo_id,
            "equipo_inventario_{$lado}_id" => $fila->id,
        ];
    }

    private function puntoDesdeDetalle(MovimientoInventarioDetalle $d, string $lado): ?PuntoStock
    {
        if ($id = $d->{"stock_lote_{$lado}_id"}) {
            return PuntoStock::stockLoteId($id);
        }

        if ($id = $d->{"equipo_inventario_{$lado}_id"}) {
            return PuntoStock::equipoInventarioId($id);
        }

        return null;
    }

    private function verificarMismoProductoLote($origen, $destino): void
    {
        if ($origen->producto_id !== $destino->producto_id || $origen->lote_id !== $destino->lote_id) {
            throw new InvalidArgumentException('Origen y destino deben ser el mismo producto y lote.');
        }
    }

    private function valorUnico(array $detalles, string $campo): ?int
    {
        $valores = array_unique(array_map(fn ($d) => $d[$campo] ?? null, $detalles));

        return count($valores) === 1 ? reset($valores) : null;
    }

    private function describir(StockLote|EquipoInventario $fila): string
    {
        return $fila instanceof StockLote
            ? "stock_lotes #{$fila->id}"
            : "equipo_inventario #{$fila->id} (equipo {$fila->equipo_id})";
    }

    private function buscarPorClave(string $clave): ?MovimientoInventario
    {
        return MovimientoInventario::with('detalles')->where('idempotency_key', $clave)->first();
    }

    private function aCentesimas(string|int|float|null $valor): int
    {
        return (int) round(((float) $valor) * 100);
    }

    private function aDecimal(int $centesimas): string
    {
        return number_format($centesimas / 100, 2, '.', '');
    }
}
