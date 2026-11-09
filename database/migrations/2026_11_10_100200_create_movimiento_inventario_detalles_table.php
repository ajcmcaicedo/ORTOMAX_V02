<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Detalle de movimientos de inventario (Kardex)
|--------------------------------------------------------------------------
|
| movimientos_inventario es solo cabecera. Cada línea registra qué
| producto/lote se movió, cuánto, desde dónde, hacia dónde y el saldo
| resultante en cada punto.
|
| Un "punto de stock" puede ser:
| - Un registro de stock_lotes (bodega + ubicación + producto + lote).
| - Un registro de equipo_inventario (contenido físico de un equipo).
| - Externo (NULL): entradas de proveedor o salidas definitivas.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimiento_inventario_detalles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('movimiento_inventario_id')
                ->constrained('movimientos_inventario', 'id', 'fk_mid_movimiento_inventario_id')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('nota_entrega_detalle_id')
                ->nullable()
                ->constrained('nota_entrega_detalles', 'id', 'fk_mid_nota_entrega_detalle_id')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('producto_id')
                ->nullable()
                ->constrained('productos', 'id', 'fk_mid_producto_id')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('lote_id')
                ->nullable()
                ->constrained('lotes', 'id', 'fk_mid_lote_id')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('instrumental_id')
                ->nullable()
                ->constrained('instrumentales', 'id', 'fk_mid_instrumental_id')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('activo_id')
                ->nullable()
                ->constrained('activos', 'id', 'fk_mid_activo_id')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->decimal('cantidad', 12, 2);

            // ORIGEN
            $table->foreignId('bodega_origen_id')->nullable()
                ->constrained('bodegas', 'id', 'fk_mid_bodega_origen_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('ubicacion_origen_id')->nullable()
                ->constrained('ubicaciones', 'id', 'fk_mid_ubicacion_origen_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('equipo_origen_id')->nullable()
                ->constrained('equipos', 'id', 'fk_mid_equipo_origen_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('stock_lote_origen_id')->nullable()
                ->constrained('stock_lotes', 'id', 'fk_mid_stock_lote_origen_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('equipo_inventario_origen_id')->nullable()
                ->constrained('equipo_inventario', 'id', 'fk_mid_equipo_inventario_origen_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('saldo_origen_resultante', 12, 2)->nullable();

            // DESTINO
            $table->foreignId('bodega_destino_id')->nullable()
                ->constrained('bodegas', 'id', 'fk_mid_bodega_destino_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('ubicacion_destino_id')->nullable()
                ->constrained('ubicaciones', 'id', 'fk_mid_ubicacion_destino_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('equipo_destino_id')->nullable()
                ->constrained('equipos', 'id', 'fk_mid_equipo_destino_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('stock_lote_destino_id')->nullable()
                ->constrained('stock_lotes', 'id', 'fk_mid_stock_lote_destino_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('equipo_inventario_destino_id')->nullable()
                ->constrained('equipo_inventario', 'id', 'fk_mid_equipo_inventario_destino_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('saldo_destino_resultante', 12, 2)->nullable();

            $table->timestamps();

            $table->index(['producto_id', 'created_at'], 'idx_mid_kardex_producto');
            $table->index(['lote_id', 'created_at'], 'idx_mid_kardex_lote');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimiento_inventario_detalles');
    }
};
