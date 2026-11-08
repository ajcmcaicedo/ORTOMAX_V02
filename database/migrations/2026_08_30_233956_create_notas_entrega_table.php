<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_entrega', function (Blueprint $table) {

            $table->id();

            // Código único de la Nota de Entrega / QR
            $table->char('numero', 12)->unique();

            // Clínica / Hospital
            $table->foreignId('institucion_id')
                ->constrained('instituciones')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Regional responsable
            $table->foreignId('regional_id')
                ->nullable()
                ->constrained('regionales')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // Bodega desde donde se despacha
            $table->foreignId('bodega_origen_id')
                ->constrained('bodegas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->dateTime('fecha_emision');

            $table->dateTime('fecha_despacho')
                ->nullable();

            $table->dateTime('fecha_cierre')
                ->nullable();

            /*
             * Estados:
             *
             * BORRADOR
             * PREPARADA
             * DESPACHADA
             * RECIBIDA
             * EN_CIRUGIA
             * EN_DEVOLUCION
             * CERRADA
             * ANULADA
             */
            $table->string('estado', 30)
                ->default('BORRADOR');

            $table->text('observaciones')
                ->nullable();

            $table->timestamps();

            $table->index('institucion_id');
            $table->index('regional_id');
            $table->index('bodega_origen_id');
            $table->index('estado');
            $table->index('fecha_emision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_entrega');
    }
};