<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Catálogo de tipos de cirugía y su plantilla de equipos sugeridos.
 *
 * La plantilla apunta a equipos.codigo_referencia (el MODELO de equipo):
 * el sistema elige la copia física disponible (N.1, N.2, ...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_cirugia', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 150);
            $table->string('region_anatomica', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('activo');
        });

        Schema::create('tipo_cirugia_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_cirugia_id')
                ->constrained('tipos_cirugia')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('codigo_referencia', 100);
            $table->unsignedInteger('orden')->default(0);
            /*
             * Obligatorio: la cirugía no se puede hacer sin él.
             * Opcional: se sugiere, pero el preparador decide.
             */
            $table->boolean('obligatorio')->default(true);
            $table->string('observacion', 255)->nullable();
            $table->timestamps();

            $table->unique(['tipo_cirugia_id', 'codigo_referencia'], 'uk_tce_tipo_referencia');
            $table->index('codigo_referencia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_cirugia_equipos');
        Schema::dropIfExists('tipos_cirugia');
    }
};
