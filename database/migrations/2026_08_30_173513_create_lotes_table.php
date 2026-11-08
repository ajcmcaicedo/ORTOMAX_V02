<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table) {

            $table->id();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('numero_lote', 100);

            $table->date('fecha_fabricacion')->nullable();

            $table->date('fecha_vencimiento')->nullable();

            $table->string('registro_sanitario', 100)->nullable();

            $table->string('estado', 30)
                ->default('ACTIVO');

            $table->text('observaciones')->nullable();

            $table->timestamps();

            /*
             * Un mismo producto puede tener muchos lotes,
             * pero un mismo lote no puede repetirse para ese producto.
             */
            $table->unique(
                ['producto_id', 'numero_lote'],
                'uk_lote_producto'
            );

            $table->index('producto_id');

            $table->index('numero_lote');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};