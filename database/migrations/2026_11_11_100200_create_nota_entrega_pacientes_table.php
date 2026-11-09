<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Datos del paciente separados de la nota (1:1).
 *
 * - Las columnas sensibles se guardan cifradas (cast "encrypted" con
 *   APP_KEY), por eso son TEXT.
 * - identificacion_hash (HMAC) permite buscar por cédula sin descifrar.
 * - Todo acceso desde la aplicación queda en la bitácora (PACIENTE_ACCESO).
 * - El paciente puede confirmarse después: la nota en BORRADOR puede no
 *   tener registro aquí.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nota_entrega_pacientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_entrega_id')
                ->unique()
                ->constrained('notas_entrega')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->text('identificacion')->nullable();
            $table->string('identificacion_hash', 64)->nullable();
            $table->text('nombres')->nullable();
            $table->text('historia_clinica')->nullable();
            $table->string('aseguradora', 150)->nullable();
            $table->boolean('confirmado')->default(false);
            $table->timestamps();

            $table->index('identificacion_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_entrega_pacientes');
    }
};
