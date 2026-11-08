<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recepciones', function (Blueprint $table) {

            $table->id();

            /*
             * Nota de Entrega que está siendo recibida.
             */
            $table->foreignId('nota_entrega_id')
                ->constrained('notas_entrega')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Bodega ORTOMAX donde se recibe físicamente.
             */
            $table->foreignId('bodega_id')
                ->constrained('bodegas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Usuario de bodega que realiza la recepción.
             */
            $table->foreignId('usuario_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Fecha y hora de recepción física.
             */
            $table->dateTime('fecha_hora');

            /*
             * Estados:
             *
             * RECIBIDA
             * EN_REVISION
             * CONFORME
             * CON_DIFERENCIAS
             * CERRADA
             */
            $table->string('estado', 30)
                ->default('RECIBIDA');

            /*
             * Observaciones generales de la recepción.
             */
            $table->text('observaciones')
                ->nullable();

            /*
             * Fecha y hora en que terminó la revisión.
             */
            $table->dateTime('fecha_cierre')
                ->nullable();

            $table->timestamps();

            $table->index('nota_entrega_id');
            $table->index('bodega_id');
            $table->index('usuario_id');
            $table->index('fecha_hora');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recepciones');
    }
};