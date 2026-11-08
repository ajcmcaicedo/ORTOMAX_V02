<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reposicion_detalles', function (Blueprint $table) {

            $table->id();

            /*
             * Reposición a la que pertenece.
             */
            $table->foreignId('reposicion_id')
                ->constrained('reposiciones')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            /*
             * Producto que se va a reponer.
             */
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Lote específico que se utilizará.
             */
            $table->foreignId('lote_id')
                ->constrained('lotes')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Ubicación física de donde sale el implante.
             *
             * Ejemplo:
             * P08H37
             */
            $table->foreignId('ubicacion_id')
                ->constrained('ubicaciones')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Equipo que recibirá la reposición.
             *
             * Ejemplo:
             * P14NCC100
             */
            $table->foreignId('equipo_destino_id')
                ->constrained('equipos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Cantidad solicitada.
             */
            $table->decimal('cantidad_solicitada', 12, 2)
                ->default(0);

            /*
             * Cantidad efectivamente trasladada.
             */
            $table->decimal('cantidad_trasladada', 12, 2)
                ->default(0);

            /*
             * Cantidad efectivamente recibida por el equipo.
             */
            $table->decimal('cantidad_recibida', 12, 2)
                ->default(0);

            /*
             * Estados:
             *
             * PENDIENTE
             * PREPARADO
             * TRASLADADO
             * RECIBIDO
             * CANCELADO
             */
            $table->string('estado', 30)
                ->default('PENDIENTE');

            /*
             * Usuario que prepara físicamente
             * la reposición.
             */
            $table->foreignId('usuario_prepara_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * Usuario que confirma la recepción
             * en el equipo.
             */
            $table->foreignId('usuario_recibe_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->dateTime('fecha_preparacion')
                ->nullable();

            $table->dateTime('fecha_traslado')
                ->nullable();

            $table->dateTime('fecha_recepcion')
                ->nullable();

            $table->text('observaciones')
                ->nullable();

            $table->timestamps();

            $table->index('reposicion_id');
            $table->index('producto_id');
            $table->index('lote_id');
            $table->index('ubicacion_id');
            $table->index('equipo_destino_id');
            $table->index('estado');
            $table->index('usuario_prepara_id');
            $table->index('usuario_recibe_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reposicion_detalles');
    }
};