<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ubicaciones', function (Blueprint $table) {

            $table->id();

            $table->foreignId('bodega_id')
                ->constrained('bodegas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('codigo', 50);

            $table->string('nombre', 100)->nullable();

            $table->string('tipo', 30)
                ->default('UBICACION');

            $table->boolean('activo')->default(true);

            $table->timestamps();

            /*
             * El mismo código puede existir en diferentes bodegas,
             * pero no puede repetirse dentro de una misma bodega.
             */
            $table->unique(
                ['bodega_id', 'codigo'],
                'uk_ubicacion_bodega_codigo'
            );

            $table->index('bodega_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicaciones');
    }
};