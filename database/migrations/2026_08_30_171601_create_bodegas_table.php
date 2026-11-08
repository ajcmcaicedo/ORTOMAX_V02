<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bodegas', function (Blueprint $table) {

            $table->id();

            $table->foreignId('regional_id')
                ->nullable()
                ->constrained('regionales')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('codigo', 50)->unique();

            $table->string('nombre', 150);

            $table->string('tipo_bodega', 30)
                ->default('SECUNDARIA');

            $table->string('direccion', 255)->nullable();

            $table->string('telefono', 50)->nullable();

            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->index('regional_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bodegas');
    }
};