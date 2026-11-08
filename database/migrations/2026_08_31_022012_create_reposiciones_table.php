<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reposiciones', function (Blueprint $table) {

            $table->id();

            /*
             * Número único de la reposición.
             *
             * Ejemplo:
             * REP-2026-000001
             */
            $table->string('numero', 30)->unique();

            /*
             * Nota de Entrega que originó la reposición.
             */
            $table->foreignId('nota_entrega_id')
                ->nullable()
                ->constrained('notas_entrega')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * Recepción que detectó la diferencia.
             */
            $table->foreignId('recepcion_id')
                ->nullable()
                ->constrained('recepciones')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * Equipo que necesita la reposición.
             *
             * Ejemplo:
             * P14NCC100
             */
            $table->foreignId('equipo_id')
                ->constrained('equipos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Bodega desde donde salen los implantes.
             *
             * Ejemplo:
             * BODEGA_PRINCIPAL
             */
            $table->foreignId('bodega_origen_id')
                ->constrained('bodegas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Usuario que genera la reposición.
             */
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * Motivo:
             *
             * CONSUMO
             * FALTANTE
             * DAÑO
             * AJUSTE
             * OTRO
             */
            $table->string('motivo', 30)
                ->default('CONSUMO');

            /*
             * Estados:
             *
             * BORRADOR
             * SOLICITADA
             * APROBADA
             * PREPARADA
             * TRASLADADA
             * RECIBIDA
             * CANCELADA
             */
            $table->string('estado', 30)
                ->default('BORRADOR');

            $table->dateTime('fecha_solicitud')
                ->nullable();

            $table->dateTime('fecha_aprobacion')
                ->nullable();

            $table->dateTime('fecha_traslado')
                ->nullable();

            $table->dateTime('fecha_recepcion')
                ->nullable();

            $table->text('observaciones')
                ->nullable();

            $table->timestamps();

            $table->index('nota_entrega_id');
            $table->index('recepcion_id');
            $table->index('equipo_id');
            $table->index('bodega_origen_id');
            $table->index('usuario_id');
            $table->index('motivo');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reposiciones');
    }
};