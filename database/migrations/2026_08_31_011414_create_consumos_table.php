<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumos', function (Blueprint $table) {

            $table->id();

            /*
             * Nota de Entrega en la que se realizó
             * el consumo.
             */
            $table->foreignId('nota_entrega_id')
                ->constrained('notas_entrega')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Detalle específico de la Nota de Entrega
             * del cual proviene el consumo.
             */
            $table->foreignId('nota_entrega_detalle_id')
                ->nullable()
                ->constrained('nota_entrega_detalles')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * Equipo en el que se utilizó el implante.
             */
            $table->foreignId('equipo_id')
                ->nullable()
                ->constrained('equipos')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * Producto consumido.
             */
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Lote consumido.
             */
            $table->foreignId('lote_id')
                ->nullable()
                ->constrained('lotes')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Usuario con rol de INSTRUMENTADOR
             * que registró el consumo.
             */
            $table->foreignId('instrumentador_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Cantidad declarada como consumida.
             */
            $table->decimal('cantidad', 12, 2);

            /*
             * Fecha y hora en que el instrumentador
             * registró el consumo.
             */
            $table->dateTime('fecha_hora');

            /*
             * REGISTRADO
             * CORREGIDO
             * ANULADO
             */
            $table->string('estado', 30)
                ->default('REGISTRADO');

            /*
             * Identifica si este registro es producto
             * de una corrección.
             */
            $table->boolean('es_correccion')
                ->default(false);

            /*
             * Consumo original que fue corregido.
             */
            $table->foreignId('consumo_original_id')
                ->nullable()
                ->constrained('consumos')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->text('motivo_correccion')
                ->nullable();

            $table->text('observaciones')
                ->nullable();

            $table->timestamps();

            $table->index('nota_entrega_id');
            $table->index('nota_entrega_detalle_id');
            $table->index('equipo_id');
            $table->index('producto_id');
            $table->index('lote_id');
            $table->index('instrumentador_id');
            $table->index('fecha_hora');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumos');
    }
};