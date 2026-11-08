<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {

            $table->id();

            $table->dateTime('fecha_hora');

            /*
             * ENTRADA
             * SALIDA
             * TRASLADO
             * REPOSICION
             * DEVOLUCION
             * CONSUMO
             * AJUSTE
             */
            $table->string('tipo_movimiento', 30);

            // ORIGEN

            $table->foreignId('bodega_origen_id')
                ->nullable()
                ->constrained('bodegas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('equipo_origen_id')
                ->nullable()
                ->constrained('equipos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // DESTINO

            $table->foreignId('bodega_destino_id')
                ->nullable()
                ->constrained('bodegas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('equipo_destino_id')
                ->nullable()
                ->constrained('equipos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Nota que originó el movimiento

            $table->foreignId('nota_entrega_id')
                ->nullable()
                ->constrained('notas_entrega')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // Usuario

            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('referencia', 100)
                ->nullable();

            $table->text('observaciones')
                ->nullable();

            $table->string('estado', 30)
                ->default('COMPLETADO');

            $table->timestamps();

            $table->index('fecha_hora');
            $table->index('tipo_movimiento');
            $table->index('bodega_origen_id');
            $table->index('equipo_origen_id');
            $table->index('bodega_destino_id');
            $table->index('equipo_destino_id');
            $table->index('nota_entrega_id');
            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};