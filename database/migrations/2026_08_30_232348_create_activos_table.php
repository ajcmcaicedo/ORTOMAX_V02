<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activos', function (Blueprint $table) {

            $table->id();

            /*
             * Producto al que pertenece el activo.
             */
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Código interno ORTOMAX del activo.
             */
            $table->string('codigo', 50)->unique();

            /*
             * Número de serie físico.
             */
            $table->string('numero_serial', 100)
                ->nullable()
                ->unique();

            $table->string('nombre', 200);

            $table->string('tipo_activo', 50)
                ->nullable();

            $table->string('marca', 100)
                ->nullable();

            $table->string('modelo', 100)
                ->nullable();

            $table->string('serie', 100)
                ->nullable();

            /*
             * DISPONIBLE
             * EN_EQUIPO
             * EN_CIRCULACION
             * EN_MANTENIMIENTO
             * BAJA
             */
            $table->string('estado', 30)
                ->default('DISPONIBLE');

            /*
             * Bodega donde está físicamente cuando
             * no se encuentra dentro de un equipo.
             */
            $table->foreignId('bodega_id')
                ->nullable()
                ->constrained('bodegas')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * Equipo donde está actualmente.
             */
            $table->foreignId('equipo_id')
                ->nullable()
                ->constrained('equipos')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->boolean('activo')
                ->default(true);

            $table->timestamps();

            $table->index('producto_id');
            $table->index('bodega_id');
            $table->index('equipo_id');
            $table->index('tipo_activo');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activos');
    }
};