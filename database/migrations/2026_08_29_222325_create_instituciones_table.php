<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instituciones', function (Blueprint $table) {

            $table->id();

            $table->string('codigo', 30)->unique();

            $table->string('tipo', 30);

            $table->string('ruc', 30)->nullable();

            $table->string('razon_social', 200);

            $table->string('nombre_comercial', 200)->nullable();

            $table->string('direccion', 255)->nullable();

            $table->string('ciudad', 100)->nullable();

            $table->foreignId('regional_id')
                ->nullable()
                ->constrained('regionales')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('telefono', 50)->nullable();

            $table->string('email', 150)->nullable();

            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->index('regional_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instituciones');
    }
};