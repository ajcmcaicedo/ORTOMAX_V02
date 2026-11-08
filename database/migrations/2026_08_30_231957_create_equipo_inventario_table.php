<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipo_inventario', function (Blueprint $table) {

            $table->id();

            $table->foreignId('equipo_id')
                ->constrained('equipos')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * El lote es fundamental para los implantes.
             */
            $table->foreignId('lote_id')
                ->nullable()
                ->constrained('lotes')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->decimal('cantidad', 12, 2)
                ->default(0);

            $table->decimal('cantidad_reservada', 12, 2)
                ->default(0);

            $table->decimal('cantidad_disponible', 12, 2)
                ->storedAs('cantidad - cantidad_reservada');

            $table->string('estado', 30)
                ->default('ACTIVO');

            $table->dateTime('fecha_ingreso')
                ->nullable();

            $table->timestamps();

            /*
             * El mismo producto/lote puede existir una sola vez
             * dentro de un equipo.
             */
            $table->unique(
                ['equipo_id', 'producto_id', 'lote_id'],
                'uk_equipo_producto_lote'
            );

            $table->index('equipo_id');
            $table->index('producto_id');
            $table->index('lote_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipo_inventario');
    }
};