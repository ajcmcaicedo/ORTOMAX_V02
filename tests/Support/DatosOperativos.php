<?php

namespace Tests\Support;

use App\Enums\EstadoNotaEntrega;
use App\Models\Bodega;
use App\Models\Equipo;
use App\Models\EquipoInventario;
use App\Models\Institucion;
use App\Models\Lote;
use App\Models\NotaEntrega;
use App\Models\NotaEntregaDetalle;
use App\Models\Producto;
use App\Models\Role;
use App\Models\StockLote;
use App\Models\TipoCirugia;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Datos mínimos para pruebas del flujo operativo.
 * Los roles y permisos vienen de la migración
 * add_notas_entrega_permisos_y_roles.
 */
trait DatosOperativos
{
    protected function usuarioConRol(string ...$roles): User
    {
        $user = User::factory()->create();

        $user->roles()->attach(Role::whereIn('nombre', $roles)->pluck('id'));

        return $user;
    }

    protected function bodega(string $tipo = 'PRINCIPAL'): Bodega
    {
        return Bodega::create([
            'codigo' => 'B' . Str::upper(Str::random(8)),
            'nombre' => 'Bodega de prueba',
            'tipo_bodega' => $tipo,
        ]);
    }

    protected function ubicacion(Bodega $bodega, string $tipo = 'UBICACION'): Ubicacion
    {
        return Ubicacion::create([
            'bodega_id' => $bodega->id,
            'codigo' => 'U' . Str::upper(Str::random(8)),
            'tipo' => $tipo,
        ]);
    }

    protected function producto(string $nombre = 'TORNILLO CORTICAL 3.5 X 20'): Producto
    {
        return Producto::create([
            'codigo' => 'P' . Str::upper(Str::random(10)),
            'nombre' => $nombre,
            'origen_producto' => 'LOCAL',
        ]);
    }

    protected function lote(Producto $producto, ?string $vence = '+2 years', string $estado = 'ACTIVO'): Lote
    {
        return Lote::create([
            'producto_id' => $producto->id,
            'numero_lote' => 'L' . Str::upper(Str::random(8)),
            'fecha_vencimiento' => $vence ? now()->modify($vence)->toDateString() : null,
            'estado' => $estado,
        ]);
    }

    protected function stock(Ubicacion $ubicacion, Lote $lote, float $cantidad, float $reservada = 0): StockLote
    {
        return StockLote::create([
            'bodega_id' => $ubicacion->bodega_id,
            'ubicacion_id' => $ubicacion->id,
            'producto_id' => $lote->producto_id,
            'lote_id' => $lote->id,
            'cantidad' => $cantidad,
            'cantidad_reservada' => $reservada,
        ])->refresh();
    }

    protected function equipo(string $referencia = 'ARIX-RADIO-DISTAL', ?Bodega $bodega = null, ?string $codigo = null, string $estado = 'ACTIVO'): Equipo
    {
        return Equipo::create([
            'codigo' => $codigo ?? 'P' . Str::upper(Str::random(8)),
            'codigo_referencia' => $referencia,
            'nombre' => 'EQUIPO ' . $referencia,
            'bodega_id' => $bodega?->id,
            'regional_id' => $bodega?->regional_id,
            'estado' => $estado,
        ])->refresh();
    }

    /**
     * Tipo de cirugía con plantilla: [referencia => obligatorio].
     */
    protected function tipoCirugia(array $plantilla, string $nombre = 'Radio distal'): TipoCirugia
    {
        $tipo = TipoCirugia::create([
            'codigo' => 'TC' . Str::upper(Str::random(6)),
            'nombre' => $nombre,
        ]);

        $orden = 1;
        foreach ($plantilla as $referencia => $obligatorio) {
            $tipo->plantilla()->create([
                'codigo_referencia' => $referencia,
                'obligatorio' => $obligatorio,
                'orden' => $orden++,
            ]);
        }

        return $tipo->refresh();
    }

    /**
     * Agrega una línea EQUIPO directamente (sin reglas), para preparar escenarios.
     */
    protected function ponerEquipoEnNota(NotaEntrega $nota, Equipo $equipo): NotaEntregaDetalle
    {
        return NotaEntregaDetalle::create([
            'nota_entrega_id' => $nota->id,
            'equipo_id' => $equipo->id,
            'tipo_linea' => 'EQUIPO',
            'codigo' => $equipo->codigo,
            'descripcion' => $equipo->nombre,
            'cantidad_enviada' => 1,
            'estado' => 'PENDIENTE',
        ]);
    }

    protected function contenidoEquipo(Equipo $equipo, Lote $lote, float $cantidad, float $reservada = 0): EquipoInventario
    {
        return EquipoInventario::create([
            'equipo_id' => $equipo->id,
            'producto_id' => $lote->producto_id,
            'lote_id' => $lote->id,
            'cantidad' => $cantidad,
            'cantidad_reservada' => $reservada,
            'estado' => 'ACTIVO',
        ])->refresh();
    }

    protected function institucion(string $nombre = 'Hospital de prueba'): Institucion
    {
        return Institucion::create([
            'codigo' => 'I' . Str::upper(Str::random(8)),
            'tipo' => 'HOSPITAL',
            'razon_social' => $nombre,
        ]);
    }

    protected function nota(EstadoNotaEntrega $estado = EstadoNotaEntrega::BORRADOR, array $atributos = []): NotaEntrega
    {
        return NotaEntrega::create($atributos + [
            'numero' => Str::upper(Str::random(12)),
            'institucion_id' => $this->institucion()->id,
            'bodega_origen_id' => $this->bodega()->id,
            'fecha_emision' => now(),
            'estado' => $estado,
        ])->refresh();
    }
}
