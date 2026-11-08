<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {

            $table->id();

            $table->string('codigo', 50)->unique();

            $table->string('nombre', 200);

            $table->text('descripcion')->nullable();

            $table->string('tipo_equipo', 50)->nullable();

            $table->foreignId('regional_id')
                ->nullable()
                ->constrained('regionales')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * Esta bodega representa la ubicación administrativa
             * del equipo cuando corresponda.
             *
             * El equipo NO es una bodega.
             */
            $table->foreignId('bodega_id')
                ->nullable()
                ->constrained('bodegas')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('estado', 30)
                ->default('ACTIVO');

            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->index('regional_id');
            $table->index('bodega_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};