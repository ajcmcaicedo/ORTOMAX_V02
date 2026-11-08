<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_lotes', function (Blueprint $table) {

            $table->id();

            $table->foreignId('bodega_id')
                ->constrained('bodegas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('ubicacion_id')
                ->constrained('ubicaciones')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('lote_id')
                ->constrained('lotes')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->decimal('cantidad', 12, 2)
                ->default(0);

            $table->decimal('cantidad_reservada', 12, 2)
                ->default(0);

            /*
             * Disponible = existencia física - reservas.
             */
            $table->decimal('cantidad_disponible', 12, 2)
                ->storedAs('cantidad - cantidad_reservada');

            $table->string('estado', 30)
                ->default('ACTIVO');

            $table->timestamps();

            /*
             * Un producto/lote solo puede tener un registro
             * dentro de una ubicación determinada.
             */
            $table->unique(
                ['ubicacion_id', 'producto_id', 'lote_id'],
                'uk_stock_ubicacion_producto_lote'
            );

            $table->index('bodega_id');
            $table->index('ubicacion_id');
            $table->index('producto_id');
            $table->index('lote_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_lotes');
    }
};